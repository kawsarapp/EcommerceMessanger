<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Client,Conversation,OrderSession};
use Illuminate\Support\Facades\{Http,Storage,Log,Cache};
use App\Services\ChatbotService;
use App\Jobs\ProcessBatchedMessage;
use Illuminate\Support\Str;

class WhatsAppWebhookController extends Controller{
    protected $chatbot;
    public function __construct(ChatbotService $chatbot){$this->chatbot=$chatbot;}
    public function updateStatus(Request $request){
        if ($request->header('Authorization') !== 'Bearer ' . env('WA_WEBHOOK_SECRET', 'super-secret-key')) {
            return response()->json(['success' => false, 'error' => 'Unauthorized Access'], 401);
        }
        $instanceId=$request->instance_id;$status=$request->status;$client=Client::where('wa_instance_id',$instanceId)->first();
        if($client){$client->update(['wa_status'=>$status]);return response()->json(['success'=>true]);}
        return response()->json(['success'=>false],404);
    }
    public function receiveMessage(Request $request){
        if ($request->header('Authorization') !== 'Bearer ' . env('WA_WEBHOOK_SECRET', 'super-secret-key')) {
            return response()->json(['success' => false, 'error' => 'Unauthorized Access'], 401);
        }
        $instanceId=$request->instance_id;$senderPhone=$request->from;$messageBody=$request->body;$senderName=$request->sender_name??'Customer';$attachmentBase64=$request->attachment; 
        $client=Client::where('wa_instance_id',$instanceId)->where('is_whatsapp_active',true)->first();
        if(!$client) return response()->json(['success'=>false,'message'=>'Bot is offline']);
        Log::info("📨 INCOMING WhatsApp | Shop: {$client->shop_name} | From: {$senderPhone} ({$senderName}) | Msg: " . substr($messageBody, 0, 100));
        $attachmentUrl=null;
        $isAudioAttachment=false;
        if($attachmentBase64){
            try{
                if(preg_match('/^data:([^;]+);base64,(.+)$/',$attachmentBase64,$matches)){
                    $mimeType=$matches[1];$base64Data=$matches[2];
                    $isAudioAttachment=str_contains($mimeType,'audio');
                    $extension='file';
                    if(str_contains($mimeType,'image')) $extension='jpg';
                    elseif(str_contains($mimeType,'video')) $extension='mp4';
                    elseif($isAudioAttachment) $extension='ogg';
                    $fileName='wa_'.time().'_'.uniqid().'.'.$extension;$filePath='chat_attachments/'.$fileName;
                    Storage::disk('public')->put($filePath,base64_decode($base64Data));$attachmentUrl=asset('storage/'.$filePath);
                    // 🎤 Audio হলে messageBody খালি রাখো — ChatbotService voice transcription করবে
                    if($isAudioAttachment){
                        $messageBody=''; // Intentionally empty so isVoiceUrl() can handle it
                        Log::info("🎤 WA Audio Received | Shop: {$client->shop_name} | From: {$senderPhone} | URL: {$attachmentUrl}");
                    } elseif(empty($messageBody)||str_starts_with($messageBody,'[Received a')){
                        $messageBody="[User sent an attachment]";
                    }
                }
            }catch(\Exception $e){Log::error("WA Webhook - Attachment Processing Error: ".$e->getMessage());}
        }
        $conversation = null;
        try{
            $conversation = Conversation::create(['client_id'=>$client->id,'sender_id'=>$senderPhone,'platform'=>'whatsapp','user_message'=>$messageBody,'attachment_url'=>$attachmentUrl,'metadata'=>['sender_name'=>$senderName]]);
        }catch(\Exception $e){}
        
        try{
            $session=OrderSession::where('client_id',$client->id)->where('sender_id',$senderPhone)->first();
            if(!$session){$session=OrderSession::create(['client_id'=>$client->id,'sender_id'=>$senderPhone,'platform'=>'whatsapp','is_human_agent_active'=>false,'customer_info'=>['history'=>[]]]);
            } elseif(empty($session->platform)){$session->update(['platform'=>'whatsapp']);} // backfill
        }catch(\Illuminate\Database\UniqueConstraintViolationException $e){$session=OrderSession::where('client_id',$client->id)->where('sender_id',$senderPhone)->first();}
        if($session&&$session->is_human_agent_active) return response()->json(['success'=>true,'message'=>'Human mode active.']);

        // ━━━ MESSAGE BATCHING ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        if ($client->message_batch_enabled) {
            $delayMs  = max(500, min(10000, (int) ($client->message_batch_delay_ms ?? 2000)));
            $delaySec = $delayMs / 1000;

            $tsKey   = "batch_last_ts_{$client->id}_{$senderPhone}";
            $msgsKey = "batch_msgs_{$client->id}_{$senderPhone}";
            $imgKey  = "batch_img_{$client->id}_{$senderPhone}";

            // Accumulate messages in cache
            $existing = Cache::get($msgsKey, []);
            if (!empty($messageBody) && $messageBody !== '[User sent an attachment]') {
                $existing[] = $messageBody;
            }
            Cache::put($msgsKey, $existing, now()->addMinutes(5));

            // Store attachment URL (last one wins)
            if ($attachmentUrl) {
                Cache::put($imgKey, $attachmentUrl, now()->addMinutes(5));
            }

            // Record timestamp and dispatch delayed job
            $now = (string) microtime(true);
            Cache::put($tsKey, $now, now()->addMinutes(5));

            ProcessBatchedMessage::dispatch(
                $client->id,
                $senderPhone,
                'whatsapp',
                $now,
                $instanceId
            )->delay(now()->addSeconds($delaySec));

            Log::info("⏳ WA Batch queued | Shop: {$client->shop_name} | Sender: {$senderPhone} | Delay: {$delayMs}ms | Msgs so far: " . count($existing));
            return response()->json(['success' => true, 'batching' => true]);
        }
        // ━━━ END BATCHING ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

