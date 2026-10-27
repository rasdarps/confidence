<?php

namespace Database\Seeders;

use App\Models\Outlet;
use App\Models\OutletStock;
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
        $products = [
            [
                'id' => 1,
                'name' => 'Rice Cooker 2litres',
                'barcode' => Product::generateBarcode('Rice Cooker 2litres', 1),
                'unit_id' => 1,
                'category_id' => 1,
                'cost_price' => 0,
                'selling_price' => 0,
                'stock_level' => 0,
                'created_by' => 1,
            ],
            [
                'id' => 2,
                'name' => 'Stanley Cup 1.5litres',
                'barcode' => Product::generateBarcode('Stanley Cup 1.5litres', 2),
                'unit_id' => 1,
                'category_id' => 2,
                'cost_price' => 0,
                'selling_price' => 0,
                'stock_level' => 5,
                'created_by' => 1,
            ],
            [
                'id' => 3,
                'name' => '4 in 1 Blender',
                'barcode' => Product::generateBarcode('4 in 1 Blender', 3),
                'unit_id' => 1,
                'category_id' => 1,
                'cost_price' => 0,
                'selling_price' => 0,
                'stock_level' => 10,
                'created_by' => 1,
            ],
        ];

        $centralOutlet = Outlet::where('is_central', true)->first();

        foreach ($products as $productData) {
            $stockLevel = $productData['stock_level'] ?? 0;
            unset($productData['stock_level']);

            $this->upsertOnlyWhenChanged(Product::class, ['id' => $productData['id']], [
                'name' => $productData['name'],
                'barcode' => $productData['barcode'],
                'unit_id' => $productData['unit_id'],
                'category_id' => $productData['category_id'],
                'cost_price' => $productData['cost_price'],
                'selling_price' => $productData['selling_price'],
                'created_by' => $productData['created_by'],
            ]);

            if ($centralOutlet) {
                OutletStock::firstOrCreate(
                    [
                        'outlet_id' => $centralOutlet->id,
                        'product_id' => $productData['id'],
                    ],
                    [
                        'quantity' => 0,
                        'stock_level' => $stockLevel,
                    ]
                );
            }
        }
    }
}
