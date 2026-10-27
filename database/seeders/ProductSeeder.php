<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Database\Seeders\Concerns\SkipsUnchangedUpserts;

class ProductSeeder extends Seeder
{
    use SkipsUnchangedUpserts;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
         $products = [
            [
                'id' => 1,
                'name' => 'Rice Cooker 2litres',
                'barcode' => Product::generateBarcode('Rice Cooker 2litres', 1),
                'quantity' => 0,
                'unit_id' => 1,
                'category_id' => 1,
                'stock_level' => 0,
                'cost_price' => 0,
                'selling_price' => 0,
                'created_by' => 1,
            ],
            [
                'id' => 2,
                'name' => 'Stanley Cup 1.5litres',
                'barcode' => Product::generateBarcode('Stanley Cup 1.5litres', 2),
                'quantity' => 0,
                'unit_id' => 1,
                'category_id' => 2,
                'stock_level' => 5,
                'cost_price' => 0,
                'selling_price' => 0,
                'created_by' => 1,
            ],
            [
                'id' => 3,
                'name' => '4 in 1 Blender',
                'barcode' => Product::generateBarcode('4 in 1 Blender', 3),
                'quantity' => 0,
                'unit_id' => 1,
                'category_id' => 1,
                'stock_level' => 10,
                'cost_price' => 0,
                'selling_price' => 0,
                'created_by' => 1,
            ],
         ];

        foreach ($products as $product) {
            $this->upsertOnlyWhenChanged(Product::class, ['id' => $product['id']], [
                'name' => $product['name'],
                'barcode' => $product['barcode'],
                'quantity' => $product['quantity'],
                'unit_id' => $product['unit_id'],
                'category_id' => $product['category_id'],
                'stock_level' => $product['stock_level'],
                'cost_price' => $product['cost_price'],
                'selling_price' => $product['selling_price'],
                'created_by' => $product['created_by'],
            ]);
        }
    }
}
