<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ConversationChatTest extends TestCase
{
    use RefreshDatabase;

    private string $conversationId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->conversationId = (string) Str::uuid();
    }

    private function operator(array $permissions): User
    {
        $user = User::factory()->create(['phone' => '024'.random_int(1000000, 9999999), 'user_type' => 'Staff', 'must_change_password' => false]);
        foreach ($permissions as $name) {
            $user->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }
        return $user;
    }

    private function ask(string $message, array $extra = [])
    {
        return $this->postJson(route('chat.ask'), array_merge(['message' => $message, 'conversation_id' => $this->conversationId], $extra));
    }

    private function invoice(string $date, float $amount, ?string $cancelled = null, ?Customer $customer = null): Sale
    {
        $customer = $customer ?? Customer::firstOrCreate(['email' => 'acme@example.test'], ['name' => 'Acme', 'mobile_no' => '0240000000']);
        $sale = Sale::create(['sale_no' => 'TEST-'.Str::random(8), 'date' => $date, 'cancelled_at' => $cancelled]);
        DB::table('payments')->insert(['sale_id' => $sale->id, 'customer_id' => $customer->id, 'paid_status' => 'partial_payment', 'total_amount' => $amount, 'paid_amount' => 20, 'due_amount' => $amount - 20]);
        return $sale;
    }

    public function test_authentication_chat_permission_and_report_permission_are_required(): void
    {
        $this->ask('sales today')->assertUnauthorized();
        $this->actingAs($this->operator(['sale-list']));
        $this->ask('sales today')->assertForbidden();
        $this->actingAs($this->operator(['chat-use']));
        $this->ask('sales today')->assertForbidden();
        $this->get(route('chat.index'))->assertOk()->assertSee('business-chat')->assertDontSee('data-question="Total sales today"', false);
    }

    public function test_floating_chat_widget_requires_permission_and_is_hidden_on_chat_page(): void
    {
        $this->assertStringNotContainsString('data-business-chat-widget', view('layout.business-chat-widget')->render());
        $this->actingAs($this->operator([]));
        $this->assertStringNotContainsString('data-business-chat-widget', view('layout.main', ['errors' => new \Illuminate\Support\ViewErrorBag()])->render());
        $this->actingAs($this->operator(['chat-use']));
        $layout = view('layout.main', ['errors' => new \Illuminate\Support\ViewErrorBag()])->render();
        $this->assertStringContainsString('data-business-chat-widget', $layout);
        $this->assertStringContainsString('aria-label="Open Business Chat"', $layout);
        $this->assertStringContainsString('href="'.route('chat.index').'" class="business-chat-launcher"', $layout);
        $this->get(route('chat.index'))->assertOk()->assertDontSee('data-business-chat-widget', false);
    }

    public function test_sales_totals_exclude_cancelled_deleted_and_out_of_range_invoices(): void
    {
        $this->invoice('2026-09-10', 120);
        $this->invoice('2026-09-10', 999, '2026-09-10 12:00:00');
        $this->invoice('2026-09-10', 888)->delete();
        $this->invoice('2026-08-10', 777);
        $this->actingAs($this->operator(['chat-use', 'sale-list', 'customer-list']));
        $this->ask('Total sales from 2026-09-01 to 2026-09-30')->assertOk()->assertJsonPath('status', 'success')
            ->assertJsonPath('answer.metrics.Invoice total (including recorded taxes)', 120)
            ->assertJsonPath('answer.metrics.Invoice count', 1);
        $this->ask('Break it down by customer')->assertJsonPath('answer.rows.0.0', 'Acme')->assertJsonPath('answer.rows.0.1', '120.00');
        $this->ask('And last month?')->assertJsonPath('status', 'success');
    }

    public function test_collections_use_payment_date_including_older_invoices(): void
    {
        $sale = $this->invoice('2026-08-01', 100);
        DB::table('payment_details')->insert(['sale_id' => $sale->id, 'date' => '2026-09-15', 'current_paid_amount' => 20]);
        DB::table('payment_details')->insert(['sale_id' => $sale->id, 'date' => '2026-08-01', 'current_paid_amount' => 10]);
        $this->actingAs($this->operator(['chat-use', 'report-sales-cash-movement']));
        $this->ask('Money collected in September 2026')->assertJsonPath('answer.metrics.Money collected', 20);
        $this->ask('Customer balances')->assertForbidden();
    }

    public function test_current_customer_balances_are_not_limited_to_today_by_default(): void
    {
        $customer = Customer::create(['name' => 'Example Customer', 'mobile_no' => '0240000001']);
        $this->invoice('2026-01-01', 100, null, $customer);
        $this->actingAs($this->operator(['chat-use', 'payment-sales-credit-list', 'customer-list']));
        $this->ask('What does customer "Example Customer" owe?')->assertJsonPath('answer.metrics.Current outstanding', 80)
            ->assertJsonPath('answer.period', 'Current, all invoice dates');
    }

    public function test_ambiguous_names_and_invalid_questions_require_clarification(): void
    {
        Customer::create(['name' => 'Acme East', 'mobile_no' => '0240000001']);
        Customer::create(['name' => 'Acme West', 'mobile_no' => '0240000002']);
        $this->actingAs($this->operator(['chat-use', 'sale-list', 'customer-list']));
        $this->ask('Sales for customer "Acme" today')->assertJsonPath('status', 'clarification');
        $this->ask('Sales on 2026-02-30')->assertJsonPath('status', 'clarification');
        $this->ask('Delete sales today')->assertJsonPath('status', 'clarification');
        $this->ask('sales today', ['conversation_id' => 'bad'])->assertUnprocessable();
        $this->ask(str_repeat('a', 501))->assertUnprocessable();
    }

    public function test_expenses_are_grouped_and_deleted_vouchers_are_excluded(): void
    {
        $category = DB::table('expense_categories')->insertGetId(['name' => 'Transport']);
        $voucher = DB::table('expense_vouchers')->insertGetId(['voucher_date' => '2026-09-10', 'total_amount' => 40]);
        DB::table('expense_voucher_items')->insert(['expense_voucher_id' => $voucher, 'expense_category_id' => $category, 'amount' => 40]);
        $deleted = DB::table('expense_vouchers')->insertGetId(['voucher_date' => '2026-09-10', 'total_amount' => 90, 'deleted_at' => now()]);
        DB::table('expense_voucher_items')->insert(['expense_voucher_id' => $deleted, 'expense_category_id' => $category, 'amount' => 90]);
        $this->actingAs($this->operator(['chat-use', 'expense-list']));
        $this->ask('Expenses for category "Transport" in September 2026')->assertJsonPath('answer.metrics.Expense total', 40)->assertJsonPath('answer.rows.0.0', 'Transport');
    }

    public function test_banking_keeps_transfers_and_currencies_separate(): void
    {
        $account = DB::table('bank_accounts')->insertGetId(['name' => 'Main Bank', 'currency' => 'GHS', 'current_balance' => 150]);
        foreach (['deposit' => 100, 'transfer_in' => 50] as $type => $amount) {
            DB::table('bank_transactions')->insert(['bank_account_id' => $account, 'transaction_date' => '2026-09-10', 'type' => $type, 'amount' => $amount]);
        }
        $this->actingAs($this->operator(['chat-use', 'bank-transaction-list']));
        $this->ask('Bank transactions in September 2026')->assertJsonCount(2, 'answer.rows')->assertJsonPath('answer.rows.1.1', 'Transfer in');
        $this->ask('Bank deposits in September 2026')->assertJsonPath('status', 'success')->assertJsonCount(1, 'answer.rows')->assertJsonPath('answer.rows.0.1', 'Deposit');
        $this->ask('Bank balances')->assertForbidden();
        $this->actingAs($this->operator(['chat-use', 'bank-account-list']));
        $this->ask('Bank balances', ['reset' => true])->assertJsonPath('answer.rows.0.2', '150.00');
        $this->ask('Bank balances in September 2026')->assertJsonPath('status', 'clarification');
    }

    public function test_reset_and_conversation_ids_isolate_follow_up_context(): void
    {
        $this->actingAs($this->operator(['chat-use', 'sale-list']));
        $this->ask('sales today')->assertJsonPath('status', 'success');
        $this->ask('And yesterday?', ['conversation_id' => (string) Str::uuid()])->assertJsonPath('status', 'clarification');
        $this->ask('And yesterday?', ['reset' => true])->assertJsonPath('status', 'clarification');
    }

    public function test_purchase_totals_supplier_payments_and_outstanding_are_distinct(): void
    {
        $supplier = DB::table('suppliers')->insertGetId(['name' => 'Wholesale', 'mobile_no' => '0241111111']);
        $purchase = DB::table('purchases')->insertGetId(['purchase_no' => 'P-1', 'date' => '2026-09-10']);
        DB::table('purchase_payments')->insert(['purchase_id' => $purchase, 'supplier_id' => $supplier, 'paid_status' => 'partial_payment', 'total_amount' => 200, 'paid_amount' => 50, 'due_amount' => 150]);
        DB::table('purchase_payment_details')->insert(['purchase_id' => $purchase, 'date' => '2026-09-11', 'current_paid_amount' => 50]);
        $this->actingAs($this->operator(['chat-use', 'purchase-list', 'supplier-list', 'report-purchase-cash-movement', 'payment-purchases-credit-list']));
        $this->ask('Purchases for supplier "Wholesale" in September 2026')->assertJsonPath('answer.metrics.Invoice total (including recorded taxes)', 200);
        $this->ask('Supplier payments for supplier "Wholesale" in September 2026')->assertJsonPath('answer.metrics.Money paid', 50);
        $this->ask('Supplier outstanding balances')->assertJsonPath('answer.metrics.Current outstanding', 150);
    }

    public function test_stock_and_best_sellers_use_active_products_and_sales(): void
    {
        $unit = DB::table('units')->insertGetId(['name' => 'Pieces']);
        $category = DB::table('categories')->insertGetId(['name' => 'General']);
        $product = DB::table('products')->insertGetId(['name' => 'Widget', 'unit_id' => $unit, 'category_id' => $category, 'quantity' => 0, 'stock_level' => 3]);
        $sale = $this->invoice('2026-09-10', 120);
        DB::table('sale_details')->insert(['sale_id' => $sale->id, 'product_id' => $product, 'unit_id' => $unit, 'category_id' => $category, 'selling_qty' => 4, 'unit_selling_price' => 30, 'selling_price' => 120]);
        $this->actingAs($this->operator(['chat-use', 'stock-report', 'report-sales-lines']));
        $this->ask('Low stock')->assertJsonPath('answer.metrics.Products at or below threshold', 1)->assertJsonPath('answer.rows.0.0', 'Widget');
        $this->ask('Low stock yesterday')->assertJsonPath('status', 'clarification');
        $this->ask('Best-selling products in September 2026')->assertJsonPath('answer.rows.0.1', 4);
    }

    public function test_comparisons_and_named_filters_recheck_permissions(): void
    {
        $this->invoice('2026-09-10', 120);
        $this->invoice('2026-08-10', 100);
        $user = $this->operator(['chat-use', 'sale-list', 'customer-list']);
        $this->actingAs($user);
        $this->ask('Compare sales in September 2026 with August 2026')->assertJsonPath('answer.metrics.Invoice total (including recorded taxes)', 120)
            ->assertJsonPath('answer.comparison.metrics.Invoice total (including recorded taxes)', 100);
        $this->ask('Sales for customer "Acme" in September 2026')->assertJsonPath('status', 'success');
        $user->revokePermissionTo('customer-list');
        $this->ask('And yesterday?')->assertForbidden();
    }

    public function test_stock_balances_separate_units_and_values_exclude_deleted_products(): void
    {
        $pieces = DB::table('units')->insertGetId(['name' => 'Pieces']);
        $kilograms = DB::table('units')->insertGetId(['name' => 'Kilograms']);
        $category = DB::table('categories')->insertGetId(['name' => 'General']);
        foreach ([
            ['Widget', $pieces, 5, 10, 15, null],
            ['Rice', $kilograms, 2.5, 4, 6, null],
            ['Empty', $pieces, 0, 20, 30, null],
            ['Removed', $pieces, 100, 50, 60, now()],
        ] as [$name, $unit, $quantity, $cost, $selling, $deleted]) {
            DB::table('products')->insert(['name' => $name, 'unit_id' => $unit, 'category_id' => $category, 'quantity' => $quantity, 'cost_price' => $cost, 'selling_price' => $selling, 'deleted_at' => $deleted]);
        }
        $this->actingAs($this->operator(['chat-use', 'stock-report', 'stock-value-report']));
        $this->ask('Show total stock balance')->assertJsonPath('status', 'success')->assertJsonPath('answer.metrics.Product count', 3)
            ->assertJsonPath('answer.metrics.Total quantity (Pieces)', 5)->assertJsonPath('answer.metrics.Total quantity (Kilograms)', 2.5)->assertJsonCount(3, 'answer.columns');
        $this->ask('Show total stock value')->assertJsonPath('answer.metrics.Stock value at cost', 60)->assertJsonPath('answer.metrics.Stock value at selling price', 90);
        $this->ask('Stock balance for product "Widget"')->assertJsonPath('answer.metrics.Product count', 1)->assertJsonPath('answer.rows.0.2', 5);
        $this->ask('And its value?')->assertJsonPath('answer.metrics.Stock value at cost', 50)->assertJsonPath('answer.rows.0.3', '50.00');
        $this->ask('Stock value for category "General" for all products')->assertJsonPath('answer.metrics.Stock value at cost', 60);
        $this->ask('Stock balance yesterday')->assertJsonPath('status', 'clarification');
        $this->ask('Compare stock balance today with yesterday')->assertJsonPath('status', 'clarification');
    }

    public function test_stock_value_permissions_and_product_resolution_are_enforced(): void
    {
        $unit = DB::table('units')->insertGetId(['name' => 'Pieces']);
        $category = DB::table('categories')->insertGetId(['name' => 'General']);
        foreach (['Widget Small', 'Widget Large'] as $name) {
            DB::table('products')->insert(['name' => $name, 'unit_id' => $unit, 'category_id' => $category, 'quantity' => 4, 'cost_price' => 10]);
        }
        $this->actingAs($this->operator(['chat-use', 'stock-report']));
        $this->ask('Stock balance for product "Widget"')->assertJsonPath('status', 'clarification');
        $this->ask('Stock balance for product "Unknown"')->assertJsonPath('status', 'clarification');
        $this->ask('Stock balance for product "Widget Small"')->assertJsonPath('status', 'success')->assertJsonMissingPath('answer.metrics.Stock value at cost');
        $this->ask('And its value?')->assertForbidden();
        $this->actingAs($this->operator(['chat-use', 'stock-value-report']));
        $this->ask('Stock value for product "Widget Small"', ['reset' => true])->assertJsonPath('answer.metrics.Stock value at cost', 40);
        $this->ask('Stock balance')->assertForbidden();
    }

    public function test_stock_category_totals_include_rows_beyond_the_display_limit(): void
    {
        $unit = DB::table('units')->insertGetId(['name' => 'Pieces']);
        $category = DB::table('categories')->insertGetId(['name' => 'General']);
        $otherCategory = DB::table('categories')->insertGetId(['name' => 'Other']);
        for ($index = 1; $index <= 51; $index++) {
            DB::table('products')->insert(['name' => 'Widget '.$index, 'unit_id' => $unit, 'category_id' => $category, 'quantity' => 1, 'cost_price' => 2, 'selling_price' => 3]);
        }
        DB::table('products')->insert(['name' => 'Other Item', 'unit_id' => $unit, 'category_id' => $otherCategory, 'quantity' => 100, 'cost_price' => 50]);
        $this->actingAs($this->operator(['chat-use', 'stock-value-report']));
        $this->ask('Stock value for category "General" for all products')->assertJsonPath('status', 'success')
            ->assertJsonPath('answer.filters.category', 'General')->assertJsonPath('answer.metrics.Product count', 51)
            ->assertJsonPath('answer.metrics.Total quantity (Pieces)', 51)->assertJsonPath('answer.metrics.Stock value at cost', 102)
            ->assertJsonCount(50, 'answer.rows');
    }

    public function test_unquoted_stock_question_selects_one_product_not_all_stock(): void
    {
        $unit = DB::table('units')->insertGetId(['name' => 'Pieces']);
        $category = DB::table('categories')->insertGetId(['name' => 'General']);
        foreach (['Stanley Cup 1.5litres' => 3, 'Other Product' => 100] as $name => $quantity) {
            DB::table('products')->insert(['name' => $name, 'unit_id' => $unit, 'category_id' => $category, 'quantity' => $quantity, 'cost_price' => 10, 'selling_price' => 15]);
        }
        $this->actingAs($this->operator(['chat-use', 'stock-value-report']));
        $this->ask('stock value for Stanley Cup 1.5litres')->assertJsonPath('status', 'success')
            ->assertJsonPath('answer.metrics.Product count', 1)->assertJsonPath('answer.metrics.Stock value at cost', 30)
            ->assertJsonPath('answer.rows.0.0', 'Stanley Cup 1.5litres');
        $this->ask('stock value for Stanley Cup 1.5litres yesterday')->assertJsonPath('status', 'clarification');
        $this->ask('stock value for Missing Product')->assertJsonPath('status', 'clarification');
    }
}