<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\ExpenseCategory;
use App\Models\AccountingFiscalPeriod;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\PaymentDetail;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Database\Seeders\AccountingChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MobileBusinessApiTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;
    private Customer $customer;
    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccountingChartOfAccountsSeeder::class);
        $unit = Unit::create(['name' => 'Pieces']);
        $category = Category::create(['name' => 'General']);
        $this->product = Product::create(['name' => 'Mobile Widget', 'barcode' => '1234567890123', 'unit_id' => $unit->id, 'category_id' => $category->id,
            'quantity' => 10, 'stock_level' => 2, 'selling_price' => 24, 'cost_price' => 10]);
        $this->customer = Customer::create(['name' => 'Mobile Customer', 'mobile_no' => '0240000001']);
        $this->supplier = Supplier::create(['name' => 'Mobile Supplier', 'mobile_no' => '0240000002']);
    }

    private function operator(array $permissions): User
    {
        $user = User::factory()->create(['phone' => '024'.random_int(1000000, 9999999), 'user_type' => 'Staff', 'must_change_password' => false]);
        foreach ($permissions as $name) $user->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        return $user;
    }

    private function login(User $user): void
    {
        Auth::forgetGuards();
        $this->withToken($user->createToken('test')->plainTextToken);
    }

    private function salePayload(): array
    {
        return ['date' => now()->toDateString(), 'customer_id' => $this->customer->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 2]], 'paid_status' => 'full_payment', 'cash_tendered' => 48];
    }

    public function test_sale_api_uses_server_prices_and_replays_without_duplicate_stock_or_journals(): void
    {
        $user = $this->operator(['sale-create']);
        $this->login($user);
        $headers = ['Idempotency-Key' => 'sale-request-001'];
        $response = $this->postJson('/api/sales', $this->salePayload(), $headers)->assertCreated()
            ->assertJsonPath('data.created_by', $user->id)->assertJsonPath('data.payment.total_amount', '48.00');
        Auth::forgetGuards();
        $this->postJson('/api/sales', $this->salePayload(), $headers)->assertCreated()->assertHeader('Idempotency-Replayed', 'true')
            ->assertJsonPath('data.id', $response->json('data.id'));
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('accounting_journals', 1);
        $this->assertSame(8.0, (float) $this->product->fresh()->quantity);
        Auth::forgetGuards();
        $this->postJson('/api/sales', array_replace($this->salePayload(), ['cash_tendered' => 49]), $headers)->assertConflict();
    }

    public function test_purchase_expense_and_credit_receipts_preserve_accounting_and_replay_safety(): void
    {
        $this->login($this->operator(['purchase-create', 'sale-create', 'payment-sales-credit-pay', 'expense-create']));
        $purchase = ['date' => now()->toDateString(), 'supplier_id' => $this->supplier->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 3, 'unit_cost_price' => 10, 'unit_selling_price' => 24]],
            'paid_status' => 'full_credit'];
        $this->postJson('/api/purchases', $purchase, ['Idempotency-Key' => 'purchase-request-001'])->assertCreated()->assertJsonPath('data.payment.due_amount', '30.00');
        $category = ExpenseCategory::create(['name' => 'Transport']);
        $expense = ['voucher_date' => now()->toDateString(), 'reference_no' => 'MOBILE-EXP-1',
            'items' => [['expense_category_id' => $category->id, 'amount' => 5]]];
        $this->postJson('/api/expenses', $expense, ['Idempotency-Key' => 'expense-request-001'])->assertCreated()->assertJsonPath('data.total_amount', '5.00');
        $this->postJson('/api/expenses', $expense, ['Idempotency-Key' => 'expense-request-001'])->assertCreated()->assertHeader('Idempotency-Replayed', 'true');
        $credit = array_replace($this->salePayload(), ['paid_status' => 'full_credit', 'cash_tendered' => 0]);
        $sale = $this->postJson('/api/sales', $credit, ['Idempotency-Key' => 'credit-sale-001'])->assertCreated()->json('data.id');
        $receipt = ['amount' => 20, 'payment_date' => now()->toDateString(), 'reference' => 'MOBILE-RECEIPT'];
        $this->postJson('/api/sales/'.$sale.'/payments', $receipt, ['Idempotency-Key' => 'receipt-request-001'])->assertCreated()->assertJsonPath('data.sale.payment.due_amount', '28.00');
        $this->postJson('/api/sales/'.$sale.'/payments', $receipt, ['Idempotency-Key' => 'receipt-request-001'])->assertCreated()->assertHeader('Idempotency-Replayed', 'true');
        $this->postJson('/api/sales/'.$sale.'/payments', ['amount' => 29, 'payment_date' => now()->toDateString()], ['Idempotency-Key' => 'receipt-overpaid-001'])->assertUnprocessable();
        $this->assertSame(11.0, (float) $this->product->fresh()->quantity);
        $this->assertDatabaseCount('accounting_journals', 4);
        $this->assertDatabaseCount('payment_details', 2);
        $this->assertDatabaseCount('expense_vouchers', 1);
        $this->assertDatabaseHas('payment_details', ['reference' => 'MOBILE-RECEIPT']);
        $this->assertSame(0, DB::table('accounting_journals')->whereColumn('total_debit', '!=', 'total_credit')->count());
    }

    public function test_failed_accounting_or_invalid_write_rolls_back_stock_and_idempotency_record(): void
    {
        $this->login($this->operator(['sale-create']));
        AccountingFiscalPeriod::query()->update(['is_closed' => true]);
        $this->postJson('/api/sales', $this->salePayload(), ['Idempotency-Key' => 'closed-period-001'])->assertUnprocessable()->assertJsonValidationErrors('date');
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('mobile_api_mutations', 0);
        $this->assertSame(10.0, (float) $this->product->fresh()->quantity);
        $this->postJson('/api/sales', $this->salePayload())->assertUnprocessable();
    }

    public function test_read_apis_hide_costs_and_keep_cashier_rows_and_balances_isolated(): void
    {
        $cashier = $this->operator(['sale-create', 'sale-list', 'payment-sales-credit-list', 'payment-sales-cash-list', 'payment-sales-credit-pay', 'customer-list']);
        $cashier->assignRole(Role::firstOrCreate(['name' => 'Cashier', 'guard_name' => 'web']));
        $this->login($cashier);
        $credit = array_replace($this->salePayload(), ['paid_status' => 'full_credit', 'cash_tendered' => 0]);
        $own = $this->postJson('/api/sales', $credit, ['Idempotency-Key' => 'own-credit-001'])->assertCreated()->json('data.id');
        $foreign = Sale::create(['sale_no' => 'FOREIGN', 'date' => now()->toDateString(), 'created_by' => $this->operator([])->id]);
        Payment::create(['sale_id' => $foreign->id, 'customer_id' => $this->customer->id, 'total_amount' => 900, 'paid_amount' => 0, 'due_amount' => 900, 'paid_status' => 'full_credit']);
        $this->getJson('/api/sales?per_page=1')->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('meta.total_sales', '48.00');
        $this->getJson('/api/sales/'.$foreign->id)->assertNotFound();
        $this->getJson('/api/customer-payments/credit')->assertJsonPath('data.0.id', $own)->assertJsonPath('meta.current_due', '48.00');
        $this->getJson('/api/customers/'.$this->customer->id)->assertJsonPath('data.current_balance', '48.00');
        $this->getJson('/api/products/'.$this->product->id)->assertOk()->assertJsonMissingPath('data.cost_price');
        $this->getJson('/api/products?barcode=1234567890123')->assertJsonPath('meta.total', 1);
        $this->getJson('/api/products?per_page=101')->assertUnprocessable();
        $this->postJson('/api/sales/'.$foreign->id.'/payments', ['amount' => 10, 'payment_date' => now()->toDateString()], ['Idempotency-Key' => 'foreign-payment-001'])->assertNotFound();
        $this->getJson('/api/bank-accounts')->assertForbidden();
    }

    public function test_read_purchase_expense_and_lookup_apis_return_allowlisted_paginated_json(): void
    {
        $this->login($this->operator(['purchase-create', 'purchase-list', 'expense-create', 'expense-list', 'bank-account-list', 'bank-transaction-list', 'stock-value-report']));
        $purchase = $this->postJson('/api/purchases', ['date' => now()->toDateString(), 'supplier_id' => $this->supplier->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 1, 'unit_cost_price' => 10, 'unit_selling_price' => 24]], 'paid_status' => 'full_payment'],
            ['Idempotency-Key' => 'read-purchase-001'])->assertCreated()->json('data.id');
        $this->getJson('/api/purchases?supplier_id='.$this->supplier->id)->assertJsonPath('meta.total', 1);
        $this->getJson('/api/purchases/'.$purchase)->assertJsonPath('data.payment.total_amount', '10.00');
        $this->getJson('/api/suppliers')->assertJsonPath('data.0.id', $this->supplier->id);
        $category = ExpenseCategory::create(['name' => 'Fuel']);
        $expense = $this->postJson('/api/expenses', ['voucher_date' => now()->toDateString(), 'items' => [['expense_category_id' => $category->id, 'amount' => 15]]],
            ['Idempotency-Key' => 'read-expense-001'])->assertCreated()->json('data.id');
        $this->getJson('/api/expense-categories')->assertJsonPath('data.0.name', 'Fuel');
        $this->getJson('/api/expenses?category_id='.$category->id)->assertJsonPath('meta.total', 1);
        $this->getJson('/api/expenses/'.$expense)->assertJsonPath('data.total_amount', '15.00');
        $this->getJson('/api/products/'.$this->product->id)->assertJsonPath('data.cost_price', '10.00');
        $this->getJson('/api/bank-accounts')->assertJsonCount(0, 'data');
        $this->getJson('/api/bank-transactions')->assertJsonCount(0, 'data');
    }

    public function test_reports_and_dashboard_separate_invoice_and_payment_dates_and_preserve_scope(): void
    {
        $user = $this->operator(['sale-create', 'sale-list', 'report-sales-cash-movement', 'stock-report', 'stock-value-report', 'report-expense', 'dashboard-view']);
        $user->assignRole(Role::firstOrCreate(['name' => 'Cashier', 'guard_name' => 'web']));
        $this->login($user);
        $this->postJson('/api/sales', $this->salePayload(), ['Idempotency-Key' => 'report-sale-001'])->assertCreated();
        $older = Sale::create(['sale_no' => 'OLDER', 'date' => '2025-01-01', 'created_by' => $user->id]);
        Payment::create(['sale_id' => $older->id, 'customer_id' => $this->customer->id, 'total_amount' => 100, 'paid_amount' => 40, 'due_amount' => 60, 'paid_status' => 'partial_payment']);
        PaymentDetail::create(['sale_id' => $older->id, 'date' => now()->toDateString(), 'current_paid_amount' => 40]);
        $foreign = Sale::create(['sale_no' => 'OTHER', 'date' => now()->toDateString(), 'created_by' => $this->operator([])->id]);
        Payment::create(['sale_id' => $foreign->id, 'customer_id' => $this->customer->id, 'total_amount' => 1000, 'paid_amount' => 1000, 'due_amount' => 0]);
        PaymentDetail::create(['sale_id' => $foreign->id, 'date' => now()->toDateString(), 'current_paid_amount' => 1000]);
        $range = '?from_date='.now()->toDateString().'&to_date='.now()->toDateString();
        $this->getJson('/api/reports/sales'.$range)->assertJsonPath('data.invoice_total', '48.00')->assertJsonPath('data.invoice_count', 1);
        $this->getJson('/api/reports/collections'.$range.'&per_page=1')->assertJsonPath('meta.money_collected', '88.00')->assertJsonPath('meta.total', 2)->assertJsonCount(1, 'data');
        $this->getJson('/api/reports/stock')->assertJsonPath('meta.quantity_totals.0.quantity', 8)->assertJsonPath('meta.stock_value_at_cost', '80.00');
        $this->getJson('/api/reports/low-stock')->assertJsonPath('meta.total', 0);
        $this->getJson('/api/reports/stock?from_date=2025-01-01')->assertUnprocessable();
        $this->getJson('/api/reports/expenses')->assertJsonPath('data.expense_total', '0.00');
        $this->getJson('/api/dashboard')->assertJsonPath('data.cards.sales.invoice_total', '48.00')->assertJsonPath('data.cards.collections.amount', '88.00')
            ->assertJsonMissingPath('data.cards.bank_balances')->assertJsonMissingPath('data.cards.purchases');
        $this->getJson('/api/reports/sales?from_date=2026-10-10&to_date=2026-10-01')->assertUnprocessable();
    }

    public function test_every_business_endpoint_requires_authentication_and_correct_permission(): void
    {
        $reads = ['/api/products', '/api/products/1', '/api/sales', '/api/sales/1', '/api/customer-payments/credit', '/api/customer-payments/paid',
            '/api/customers', '/api/customers/1', '/api/suppliers', '/api/purchases', '/api/purchases/1', '/api/expense-categories', '/api/expenses',
            '/api/expenses/1', '/api/bank-accounts', '/api/bank-transactions', '/api/dashboard', '/api/reports/sales', '/api/reports/collections',
            '/api/reports/stock', '/api/reports/low-stock', '/api/reports/expenses'];
        foreach ($reads as $path) $this->getJson($path)->assertUnauthorized();
        foreach (['/api/sales', '/api/purchases', '/api/expenses', '/api/sales/1/payments'] as $path) $this->postJson($path)->assertUnauthorized();
        $this->login($this->operator([]));
        foreach ($reads as $path) $this->getJson($path)->assertForbidden();
        foreach (['/api/sales', '/api/purchases', '/api/expenses', '/api/sales/1/payments'] as $path) $this->postJson($path)->assertForbidden();
    }

    public function test_tampered_or_invalid_sales_and_deleted_products_never_mutate_stock(): void
    {
        $this->login($this->operator(['sale-create']));
        $payload = $this->salePayload();
        $payload['items'][0]['unit_selling_price'] = 1;
        $this->postJson('/api/sales', $payload, ['Idempotency-Key' => 'tamper-price-001'])->assertUnprocessable()->assertJsonValidationErrors('items.0.unit_selling_price');
        $this->postJson('/api/sales', array_merge($this->salePayload(), ['created_by' => 99]), ['Idempotency-Key' => 'tamper-creator-001'])->assertUnprocessable();
        $payload = $this->salePayload();
        $payload['items'][0]['quantity'] = 11;
        $payload['cash_tendered'] = 264;
        $this->postJson('/api/sales', $payload, ['Idempotency-Key' => 'oversell-001'])->assertUnprocessable()->assertJsonValidationErrors('items.0.quantity');
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('stock_ledgers', 0);
        $this->assertDatabaseCount('mobile_api_mutations', 0);
        $this->assertSame(10.0, (float) $this->product->fresh()->quantity);
        $this->product->delete();
        $this->postJson('/api/sales', $this->salePayload(), ['Idempotency-Key' => 'deleted-product-001'])->assertUnprocessable();
        $this->getJson('/api/products')->assertJsonPath('meta.total', 0);
    }

    public function test_stock_units_and_bank_currencies_stay_separate_and_sensitive_account_numbers_are_hidden(): void
    {
        $this->login($this->operator(['stock-report', 'bank-account-list', 'bank-transaction-list']));
        $unit = Unit::create(['name' => 'Kilograms']);
        Product::create(['name' => 'Rice', 'unit_id' => $unit->id, 'category_id' => $this->product->category_id,
            'quantity' => 1.5, 'stock_level' => 2, 'cost_price' => 4, 'selling_price' => 8]);
        $this->getJson('/api/reports/stock')->assertJsonCount(2, 'meta.quantity_totals')->assertJsonMissingPath('meta.stock_value_at_cost');
        $this->getJson('/api/reports/low-stock')->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.name', 'Rice');
        foreach (['GHS', 'USD'] as $currency) {
            $account = DB::table('bank_accounts')->insertGetId(['name' => $currency.' Bank', 'currency' => $currency, 'account_number' => 'SENSITIVE-ACCOUNT', 'current_balance' => 50]);
            DB::table('bank_transactions')->insert(['bank_account_id' => $account, 'transaction_date' => now()->toDateString(), 'type' => 'transfer_in', 'amount' => 50]);
        }
        $this->getJson('/api/bank-accounts')->assertJsonPath('meta.total', 2)->assertJsonMissingPath('data.0.account_number');
        $this->getJson('/api/bank-transactions')->assertJsonPath('meta.total', 2)->assertJsonPath('data.0.type', 'transfer_in');
        $this->postJson('/api/bank-transactions', [])->assertStatus(405);
    }

    public function test_user_capabilities_customer_lookup_and_all_payment_modes_are_usable_without_admin_permissions(): void
    {
        $user = $this->operator(['sale-create', 'payment-sales-cash-list', 'payment-sales-credit-list']);
        $user->assignRole(Role::firstOrCreate(['name' => 'Cashier', 'guard_name' => 'web']));
        $this->login($user);
        $this->getJson('/api/user')->assertJsonPath('data.capabilities.sales_create', true)->assertJsonPath('data.capabilities.bank_accounts', false)
            ->assertJsonPath('data.pos_settings.allow_negative_stock', false);
        $this->getJson('/api/customers')->assertJsonPath('data.0.id', $this->customer->id)->assertJsonMissingPath('data.0.email');
        $this->getJson('/api/sales')->assertForbidden();
        foreach (['full_payment' => [48, 48, '0.00'], 'partial_payment' => [20, 20, '28.00'], 'full_credit' => [0, 0, '48.00']] as $status => [$paid, $cash, $due]) {
            $payload = array_replace($this->salePayload(), ['paid_status' => $status, 'paid_amount' => $paid, 'cash_tendered' => $cash]);
            $this->postJson('/api/sales', $payload, ['Idempotency-Key' => 'mode-'.$status.'-001'])->assertCreated()->assertJsonPath('data.payment.due_amount', $due);
        }
        $this->getJson('/api/customer-payments/paid')->assertJsonPath('meta.total', 1);
        $this->getJson('/api/customer-payments/credit')->assertJsonPath('meta.total', 2)->assertJsonPath('meta.current_due', '76.00');
    }

    public function test_super_admin_capabilities_match_gate_authorized_routes_without_direct_permission_rows(): void
    {
        $user = $this->operator([]);
        $user->update(['user_type' => 'Super Admin']);
        $this->login($user);
        $this->getJson('/api/user')->assertJsonPath('data.capabilities.product_lookup', true)->assertJsonPath('data.capabilities.bank_accounts', true);
        $this->getJson('/api/products')->assertOk()->assertJsonPath('data.0.cost_price', '10.00');
        $this->getJson('/api/suppliers')->assertOk();
        $this->getJson('/api/reports/expenses')->assertOk();
        $this->getJson('/api/bank-accounts')->assertOk();
    }
}