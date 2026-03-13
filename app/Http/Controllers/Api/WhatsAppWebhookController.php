<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;use Illuminate\Http\Request;use App\Models\{Client,Conversation,OrderSession};use Illuminate\Support\Facades\{Http,Storage,Log};use App\Services\ChatbotService;use Illuminate\Support\Str;
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
        $attachmentUrl=null;
        if($attachmentBase64){
            try{
                if(preg_match('/^data:([^;]+);base64,(.+)$/',$attachmentBase64,$matches)){
                    $mimeType=$matches[1];$base64Data=$matches[2];
                    $extension='file';if(str_contains($mimeType,'image')) $extension='jpg';elseif(str_contains($mimeType,'video')) $extension='mp4';elseif(str_contains($mimeType,'audio')) $extension='ogg'; 
                    $fileName='wa_'.time().'_'.uniqid().'.'.$extension;$filePath='chat_attachments/'.$fileName;
                    Storage::disk('public')->put($filePath,base64_decode($base64Data));$attachmentUrl=asset('storage/'.$filePath);
                    if(empty($messageBody)||str_starts_with($messageBody,'[Received a')) $messageBody="[User sent an attachment]"; 
                }
            }catch(\Exception $e){Log::error("WA Webhook - Attachment Processing Error: ".$e->getMessage());}
        }
        try{Conversation::create(['client_id'=>$client->id,'sender_id'=>$senderPhone,'platform'=>'whatsapp','user_message'=>$messageBody,'attachment_url'=>$attachmentUrl,'metadata'=>['sender_name'=>$senderName]]);}catch(\Exception $e){}
        try{
            $session=OrderSession::where('client_id',$client->id)->where('sender_id',$senderPhone)->first();
            if(!$session){$session=OrderSession::create(['client_id'=>$client->id,'sender_id'=>$senderPhone,'is_human_agent_active'=>false,'customer_info'=>['history'=>[]]]);}
        }catch(\Illuminate\Database\UniqueConstraintViolationException $e){$session=OrderSession::where('client_id',$client->id)->where('sender_id',$senderPhone)->first();}
        if($session&&$session->is_human_agent_active) return response()->json(['success'=>true,'message'=>'Human mode active.']);
        try{
            $aiReply=$this->chatbot->handleMessage($client,$senderPhone,$messageBody,$attachmentUrl);
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
                $aiReply=preg_replace('/\[CAROUSEL:\s*([^\]]+)\]/i','',$aiReply);$aiReply=trim(preg_replace('/\[QUICK_REPLIES:\s*([^\]]+)\]/i','',$aiReply));
                if(!empty($aiReply)){Http::post('http://127.0.0.1:3001/api/send-message',['instance_id'=>$instanceId,'to'=>$senderPhone,'message'=>$aiReply]);}
                foreach($outgoingImages as $index=>$imgUrl){
                    try{
                        $imageContent=file_get_contents($imgUrl);
                        if($imageContent!==false){
                            $mimeType=(new \finfo(FILEINFO_MIME_TYPE))->buffer($imageContent);$base64Image=base64_encode($imageContent);
                            Http::post('http://127.0.0.1:3001/api/send-message',['instance_id'=>$instanceId,'to'=>$senderPhone,'message'=>'','media'=>['mimetype'=>$mimeType,'data'=>$base64Image,'filename'=>'product_image_'.$index]]);
                        }
                    }catch(\Exception $imgEx){}
                }
                if(isset($conversation)){$conversation->update(['bot_response'=>$aiReply]);}
            }
        }catch(\Exception $e){Log::error("WA Webhook - AI Error: ".$e->getMessage());}
        return response()->json(['success'=>true]);
    }
}