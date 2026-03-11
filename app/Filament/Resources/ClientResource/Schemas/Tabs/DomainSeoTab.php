<?php
namespace App\Filament\Resources\ClientResource\Schemas\Tabs;

use App\Models\Client;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

class DomainSeoTab
{
    public static function schema(): array
    {
        return [
            Section::make('Custom Domain Setup')
                ->description('আপনার শপের জন্য নিজস্ব ডোমেইন (e.g. yourbrand.com) সেটআপ করুন।')
                ->icon('heroicon-m-globe-alt')
                ->schema([
                    TextInput::make('custom_domain')
                        ->label('Your Domain Name')
                        ->placeholder('yourbrand.com (without https://)')
                        ->prefixIcon('heroicon-m-globe-alt')
                        ->unique(Client::class, 'custom_domain', ignoreRecord: true)
                        ->suffixAction(
                            Action::make('verify_domain')
                                ->icon('heroicon-m-check-badge')
                                ->color('success')
                                ->label('Verify Setup')
                                ->action(function ($state, $livewire) {
                                    if (!$state) {
                                        Notification::make()->title('Please enter a domain first.')->warning()->send();
                                        return;
                                    }
                                    
                                    $domain = preg_replace('/^https?:\/\//', '', $state);
                                    $domain = trim($domain, '/');
                                    
                                    try {
                                        $realServerIp = '198.38.91.154'; 
                                        $mainDomain = 'asianhost.net';
                                        
                                        $recordsA = dns_get_record($domain, DNS_A);
                                        $recordsCNAME = dns_get_record($domain, DNS_CNAME);
                                        
                                        $isMatched = false;

                                        foreach ($recordsCNAME as $record) {
                                            if (isset($record['target']) && $record['target'] === $mainDomain) {
                                                $isMatched = true; break;
                                            }
                                        }

                                        if (!$isMatched) {
                                            foreach ($recordsA as $record) {
                                                if (isset($record['ip']) && $record['ip'] === $realServerIp) {
                                                    $isMatched = true; break;
                                                }
                                            }
                                        }

                                        if ($isMatched) {
                                            Notification::make()->title('✅ Domain Verified!')->body('DNS record is perfectly pointing to our server.')->success()->send();
                                        } else {
                                            Notification::make()->title('❌ DNS Not Matched!')->body("Please point your domain to IP: {$realServerIp} or add a CNAME for {$mainDomain}.")->danger()->send();
                                        }
                                    } catch (\Exception $e) {
                                        Notification::make()->title('❌ Verification Failed')->body('Could not check DNS records.')->danger()->send();
                                    }
                                })
                        ),

                    Placeholder::make('dns_instructions')
                        ->label('DNS Setup Instructions (অবশ্যই করণীয়)')
                        ->content(function () {
                            $realServerIp = '198.38.91.154';
                            return new HtmlString('
                                <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 text-sm text-gray-800 shadow-sm mt-2">
                                    <p class="mb-3 font-bold text-blue-800"><i class="fas fa-info-circle"></i> ডোমেইন কানেক্ট করার নিয়ম:</p>
                                    <p class="mb-4">আপনার ডোমেইন কন্ট্রোল প্যানেলে (যেমন: Cloudflare, Namecheap) গিয়ে DNS Settings থেকে নিচের <strong>A Record</strong> অথবা <strong>CNAME Record</strong> যুক্ত করুন:</p>
                                    
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-left border-collapse bg-white rounded-lg overflow-hidden shadow-sm">
                                            <thead>
                                                <tr class="bg-gray-100 text-gray-700">
                                                    <th class="border-b p-3 font-bold">Type</th>
                                                    <th class="border-b p-3 font-bold">Name / Host</th>
                                                    <th class="border-b p-3 font-bold">Value / Target</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="border-b p-3 font-bold text-blue-600">A Record</td>
                                                    <td class="border-b p-3">@ (বা আপনার ডোমেইন নাম)</td>
                                                    <td class="border-b p-3 font-mono font-bold text-green-600">' . $realServerIp . '</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="3" class="text-center text-xs text-gray-400 py-1 bg-gray-50">অথবা (যেকোনো একটি ব্যবহার করুন)</td>
                                                </tr>
                                                <tr>
                                                    <td class="border-b p-3 font-bold text-blue-600">CNAME</td>
                                                    <td class="border-b p-3">www (বা সাবডোমেইন)</td>
                                                    <td class="border-b p-3 font-mono font-bold text-green-600">asianhost.net</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <p class="mt-4 text-xs text-red-500 font-bold">* Cloudflare ব্যবহার করলে প্রথমে "Proxy Status" বন্ধ (DNS Only) করে Verify করুন।</p>
                                </div>
                            ');
                        })
                        ->columnSpanFull(),
                ]),

            Section::make('SEO & Analytics')->schema([
                TextInput::make('meta_title')
                    ->label('Meta Title')
                    ->placeholder('Best Online Shop in BD')
                    ->maxLength(60),
                TextInput::make('pixel_id')
                    ->label('Facebook Pixel ID')
                    ->numeric(),
                Textarea::make('meta_description')
                    ->label('Meta Description')
                    ->rows(2)
                    ->columnSpanFull(),
            ])->columns(2),
        ];
    }
}