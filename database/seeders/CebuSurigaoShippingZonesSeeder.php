<?php

namespace Database\Seeders;

use App\Models\ShippingZone;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CebuSurigaoShippingZonesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $zones = [
            // Cebu Zones (Local - Free/Low Shipping)
            [
                'name' => 'Cebu City',
                'province' => 'Cebu',
                'region' => 'Central Visayas',
                'base_shipping_rate' => 0.00,
                'estimated_delivery_days' => 0,
                'is_active' => true,
                'requires_sea_transport' => false,
                'delivery_notes' => 'Same-day delivery available for orders placed before 2 PM',
            ],
            [
                'name' => 'Mandaue City',
                'province' => 'Cebu',
                'region' => 'Central Visayas',
                'base_shipping_rate' => 50.00,
                'estimated_delivery_days' => 0,
                'is_active' => true,
                'requires_sea_transport' => false,
                'delivery_notes' => 'Same-day delivery for nearby areas',
            ],
            [
                'name' => 'Lapu-Lapu City',
                'province' => 'Cebu',
                'region' => 'Central Visayas',
                'base_shipping_rate' => 100.00,
                'estimated_delivery_days' => 1,
                'is_active' => true,
                'requires_sea_transport' => false,
                'delivery_notes' => 'Next-day delivery via bridge connection',
            ],

            // Surigao del Norte Zones
            [
                'name' => 'Surigao City',
                'province' => 'Surigao del Norte',
                'region' => 'Caraga',
                'base_shipping_rate' => 150.00,
                'estimated_delivery_days' => 2,
                'is_active' => true,
                'requires_sea_transport' => true,
                'delivery_notes' => 'Inter-island shipping via ferry. Subject to weather conditions.',
            ],
            [
                'name' => 'Siargao Island',
                'province' => 'Surigao del Norte',
                'region' => 'Caraga',
                'base_shipping_rate' => 250.00,
                'estimated_delivery_days' => 3,
                'is_active' => true,
                'requires_sea_transport' => true,
                'delivery_notes' => 'Island destination. Weather-dependent ferry schedule.',
            ],

            // Surigao del Sur Zones
            [
                'name' => 'Tandag City',
                'province' => 'Surigao del Sur',
                'region' => 'Caraga',
                'base_shipping_rate' => 180.00,
                'estimated_delivery_days' => 2,
                'is_active' => true,
                'requires_sea_transport' => true,
                'delivery_notes' => 'Provincial capital. Regular ferry service available.',
            ],
            [
                'name' => 'Bislig City',
                'province' => 'Surigao del Sur',
                'region' => 'Caraga',
                'base_shipping_rate' => 190.00,
                'estimated_delivery_days' => 3,
                'is_active' => true,
                'requires_sea_transport' => true,
                'delivery_notes' => 'Southern route. May experience delays during monsoon season.',
            ],

            // Agusan Zones (Additional coverage)
            [
                'name' => 'Butuan City',
                'province' => 'Agusan del Norte',
                'region' => 'Caraga',
                'base_shipping_rate' => 200.00,
                'estimated_delivery_days' => 3,
                'is_active' => true,
                'requires_sea_transport' => true,
                'delivery_notes' => 'Regional center. Requires sea transport to Mindanao.',
            ],
            [
                'name' => 'Cabadbaran City',
                'province' => 'Agusan del Norte',
                'region' => 'Caraga',
                'base_shipping_rate' => 210.00,
                'estimated_delivery_days' => 3,
                'is_active' => true,
                'requires_sea_transport' => true,
                'delivery_notes' => 'Coastal city. Weather-dependent shipping schedule.',
            ],

            // Dinagat Islands
            [
                'name' => 'San Jose (Dinagat Islands)',
                'province' => 'Dinagat Islands',
                'region' => 'Caraga',
                'base_shipping_rate' => 220.00,
                'estimated_delivery_days' => 3,
                'is_active' => true,
                'requires_sea_transport' => true,
                'delivery_notes' => 'Island province. Limited ferry schedules, check weather advisories.',
            ],
        ];

        foreach ($zones as $zone) {
            ShippingZone::updateOrCreate(
                ['name' => $zone['name'], 'province' => $zone['province']],
                $zone
            );
        }

        $this->command->info('Cebu-Surigao shipping zones seeded successfully!');
        $this->command->info('Total zones: ' . count($zones));
    }
}
