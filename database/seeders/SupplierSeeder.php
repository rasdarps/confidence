<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Database\Seeders\Concerns\SkipsUnchangedUpserts;

class SupplierSeeder extends Seeder
{
    use SkipsUnchangedUpserts;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $suppliers = [
            [
                'id' => 1,
                'name' => 'cash_purchase',
                'mobile_no' => '0000000000',
                'email' => 'cash_purchase@email.com',
                'address' => 'PMB Accra Ghana',
                'city' => 'Accra',
                'type' => 'Manufacturer',
                'created_by' => 1,
            ],
            
        ];

        foreach ($suppliers as $supplier) {
            $this->upsertOnlyWhenChanged(Supplier::class, ['id' => $supplier['id']], [
                'name' => $supplier['name'],
                'mobile_no' => $supplier['mobile_no'],
                'email' => $supplier['email'],
                'address' => $supplier['address'],
                'city' => $supplier['city'],
                'type' => $supplier['type'],
                'created_by' => $supplier['created_by'],
            ]);
        }
    }
}
