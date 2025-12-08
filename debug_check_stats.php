<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('username', 'delivery')->first();
echo "User: " . $user->name . " (ID: " . $user->id . ")\n";

$stats = \App\Models\Delivery::where('delivery_personnel_id', $user->id)
    ->selectRaw("count(case when status = 'scheduled' then 1 end) as scheduled")
    ->selectRaw("count(case when status = 'out_for_delivery' then 1 end) as out_for_delivery")
    ->selectRaw("count(case when status = 'in_transit' then 1 end) as in_transit")
    ->selectRaw("count(case when status = 'delivered' then 1 end) as delivered")
    ->selectRaw("count(case when status = 'failed' then 1 end) as failed")
    ->selectRaw("count(*) as total")
    ->first();

echo "Stats:\n";
echo " - Scheduled: " . $stats->scheduled . "\n";
echo " - Out for Delivery: " . $stats->out_for_delivery . "\n";
echo " - In Transit: " . $stats->in_transit . "\n";
echo " - Delivered: " . $stats->delivered . "\n";
echo " - Failed: " . $stats->failed . "\n";
echo " - Total: " . $stats->total . "\n";
