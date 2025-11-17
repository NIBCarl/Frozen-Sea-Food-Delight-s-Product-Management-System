<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\Delivery;
use Carbon\Carbon;

class CreateMissingDeliveries extends Command
{
    protected $signature = 'deliveries:create-missing';
    protected $description = 'Create delivery records for orders that don\'t have them';

    public function handle()
    {
        // Find orders without deliveries
        $ordersWithoutDeliveries = Order::whereDoesntHave('delivery')
            ->whereIn('status', ['pending', 'confirmed', 'processing'])
            ->get();

        if ($ordersWithoutDeliveries->isEmpty()) {
            $this->info('No orders found without delivery records.');
            return 0;
        }

        $this->info("Found {$ordersWithoutDeliveries->count()} orders without delivery records.");
        
        $created = 0;
        foreach ($ordersWithoutDeliveries as $order) {
            // Use preferred delivery date or default to tomorrow
            $scheduledDate = $order->preferred_delivery_date 
                ? Carbon::parse($order->preferred_delivery_date)
                : now()->addDay();

            Delivery::create([
                'order_id' => $order->id,
                'scheduled_date' => $scheduledDate,
                'status' => 'scheduled',
                'delivery_personnel_id' => null,
            ]);

            $created++;
            $this->info("Created delivery for Order #{$order->order_number}");
        }

        $this->info("Successfully created {$created} delivery records.");
        return 0;
    }
}
