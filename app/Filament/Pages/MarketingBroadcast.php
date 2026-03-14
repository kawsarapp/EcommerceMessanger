<?php
namespace App\Filament\Pages;
use Filament\Pages\Page;use Filament\Forms\Contracts\HasForms;use Filament\Forms\Concerns\InteractsWithForms;use Filament\Forms\Form;use Filament\Forms\Components\{Section,Select,Textarea,FileUpload,Grid};use Filament\Notifications\Notification;use App\Models\{OrderSession,Conversation,Order,Client};use App\Services\Messenger\MessengerResponseService;use Illuminate\Support\Facades\{Http,Log};

class MarketingBroadcast extends Page implements HasForms
{
    use InteractsWithForms;
    protected static ?string $navigationIcon='heroicon-o-megaphone';protected static ?string $navigationGroup='Marketing & Sales';protected static ?string $navigationLabel='Broadcast Message';protected static ?string $title='Marketing Broadcast';protected static ?int $navigationSort=1;protected static string $view='filament.pages.marketing-broadcast';
    public ?array $data=[];
    public function mount():void{$this->form->fill();}
    public static function canAccess():bool{return true;}
    
    public function form(Form $form):Form
    {
        return $form->schema([
            Section::make('Create Broadcast Campaign')->description('মেসেঞ্জার এবং হোয়াটসঅ্যাপে আপনার কাস্টমারদের সরাসরি প্রমোশনাল অফার বা ব্যানার পাঠান।')->schema([
                Grid::make(['default' => 2])->schema([
                    Select::make('platform')->label('Platform (কোথায় পাঠাবেন?)')->options([
                        'both'=>'Messenger & WhatsApp (সবাইকে)',
                        'messenger'=>'Only Messenger (শুধুমাত্র মেসেঞ্জারে)',
                        'whatsapp'=>'Only WhatsApp (শুধুমাত্র হোয়াটসঅ্যাপে)'
                    ])->required()->default('both'),
                    
                    Select::make('audience')->label('Target Audience (কাদের পাঠাবেন?)')->options([
                        'all'=>'All Customers (যারা পেজে আগে মেসেজ দিয়েছে)',
                        'buyers'=>'Past Buyers (যাদের অর্ডার ডেলিভারি বা কমপ্লিট হয়েছে)',
                        'abandoned'=>'Abandoned Carts (যারা প্রোডাক্ট দেখেও অর্ডার করেনি)',
                        'high_value'=>'High Value VIPs (যারা ৫,০০০+ টাকার অর্ডার করেছে)',
                        'custom_wa'=>'Custom WhatsApp Numbers (নিজে নাম্বার দিন)'
                    ])->required()->default('all')->live(),
                    
                    \Filament\Forms\Components\Textarea::make('custom_wa_numbers')
                        ->label('WhatsApp Numbers (কমা বা স্পেস দিয়ে আলাদা করুন)')
                        ->placeholder("01700000000, 01800000000\nবা নিচে নিচে লিখুন")
                        ->visible(fn (\Filament\Forms\Get $get) => $get('audience') === 'custom_wa')
                        ->required(fn (\Filament\Forms\Get $get) => $get('audience') === 'custom_wa')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),

                Grid::make(['default' => 1])->schema([
                    \Filament\Forms\Components\Toggle::make('is_24h_only')
                        ->label('Only send to active users (Last 24 Hours)')
                        ->helperText('ফেসবুকের নিয়ম অনুযায়ী প্রমোশনাল মেসেজ শুধুমাত্র তাদেরকেই পাঠানো উচিত যারা গত ২৪ ঘন্টায় আপনার পেজে মেসেজ দিয়েছে। এটি চালু রাখলে আপনার পেজ নিরাপদ থাকবে।')
                        ->default(true)
                ]),
                
                FileUpload::make('image')->label('Offer Image / Banner (Optional)')->image()->directory('broadcasts')->columnSpanFull(),
                
                Textarea::make('message')->label('Broadcast Message (আপনার অফার)')->placeholder("হ্যালো {{name}}, আপনার জন্য ধামাকা অফার! আজকেই যেকোনো প্রোডাক্ট অর্ডারে পাচ্ছেন ২০% ছাড়!")->rows(4)->required()
                ->helperText("ম্যাজিক ট্রিক: কাস্টমারের আসল নাম মেনশন করতে {{name}} এবং আপনার শপের নাম দিতে {{shop}} ট্যাগ ব্যবহার করুন।"),

                Grid::make(['default' => 2])->schema([
                    \Filament\Forms\Components\TextInput::make('btn_text')->label('Call to Action Button (Optional)')->placeholder('e.g. Shop Now / Order Now')->maxLength(20),
                    \Filament\Forms\Components\TextInput::make('btn_url')->label('Button Link URL')->url()->placeholder('https://yourshop.com/product/xyz'),
                ]),
            ])->statePath('data'),
        ]);
    }

    public function sendBroadcast()
    {
        $data=$this->form->getState();$clientId=auth()->id()===1?Client::first()?->id:auth()->user()->client->id??null;
        if(!$clientId){Notification::make()->title('Error: Shop not found!')->danger()->send();return;}
        $client = Client::find($clientId);

        // ✅ Null-safe access — Undefined array key bug fix
        $platform  = $data['platform']  ?? null;
        $audience  = $data['audience']  ?? null;
        $message   = $data['message']   ?? null;
        $image     = $data['image']     ?? null;
        $is24h     = $data['is_24h_only'] ?? false;
        $btnText   = $data['btn_text']  ?? null;
        $btnUrl    = $data['btn_url']   ?? null;
        $customWaNumbers = $data['custom_wa_numbers'] ?? null;

        // ✅ Early validation
        if (!$platform || !$audience || !$message) {
            Notification::make()->title('⚠️ Required fields missing!')->body('Platform, Audience এবং Message অবশ্যই পূরণ করুন।')->warning()->send();
            return;
        }

        $finalTargets=[];

        if ($audience === 'custom_wa') {
            $rawNumbers = preg_split('/[\s,]+/', $customWaNumbers, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($rawNumbers as $num) {
                $cleanNum = preg_replace('/[^0-9\+]/', '', $num);
                if (!empty($cleanNum)) {
                    if (str_starts_with($cleanNum, '01')) $cleanNum = '88' . $cleanNum;
                    elseif (str_starts_with($cleanNum, '+')) $cleanNum = str_replace('+', '', $cleanNum);
                    
                    $waId = str_contains($cleanNum, '@s.whatsapp.net') ? $cleanNum : $cleanNum . '@s.whatsapp.net';
                    $finalTargets[] = ['id' => $waId, 'name' => 'Sir/Ma\'am', 'platform' => 'whatsapp'];
                }
            }
        } else {
            // ১. কাস্টমারদের ফিল্টার করা (Messenger + WA)
            $query=Conversation::where('client_id',$clientId);
            if($platform!=='both'){
                $query->where('platform',$platform);
            }
            if($is24h){$query->where('updated_at', '>=', now()->subHours(24));}
            
            $conversations=$query->select('sender_id','platform')->distinct()->get();
            
            foreach($conversations as $conv){
                $sId=$conv->sender_id;$plat=$conv->platform;
                $order=Order::where('client_id',$clientId)->where(function($q)use($sId){$q->where('sender_id',$sId)->orWhere('customer_phone',$sId);})->latest()->first();
                
                $name=$order?$order->customer_name:'Sir/Ma\'am';$hasBought=$order?true:false;
                $hasAbandoned=OrderSession::where('client_id',$clientId)->where('sender_id',$sId)->where('customer_info->step','!=','completed')->exists();
                $isHighValue=Order::where('client_id',$clientId)->where('sender_id',$sId)->sum('total_amount')>=5000;
                
                $shouldAdd=false;
                if($audience==='all')$shouldAdd=true;
                elseif($audience==='buyers'&&$hasBought)$shouldAdd=true;
                elseif($audience==='abandoned'&&$hasAbandoned)$shouldAdd=true;
                elseif($audience==='high_value'&&$isHighValue)$shouldAdd=true;
                
                if($shouldAdd)$finalTargets[]=['id'=>$sId,'name'=>$name,'platform'=>$plat];
            }
        }

        if(empty($finalTargets)){Notification::make()->title('No customers found in this category!')->warning()->send();return;}

        // ২. ব্রডকাস্ট সেন্ড করা
        $successCount = 0;
        $messengerService = app(MessengerResponseService::class);
        $imgUrl = $image ? asset('storage/' . $image) : null;
        $waApiUrl = config('services.whatsapp.api_url');

        foreach ($finalTargets as $target) {
            $personalizedMsg = str_replace(['{{name}}', '{{shop}}'], [$target['name'], $client->shop_name], $message);
            try {
                if ($target['platform'] === 'messenger' && $client->fb_page_token) {
                    $messengerService->sendMessengerMessage($target['id'], $personalizedMsg, $client->fb_page_token, $imgUrl, [], $btnText, $btnUrl);
                    $successCount++;
                } elseif ($target['platform'] === 'whatsapp' && $client->wa_instance_id) {
                    if ($image) {
                        $imgContent = file_get_contents(storage_path('app/public/' . $image));
                        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($imgContent);
                        $base64 = base64_encode($imgContent);
                        Http::timeout(15)->post($waApiUrl . '/api/send-message', [
                            'instance_id' => $client->wa_instance_id,
                            'to'          => $target['id'],
                            'message'     => $personalizedMsg,
                            'media'       => ['mimetype' => $mime, 'data' => $base64, 'filename' => 'offer.jpg'],
                        ]);
                    } else {
                        Http::timeout(15)->post($waApiUrl . '/api/send-message', [
                            'instance_id' => $client->wa_instance_id,
                            'to'          => $target['id'],
                            'message'     => $personalizedMsg,
                        ]);
                    }
                    $successCount++;
                }
            } catch (\Exception $e) {
                Log::error("Broadcast Error: " . $e->getMessage());
            }
        }

        $this->form->fill();
        Notification::make()->title('🚀 Broadcast Sent Successfully!')->body("মোট {$successCount} জনের কাছে আপনার অফারটি সফলভাবে পাঠানো হয়েছে।")->success()->send();
    }
}