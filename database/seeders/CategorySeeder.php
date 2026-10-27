<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Database\Seeders\Concerns\SkipsUnchangedUpserts;

class CategorySeeder extends Seeder
{
    use SkipsUnchangedUpserts;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $categories = [
            [
                'id' => 1,
                'name' => 'Home Appliances',
                'created_by' => 1,
            ],
            [
                'id' => 2,
                'name' => 'Kitchen Ware',
                'created_by' => 1,
            ],
            [
                'id' => 3,
                'name' => 'Bathroom Ware',
                'created_by' => 1,
            ],
            [
                'id' => 4,
                'name' => 'Decorations',
                'created_by' => 1,
            ],
           
        ];

        foreach ($categories as $category) {
            $this->upsertOnlyWhenChanged(Category::class, ['id' => $category['id']], [
                'name' => $category['name'],
                'created_by' => $category['created_by'],
            ]);
        }
    }
}
