<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Product;
use Carbon\Carbon;

class SampleOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get customer users
        $customers = User::role('customer')->get();
        $products = Product::where('is_available', true)->get();

        if ($customers->isEmpty() || $products->isEmpty()) {
            $this->command->warn('No customers or products found. Skipping order seeding.');
            return;
        }

        foreach ($customers->take(2) as $customer) {
            // Create 2-3 orders per customer
            for ($i = 0; $i < rand(2, 3); $i++) {
                $order = Order::create([
                    'user_id' => $customer->id,
                    'order_number' => 'ORD-' . strtoupper(uniqid()),
                    'status' => $this->getRandomStatus(),
                    'total_amount' => 0, // Will calculate below
                    'delivery_address' => $customer->delivery_address ?: '123 Main St, Surigao City',
                    'contact_number' => $customer->contact_number ?: '09123456789',
                    'payment_method' => 'cash_on_delivery',
                    'payment_status' => 'pending',
                    'notes' => 'Sample order for testing',
                    'created_at' => Carbon::now()->subDays(rand(1, 30)),
                ]);

                // Add 1-3 items per order
                $totalAmount = 0;
                $selectedProducts = $products->random(rand(1, 3));
                
                foreach ($selectedProducts as $product) {
                    $quantity = rand(1, 3);
                    $price = $product->price;
                    $subtotal = $quantity * $price;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'price' => $price,
                        'subtotal' => $subtotal,
                    ]);

                    $totalAmount += $subtotal;
                }

                // Update order total
                $order->update([
                    'total_amount' => $totalAmount
                ]);
            }
        }

        $this->command->info('Sample orders created successfully!');
    }

    private function getRandomStatus()
    {
        $statuses = ['pending', 'processing', 'in_transit', 'delivered'];
        return $statuses[array_rand($statuses)];
    }
}
