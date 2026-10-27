<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;
use Database\Seeders\Concerns\SkipsUnchangedUpserts;

class UnitSeeder extends Seeder
{
    use SkipsUnchangedUpserts;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
            $units = array(
                array('id' => 1, 'name' => 'pieces'),
                array('id' => 2, 'name' => 'boxes'),
                array('id' => 3, 'name' => 'bundles'),
                array('id' => 4, 'name' => 'liters'),
                array('id' => 5, 'name' => 'kilograms'),
                array('id' => 6, 'name' => 'meters'),
           

        );

        foreach ($units as $unit) {
            $this->upsertOnlyWhenChanged(Unit::class, ['id' => $unit['id']], [
                'name' => $unit['name'],
                'created_by' => 1,
            ]);
        }
    }
}
