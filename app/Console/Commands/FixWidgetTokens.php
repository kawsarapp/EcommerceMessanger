<?php

namespace App\Console\Commands;

use App\Models\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class FixWidgetTokens extends Command
{
    protected $signature   = 'widget:fix-tokens';
    protected $description = 'Generate missing api_tokens for all clients and clear widget cache';

    public function handle(): int
    {
        $this->info('🔧 Fixing widget API tokens...');

        // 1. Find clients with missing tokens
        $missing = Client::whereNull('api_token')
            ->orWhere('api_token', '')
            ->get();

        if ($missing->isEmpty()) {
            $this->info('✅ All clients already have API tokens.');
        } else {
            $this->warn("Found {$missing->count()} client(s) without API token. Generating...");
            foreach ($missing as $client) {
                $token = Str::random(60);
                $client->update(['api_token' => $token]);
                $this->line("  → Client #{$client->id} ({$client->shop_name}): token generated");
            }
        }

        // 2. Show ALL client tokens for verification
        $this->newLine();
        $this->info('📋 All client API tokens:');
        $this->table(
            ['ID', 'Shop Name', 'AI Enabled', 'API Token (first 20 chars)'],
            Client::all()->map(fn($c) => [
                $c->id,
                $c->shop_name,
                $c->is_ai_enabled ? '✅' : '❌',
                $c->api_token ? substr($c->api_token, 0, 20) . '...' : '⚠️ MISSING',
            ])
        );

        // 3. Clear all widget-related cache
        $this->newLine();
        $this->info('🗑️  Clearing widget cache...');
        Cache::flush();
        $this->info('✅ Cache cleared.');

        $this->newLine();
        $this->info('🎉 Done! Copy the API token from the dashboard and test the widget again.');

        return Command::SUCCESS;
    }
}
