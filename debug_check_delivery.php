<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$output = "";

try {
    $user = \App\Models\User::where('username', 'delivery')->first();
    if(!$user) {
        $output .= "User 'delivery' not found.\n";
    } else {
        $output .= "User found: " . $user->name . " (ID: " . $user->id . ")\n";
        
        $deliveries = \App\Models\Delivery::where('delivery_personnel_id', $user->id)->get();
        $output .= "Total Deliveries Assigned: " . $deliveries->count() . "\n";
        
        foreach($deliveries as $d) {
            $output .= " - ID: " . $d->id . " | Status: " . $d->status . " | Scheduled: " . $d->scheduled_date . "\n";
        }
    
        // Check query used in TodayDeliveries
        $todayDeliveries = \App\Models\Delivery::where('delivery_personnel_id', $user->id)
                ->where('scheduled_date', '>=', now()->startOfDay()) // today
                // ->where('scheduled_date', '<=', now()->addDays(7)->endOfDay())
                ->whereIn('status', ['scheduled', 'out_for_delivery', 'in_transit'])
                ->get();
                
        $output .= "\nVisible in 'Today's Deliveries' page:\n";
        $output .= "Count: " . $todayDeliveries->count() . "\n";
        foreach($todayDeliveries as $d) {
            $output .= " - ID: " . $d->id . " | Status: " . $d->status . " | Scheduled: " . $d->scheduled_date . "\n";
        }
        
        $unassigned = \App\Models\Order::where('status', 'processing')
            ->whereDoesntHave('delivery')
            ->count();
        $output .= "\nUnassigned Processing Orders: " . $unassigned . "\n";
    }

} catch (\Exception $e) {
    $output .= "Error: " . $e->getMessage() . "\n";
}

file_put_contents('debug_output.txt', $output);
echo "Done.\n";
