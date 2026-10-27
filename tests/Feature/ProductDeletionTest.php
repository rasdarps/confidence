<?php

namespace Tests\Feature;

use App\Http\Controllers\Backend\ProductController;
use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class ProductDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');

        parent::setUp();
    }

    public function test_product_with_purchase_transaction_cannot_be_deleted(): void
    {
        [$product, $encryptedId] = $this->createProductWithPurchaseTransaction();

        $response = app(ProductController::class)->destroy($encryptedId);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(
            "Product '{$product->name}' cannot be deleted because it has purchase transaction records.",
            $response->getData(true)['message']
        );
        $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);
    }

    public function test_product_without_purchase_transaction_can_be_deleted(): void
    {
        $product = $this->createProduct();
        $encryptedId = Crypt::encrypt($product->id);

        $response = app(ProductController::class)->destroy($encryptedId);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame("Product '{$product->name}' was successfully deleted.", $response->getData(true)['message']);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    private function createProductWithPurchaseTransaction(): array
    {
        $product = $this->createProduct();
        $purchase = Purchase::create([
            'purchase_no' => 'PO-' . uniqid(),
            'date' => now()->toDateString(),
        ]);

        PurchaseDetail::unguarded(function () use ($purchase, $product) {
            PurchaseDetail::create([
                'purchase_id' => $purchase->id,
                'date' => now()->toDateString(),
                'category_id' => $product->category_id,
                'product_id' => $product->id,
                'buying_qty' => 1,
                'unit_cost_price' => 100,
                'unit_id' => $product->unit_id,
                'buying_price' => 100,
            ]);
        });

        return [$product, Crypt::encrypt($product->id)];
    }

    private function createProduct(): Product
    {
        $unit = Unit::create([
            'name' => 'Piece',
        ]);

        $category = Category::create([
            'name' => 'General',
        ]);

        return Product::create([
            'name' => 'Test Product ' . uniqid(),
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'quantity' => 0,
            'stock_level' => 0,
            'barcode' => null,
            'cost_price' => 0,
            'selling_price' => 0,
        ]);
    }
}
