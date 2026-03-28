<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Services\Courier\CourierIntegrationService;

class SyncCourierStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:sync-courier';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically checks shipping status of pending/shipped orders against Live Courier APIs (Steadfast/Pathao/RedX).';

    /**
     * Execute the console command.
     */
    public function handle(CourierIntegrationService $courierService)
    {
        $this->info('Starting background Courier API synchronization...');

        $orders = Order::whereNotNull('tracking_code')
            ->whereNotNull('courier_name')
            ->whereNotIn('order_status', ['delivered', 'cancelled'])
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No eligible orders found for syncing.');
            return;
        }

        $synced = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar(count($orders));
        $bar->start();

        foreach ($orders as $order) {
            $result = $courierService->syncStatus($order);
            if ($result['status'] === 'success') {
                $synced++;
            } else {
                $failed++;
                \Illuminate\Support\Facades\Log::warning("Auto-Sync Failed for Order ID: {$order->id}. Reason: " . $result['message']);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Synchronization Complete! Synced: {$synced}, Failed: {$failed}");
    }
}
