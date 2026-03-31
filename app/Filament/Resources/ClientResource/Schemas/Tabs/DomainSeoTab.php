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
                                <div style="background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.3);border-radius:10px;padding:20px;margin-top:8px;">
                                    <p style="font-weight:700;font-size:13px;color:#3b82f6;margin-bottom:10px;">
                                        <svg style="display:inline;width:16px;height:16px;margin-right:6px;vertical-align:-2px;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                                        ডোমেইন কানেক্ট করার নিয়ম:
                                    </p>
                                    <p style="font-size:12px;margin-bottom:14px;opacity:0.85;">
                                        আপনার ডোমেইন কন্ট্রোল প্যানেলে (যেমন: Cloudflare, Namecheap) গিয়ে DNS Settings থেকে নিচের 
                                        <strong>A Record</strong> অথবা <strong>CNAME Record</strong> যুক্ত করুন:
                                    </p>
                                    
                                    <div style="overflow-x:auto;border-radius:8px;overflow:hidden;border:1px solid rgba(0,0,0,0.1);">
                                        <table style="width:100%;border-collapse:collapse;font-size:12px;">
                                            <thead>
                                                <tr style="background:rgba(59,130,246,0.15);">
                                                    <th style="padding:10px 14px;text-align:left;font-weight:700;border-bottom:1px solid rgba(0,0,0,0.1);">Type</th>
                                                    <th style="padding:10px 14px;text-align:left;font-weight:700;border-bottom:1px solid rgba(0,0,0,0.1);">Name / Host</th>
                                                    <th style="padding:10px 14px;text-align:left;font-weight:700;border-bottom:1px solid rgba(0,0,0,0.1);">Value / Target</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td style="padding:10px 14px;font-weight:700;color:#3b82f6;border-bottom:1px solid rgba(0,0,0,0.07);">A Record</td>
                                                    <td style="padding:10px 14px;border-bottom:1px solid rgba(0,0,0,0.07);">@ (বা আপনার ডোমেইন নাম)</td>
                                                    <td style="padding:10px 14px;font-family:monospace;font-weight:700;color:#22c55e;border-bottom:1px solid rgba(0,0,0,0.07);">' . $realServerIp . '</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="3" style="padding:6px 14px;text-align:center;font-size:11px;opacity:0.5;background:rgba(0,0,0,0.03);">অথবা (যেকোনো একটি ব্যবহার করুন)</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:10px 14px;font-weight:700;color:#3b82f6;">CNAME</td>
                                                    <td style="padding:10px 14px;">www (বা সাবডোমেইন)</td>
                                                    <td style="padding:10px 14px;font-family:monospace;font-weight:700;color:#22c55e;">asianhost.net</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <p style="margin-top:12px;font-size:11px;color:#ef4444;font-weight:700;">
                                        ⚠️ Cloudflare ব্যবহার করলে প্রথমে &quot;Proxy Status&quot; বন্ধ (DNS Only) করে Verify করুন।
                                    </p>
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
                Textarea::make('meta_description')
                    ->label('Meta Description')
                    ->rows(2)
                    ->columnSpanFull(),
            ])->columns(2),
        ];
    }
}