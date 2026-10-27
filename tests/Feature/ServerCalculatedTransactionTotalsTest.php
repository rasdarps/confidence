<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\AccountingChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ServerCalculatedTransactionTotalsTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;
    private Product $product;
    private Customer $customer;
    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        config(['pos.allow_negative_stock' => false, 'pos.prevent_backdated_invoices' => false]);
        $this->seed(AccountingChartOfAccountsSeeder::class);
        $this->operator = User::factory()->create([
            'phone' => '0242222333', 'user_type' => 'Staff', 'must_change_password' => false,
        ]);
        foreach (['sale-create', 'purchase-create'] as $permission) {
            $this->operator->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
        }
        $unit = Unit::create(['name' => 'Piece']);
        $category = Category::create(['name' => 'Mobile API Test']);
        $this->product = Product::create([
            'name' => 'Mobile Price Test Product', 'unit_id' => $unit->id, 'category_id' => $category->id,
            'quantity' => 20, 'stock_level' => 2, 'cost_price' => 10, 'selling_price' => 50,
        ]);
        $this->customer = Customer::create(['name' => 'Mobile Test Customer', 'mobile_no' => '0242222334']);
        $this->supplier = Supplier::create(['name' => 'Mobile Test Supplier', 'mobile_no' => '0242222335']);
        $this->actingAs($this->operator);
    }

    public function test_sale_ignores_tampered_client_unit_and_line_totals(): void
    {
        $this->postJson(route('sales.store'), [
            'date' => now()->toDateString(), 'product_id' => [$this->product->id], 'selling_qty' => [2],
            'unit_selling_price' => [0.01], 'selling_price' => [0.02], 'discount_amount' => 10,
            'paid_status' => 'full_payment', 'cash_tendered' => 90, 'customer_id' => $this->customer->id,
        ])->assertStatus(302);

        $this->assertDatabaseHas('sale_details', [
            'product_id' => $this->product->id, 'selling_qty' => 2,
            'unit_selling_price' => 50, 'selling_price' => 100,
        ]);
        $this->assertDatabaseHas('payments', [
            'customer_id' => $this->customer->id, 'total_amount' => 90,
            'discount_amount' => 10, 'paid_amount' => 90, 'due_amount' => 0,
            'taxable_amount' => 75,
        ]);
        $this->assertSame(18.0, (float) $this->product->fresh()->quantity);
    }

    public function test_sale_payment_validation_uses_server_price_not_submitted_total(): void
    {
        $this->postJson(route('sales.store'), [
            'date' => now()->toDateString(), 'product_id' => [$this->product->id], 'selling_qty' => [2],
            'unit_selling_price' => [0.01], 'selling_price' => [0.02],
            'paid_status' => 'full_payment', 'cash_tendered' => 1, 'customer_id' => $this->customer->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('cash_tendered');
        $this->assertDatabaseCount('sales', 0);
        $this->assertSame(20.0, (float) $this->product->fresh()->quantity);
    }

    public function test_purchase_recalculates_line_and_discounted_invoice_totals(): void
    {
        $this->postJson(route('purchases.store'), [
            'date' => now()->toDateString(), 'product_id' => [$this->product->id], 'buying_qty' => [2],
            'unit_cost_price' => [10], 'unit_selling_price' => [20], 'buying_price' => [0.01],
            'estimated_amount' => 0.01, 'discount_amount' => 2,
            'paid_status' => 'full_payment', 'supplier_id' => $this->supplier->id,
        ])->assertStatus(302);

        $this->assertDatabaseHas('purchase_details', [
            'product_id' => $this->product->id, 'buying_qty' => 2,
            'unit_cost_price' => 10, 'buying_price' => 20,
        ]);
        $this->assertDatabaseHas('purchase_payments', [
            'supplier_id' => $this->supplier->id, 'total_amount' => 18,
            'discount_amount' => 2, 'paid_amount' => 18, 'due_amount' => 0,
        ]);
        $this->assertSame(22.0, (float) $this->product->fresh()->quantity);
    }

    public function test_purchase_rejects_discount_above_server_calculated_subtotal(): void
    {
        $this->postJson(route('purchases.store'), [
            'date' => now()->toDateString(), 'product_id' => [$this->product->id], 'buying_qty' => [2],
            'unit_cost_price' => [10], 'unit_selling_price' => [20], 'buying_price' => [20],
            'estimated_amount' => 0, 'discount_amount' => 21,
            'paid_status' => 'full_payment', 'supplier_id' => $this->supplier->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('discount_amount');
        $this->assertDatabaseCount('purchases', 0);
        $this->assertSame(20.0, (float) $this->product->fresh()->quantity);
    }
}