        try{

            $aiReply=$this->chatbot->handleMessage($client,$senderPhone,$messageBody,$attachmentUrl,'whatsapp');
            if($aiReply){
                $outgoingImages=[];
                // 🔥 NEW: Extracting the secret ATTACH_IMAGE tags
                if(preg_match_all('/\[ATTACH_IMAGE:\s*(https?:\/\/[^\]]+)\]/i',$aiReply,$imgMatches)){
                    foreach($imgMatches[1] as $imgUrl){$outgoingImages[]=trim($imgUrl);}
                    $aiReply=preg_replace('/\[ATTACH_IMAGE:\s*https?:\/\/[^\]]+\]/i','',$aiReply);
                }elseif(preg_match_all('/\[IMAGE:\s*(https?:\/\/[^\]]+)\]/i',$aiReply,$imgMatches)){
                    foreach($imgMatches[1] as $imgUrl){$outgoingImages[]=trim($imgUrl);}
                    $aiReply=preg_replace('/[0-9]+\.?\s*\[IMAGE:\s*https?:\/\/[^\]]+\]/i','',$aiReply);
                    $aiReply=preg_replace('/-\s*\[IMAGE:\s*https?:\/\/[^\]]+\]/i','',$aiReply);
                    $aiReply=preg_replace('/\[IMAGE:\s*https?:\/\/[^\]]+\]/i','',$aiReply);
                }
                // [QUICK_REPLIES] conversion to text menu
                if (preg_match('/\[QUICK_REPLIES:\s*([^\]]+)\]/i', $aiReply, $matches)) {
                    $options = explode(',', $matches[1]);
                    $menu = "\n\n(অর্ডার করতে পছন্দটি টাইপ করুন)\n";
                    foreach ($options as $opt) {
                        $cleanOpt = trim(str_replace(['"', "'"], '', $opt));
                        if (!empty($cleanOpt)) {
                            $menu .= "▪️ {$cleanOpt}\n";
                        }
                    }
                    $aiReply = str_replace($matches[0], $menu, $aiReply);
                }

                // [CAROUSEL] conversion to text menu + images
                if (preg_match('/\[CAROUSEL:\s*([\d,\s]+)\]/i', $aiReply, $matches)) {
                    $carouselIds = explode(',', $matches[1]);
                    $products = \App\Models\Product::whereIn('id', $carouselIds)->get();
                    $aiReply = str_replace($matches[0], '', $aiReply); // remove tag
                    
                    if ($products->isNotEmpty()) {
                        $aiReply .= "\n🛍️ আমাদের কালেকশন:\n\n";
                        foreach ($products as $idx => $p) {
                            $num = $idx + 1;
                            $price = $p->sale_price > 0 ? $p->sale_price : $p->regular_price;
                            $aiReply .= "👉 *{$num}. {$p->name}*\n";
                            $aiReply .= "দাম: ৳{$price}\n\n";
                            
                            if (!empty($p->thumbnail)) {
                                $outgoingImages[] = asset('storage/' . ltrim($p->thumbnail, '/'));
                            }
                        }
                        $aiReply .= "(কোনোটি পছন্দ হলে তার নাম লিখে জানান)\n";
                    }
                }
                
                $aiReply = trim($aiReply);
                if(!empty($aiReply)){Http::post(config('services.whatsapp.api_url') . '/api/send-message',['instance_id'=>$instanceId,'to'=>$senderPhone,'message'=>$aiReply]);}
                foreach($outgoingImages as $index=>$imgUrl){
                    try{
                        // ✅ Use cURL with SSL disabled (fixes self-signed cert issue on asianhost.net)
                        $ch = curl_init($imgUrl);
                        curl_setopt_array($ch, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_TIMEOUT        => 20,
                            CURLOPT_SSL_VERIFYPEER => false,
                            CURLOPT_SSL_VERIFYHOST => 0,
                        ]);
                        $imageContent = curl_exec($ch);
                        $httpCode     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);

                        if($imageContent !== false && $httpCode < 400){
                            $finfo = new \finfo(FILEINFO_MIME_TYPE);
                            $mimeType = $finfo->buffer($imageContent);
                            $base64Image = base64_encode($imageContent);
                            Http::post(config('services.whatsapp.api_url') . '/api/send-message',[
                                'instance_id' => $instanceId,
                                'to'          => $senderPhone,
                                'message'     => '',
                                'media'       => [
                                    'mimetype' => $mimeType,
                                    'data'     => $base64Image,
                                    'filename' => 'product_image_' . $index,
                                ],
                            ]);
                            Log::info("✅ WA Image Sent | To: {$senderPhone} | URL: " . substr($imgUrl, 0, 80));
                        } else {
                            // Fallback: image URL পাঠিয়ে দাও
                            Http::post(config('services.whatsapp.api_url') . '/api/send-message',[
                                'instance_id' => $instanceId,
                                'to'          => $senderPhone,
                                'message'     => "📸 Product Image: " . $imgUrl,
                            ]);
                            Log::warning("⚠️ WA Image Download Failed (HTTP {$httpCode}), sent URL fallback: {$imgUrl}");
                        }
                    }catch(\Exception $imgEx){
                        Log::error("❌ WA Image Send Error: " . $imgEx->getMessage());
                    }
                }

                
                if($conversation){
                    $conversation->update(['bot_response'=>$aiReply]);
                    Log::info("✅ OUTGOING WhatsApp | Shop: {$client->shop_name} | To: {$senderPhone} | AI: " . substr($aiReply, 0, 100));
                }
            }
        }catch(\Exception $e){Log::error("WA Webhook - AI Error: ".$e->getMessage());}
        return response()->json(['success'=>true]);
    }
}