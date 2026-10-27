<?php

namespace Database\Seeders;

use App\Models\Outlet;
use Illuminate\Database\Seeder;

class OutletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Outlet::firstOrCreate(
            ['code' => 'HQ-CENTRAL'],
            [
                'name' => 'Central Warehouse / HQ',
                'type' => 'central_warehouse',
                'is_central' => true,
                'phone' => '0200000000',
                'email' => 'hq@confidence.com',
                'address' => 'Headquarters & Central Hub',
                'is_active' => true,
            ]
        );

        Outlet::firstOrCreate(
            ['code' => 'OUT-001'],
            [
                'name' => 'Main Retail Branch',
                'type' => 'retail_outlet',
                'is_central' => false,
                'phone' => '0240000001',
                'email' => 'branch1@confidence.com',
                'address' => 'Accra Central',
                'is_active' => true,
            ]
        );
    }
}
