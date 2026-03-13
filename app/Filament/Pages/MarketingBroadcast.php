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
                Grid::make(2)->schema([
                    Select::make('platform')->label('Platform (কোথায় পাঠাবেন?)')->options([
                        'both'=>'Messenger & WhatsApp (সবাইকে)',
                        'messenger'=>'Only Messenger (শুধুমাত্র মেসেঞ্জারে)',
                        'whatsapp'=>'Only WhatsApp (শুধুমাত্র হোয়াটসঅ্যাপে)'
                    ])->required()->default('both'),
                    
                    Select::make('audience')->label('Target Audience (কাদের পাঠাবেন?)')->options([
                        'all'=>'All Customers (যারা পেজে আগে মেসেজ দিয়েছে)',
                        'buyers'=>'Past Buyers (যাদের অর্ডার ডেলিভারি বা কমপ্লিট হয়েছে)',
                        'abandoned'=>'Abandoned Carts (যারা প্রোডাক্ট দেখেও অর্ডার করেনি)',
                        'high_value'=>'High Value VIPs (যারা ৫,০০০+ টাকার অর্ডার করেছে)'
                    ])->required()->default('all'),
                ]),
                
                FileUpload::make('image')->label('Offer Image / Banner (Optional)')->image()->directory('broadcasts')->columnSpanFull(),
                
                Textarea::make('message')->label('Broadcast Message (আপনার অফার)')->placeholder("হ্যালো {{name}}, আপনার জন্য ধামাকা অফার! আজকেই যেকোনো প্রোডাক্ট অর্ডারে পাচ্ছেন ২০% ছাড়!")->rows(5)->required()
                ->helperText("ম্যাজিক ট্রিক: কাস্টমারের আসল নাম মেনশন করতে {{name}} ট্যাগ ব্যবহার করুন। সিস্টেম অটোমেটিক নাম বসিয়ে নিবে!"),
            ])->statePath('data'),
        ]);
    }

    public function sendBroadcast()
    {
        $data=$this->form->getState();$clientId=auth()->id()===1?Client::first()?->id:auth()->user()->client->id??null;
        if(!$clientId){Notification::make()->title('Error: Shop not found!')->danger()->send();return;}
        $client=Client::find($clientId);$platform=$data['platform'];$audience=$data['audience'];$message=$data['message'];$image=$data['image']??null;
        
        // ১. কাস্টমারদের ফিল্টার করা (Messenger + WA)
        $query=Conversation::where('client_id',$clientId);
        if($platform!=='both'){$query->where('platform',$platform);}
        $conversations=$query->select('sender_id','platform')->distinct()->get();
        $finalTargets=[];

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

        if(empty($finalTargets)){Notification::make()->title('No customers found in this category!')->warning()->send();return;}

        // ২. ব্রডকাস্ট সেন্ড করা
        $successCount=0;$messengerService=app(MessengerResponseService::class);$imgUrl=$image?asset('storage/'.$image):null;
        
        foreach($finalTargets as $target){
            $personalizedMsg=str_replace('{{name}}',$target['name'],$message);
            try{
                if($target['platform']==='messenger'&&$client->fb_page_token){
                    if($imgUrl) $messengerService->sendMessengerMessage($target['id'],$personalizedMsg,$client->fb_page_token,$imgUrl);
                    else $messengerService->sendMessengerMessage($target['id'],$personalizedMsg,$client->fb_page_token);
                    $successCount++;
                }elseif($target['platform']==='whatsapp'&&$client->wa_instance_id){
                    if($image){
                        $imgContent=file_get_contents(storage_path('app/public/'.$image));$mime=(new \finfo(FILEINFO_MIME_TYPE))->buffer($imgContent);$base64=base64_encode($imgContent);
                        Http::post('http://127.0.0.1:3001/api/send-message',['instance_id'=>$client->wa_instance_id,'to'=>$target['id'],'message'=>$personalizedMsg,'media'=>['mimetype'=>$mime,'data'=>$base64,'filename'=>'offer.jpg']]);
                    }else{
                        Http::post('http://127.0.0.1:3001/api/send-message',['instance_id'=>$client->wa_instance_id,'to'=>$target['id'],'message'=>$personalizedMsg]);
                    }
                    $successCount++;
                }
            }catch(\Exception $e){Log::error("Broadcast Error: ".$e->getMessage());}
        }

        $this->form->fill();
        Notification::make()->title('🚀 Broadcast Sent Successfully!')->body("মোট {$successCount} জনের কাছে আপনার অফারটি সফলভাবে পাঠানো হয়েছে।")->success()->send();
    }
}