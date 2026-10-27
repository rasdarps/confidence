<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ExpenseVoucher;
use App\Models\Purchase;
use App\Models\Sale;
use Database\Seeders\AccountingChartOfAccountsSeeder;
use Database\Seeders\BusinessChatTestDataSeeder;
use Database\Seeders\BusinessChatCurrentMonthSeeder;
use Database\Seeders\BusinessChatProfitableSalesSeeder;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BusinessChatTestDataSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_seeder_creates_five_purchases_and_sales_per_month_with_consistent_ledgers(): void
    {
        $this->seed(AccountingChartOfAccountsSeeder::class);
        $this->seed(BusinessChatTestDataSeeder::class);
        foreach (range(5, 9) as $month) {
            $this->assertSame(5, Purchase::whereMonth('date', $month)->whereYear('date', 2026)->count());
            $this->assertSame(5, Sale::whereMonth('date', $month)->whereYear('date', 2026)->count());
            $expenses = ExpenseVoucher::whereMonth('voucher_date', $month)->whereYear('voucher_date', 2026);
            $this->assertSame(5, (clone $expenses)->count());
            $this->assertEquals(1500 + ($month - 5) * 125, $expenses->sum('total_amount'));
        }
        $this->assertSame(25, DB::table('purchase_payments')->count());
        $this->assertSame(25, DB::table('payments')->count());
        $this->assertSame(25, DB::table('purchase_payment_details')->count());
        $this->assertSame(25, DB::table('payment_details')->count());
        $this->assertSame(50, DB::table('stock_ledgers')->count());
        $this->assertSame(75, DB::table('accounting_journals')->count());
        $this->assertSame(25, DB::table('expense_voucher_items')->count());
        foreach (ExpenseVoucher::with('items')->get() as $expense) {
            $this->assertEqualsWithDelta((float) $expense->total_amount, $expense->items->sum('amount'), 0.001);
            $this->assertSame('posted', $expense->accounting_status);
        }
        $this->assertSame(0, DB::table('accounting_journals')->whereColumn('total_debit', '!=', 'total_credit')->count());
        foreach (Product::all() as $product) {
            $bought = DB::table('purchase_details')->where('product_id', $product->id)->sum('buying_qty');
            $sold = DB::table('sale_details')->where('product_id', $product->id)->sum('selling_qty');
            $this->assertSame((float) ($bought - $sold), (float) $product->quantity);
            $this->assertGreaterThanOrEqual(0, $product->quantity);
        }
        foreach (['payments', 'purchase_payments'] as $table) {
            foreach (DB::table($table)->get() as $payment) {
                $this->assertEqualsWithDelta($payment->total_amount, $payment->paid_amount + $payment->due_amount, 0.001);
            }
            $this->assertSame(3, DB::table($table)->distinct()->count('paid_status'));
        }
        $quantities = Product::pluck('quantity', 'id')->all();
        $this->seed(BusinessChatTestDataSeeder::class);
        $this->assertSame(25, Purchase::count());
        $this->assertSame(25, Sale::count());
        $this->assertSame(50, DB::table('stock_ledgers')->count());
        $this->assertSame(75, DB::table('accounting_journals')->count());
        $this->assertSame(25, ExpenseVoucher::count());
        $this->assertSame(25, DB::table('expense_voucher_items')->count());
        $this->assertSame($quantities, Product::pluck('quantity', 'id')->all());
    }

    public function test_closed_fiscal_period_rolls_back_the_entire_batch(): void
    {
        $this->seed(AccountingChartOfAccountsSeeder::class);
        DB::table('accounting_fiscal_periods')->insert(['name' => 'Closed May', 'start_date' => '2026-05-01', 'end_date' => '2026-05-31', 'is_closed' => true]);
        try {
            $this->seed(BusinessChatTestDataSeeder::class);
            $this->fail('A closed fiscal period should prevent posting test data.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('closed fiscal period', $exception->getMessage());
        }
        $this->assertSame(0, Product::count());
        $this->assertSame(0, Purchase::count());
        $this->assertSame(0, Sale::count());
        $this->assertSame(0, DB::table('stock_ledgers')->count());
        $this->assertSame(0, ExpenseVoucher::count());
        $this->assertSame(0, DB::table('expense_voucher_items')->count());
    }

    public function test_current_month_batch_ends_today_and_does_not_duplicate_existing_data(): void
    {
        CarbonImmutable::setTestNow('2026-10-03 12:00:00');
        $this->seed(AccountingChartOfAccountsSeeder::class);
        $this->seed(BusinessChatTestDataSeeder::class);
        $this->seed(BusinessChatCurrentMonthSeeder::class);
        foreach (range(1, 3) as $day) {
            $date = sprintf('2026-10-%02d', $day);
            $this->assertSame(2, Sale::whereDate('date', $date)->count());
            $this->assertSame(2, Purchase::whereDate('date', $date)->count());
            $this->assertSame(2, ExpenseVoucher::whereDate('voucher_date', $date)->count());
        }
        $this->assertSame(0, Sale::whereDate('date', '>', '2026-10-03')->count());
        $this->assertSame(0, Purchase::whereDate('date', '>', '2026-10-03')->count());
        $this->assertSame(0, ExpenseVoucher::whereDate('voucher_date', '>', '2026-10-03')->count());
        $this->assertEquals(1620, DB::table('purchase_payments')->join('purchases', 'purchases.id', '=', 'purchase_payments.purchase_id')->whereMonth('purchases.date', 10)->sum('purchase_payments.total_amount'));
        $this->assertEquals(727.5, DB::table('payments')->join('sales', 'sales.id', '=', 'payments.sale_id')->whereMonth('sales.date', 10)->sum('payments.total_amount'));
        $this->assertEquals(435, ExpenseVoucher::whereMonth('voucher_date', 10)->sum('total_amount'));
        $this->assertSame(93, DB::table('accounting_journals')->count());
        $this->assertSame(0, DB::table('accounting_journals')->whereColumn('total_debit', '!=', 'total_credit')->count());
        $quantities = Product::pluck('quantity', 'id')->all();
        $this->seed(BusinessChatCurrentMonthSeeder::class);
        $this->assertSame(31, Sale::count());
        $this->assertSame(31, Purchase::count());
        $this->assertSame(31, ExpenseVoucher::count());
        $this->assertSame(62, DB::table('stock_ledgers')->count());
        $this->assertSame(93, DB::table('accounting_journals')->count());
        $this->assertSame($quantities, Product::pluck('quantity', 'id')->all());
    }

    public function test_only_synthetic_sales_are_repriced_with_balanced_adjustments_and_no_stock_change(): void
    {
        CarbonImmutable::setTestNow('2026-10-03 12:00:00');
        $this->seed(AccountingChartOfAccountsSeeder::class);
        $this->seed(BusinessChatTestDataSeeder::class);
        $this->seed(BusinessChatCurrentMonthSeeder::class);
        $genuine = Sale::create(['sale_no' => 'REAL-EXAMPLE', 'date' => '2026-05-01', 'description' => 'Unrelated sale']);
        $quantities = Product::pluck('quantity', 'id')->all();
        $journals = DB::table('accounting_journals')->orderBy('id')->get()->toArray();
        $this->seed(BusinessChatProfitableSalesSeeder::class);
        $this->assertSame($quantities, Product::pluck('quantity', 'id')->all());
        $this->assertSame(62, DB::table('stock_ledgers')->count());
        $this->assertEquals($journals, DB::table('accounting_journals')->whereIn('id', array_column($journals, 'id'))->orderBy('id')->get()->toArray());
        $this->assertSame('Unrelated sale', $genuine->fresh()->description);
        $this->assertSame(0, DB::table('accounting_journals')->whereColumn('total_debit', '!=', 'total_credit')->count());
        foreach (range(5, 10) as $month) {
            $profit = DB::table('accounting_journal_lines as l')->join('accounting_journals as j', 'j.id', '=', 'l.journal_id')
                ->join('accounting_accounts as a', 'a.id', '=', 'l.account_id')->where('j.status', 'posted')->whereMonth('j.posting_date', $month)
                ->whereIn('a.type', ['income', 'expense'])->selectRaw('SUM(l.credit - l.debit) as profit')->value('profit');
            $this->assertGreaterThan(0, $profit);
        }
        foreach (DB::table('payments')->get() as $payment) {
            $this->assertEqualsWithDelta($payment->total_amount, $payment->paid_amount + $payment->due_amount, 0.001);
            $this->assertEqualsWithDelta($payment->total_amount, DB::table('sale_details')->where('sale_id', $payment->sale_id)->sum('selling_price'), 0.001);
            $this->assertEqualsWithDelta($payment->paid_amount, DB::table('payment_details')->where('sale_id', $payment->sale_id)->sum('current_paid_amount'), 0.001);
        }
        $afterCount = DB::table('accounting_journals')->count();
        $afterTotals = DB::table('payments')->orderBy('id')->pluck('total_amount')->all();
        $this->seed(BusinessChatProfitableSalesSeeder::class);
        $this->assertSame($afterCount, DB::table('accounting_journals')->count());
        $this->assertSame($afterTotals, DB::table('payments')->orderBy('id')->pluck('total_amount')->all());
    }
}