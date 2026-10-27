<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\ExpenseVoucher;
use App\Models\ExpenseVoucherItem;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\PurchasePayment;
use App\Models\PurchasePaymentDetail;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\Accounting\AccountingIntegrationService;
use App\Services\StockLedgerService;
use App\Support\PaymentStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BusinessChatTestDataSeeder extends Seeder
{
    public const TAG = '[CHAT TEST MAY-SEP 2026]';

    public function run(): void
    {
        $created = DB::transaction(function () {
            $actor = User::query()->orderBy('id')->value('id');
            $category = Category::firstOrCreate(['name' => 'CHAT TEST Products (May-Sep 2026)'], ['created_by' => $actor]);
            $unit = Unit::firstOrCreate(['name' => 'CHAT TEST Pieces'], ['created_by' => $actor]);
            $statuses = [PaymentStatus::FULL_PAYMENT, PaymentStatus::PARTIAL_PAYMENT, PaymentStatus::FULL_CREDIT, PaymentStatus::FULL_PAYMENT, PaymentStatus::PARTIAL_PAYMENT];
            $expenseCategories = ['Transport', 'Utilities', 'Rent', 'Office Supplies', 'Maintenance'];
            $created = ['purchases' => 0, 'sales' => 0, 'expenses' => 0];

            for ($monthIndex = 0; $monthIndex < 5; $monthIndex++) {
                $month = CarbonImmutable::parse('2026-05-01')->addMonths($monthIndex);
                for ($itemIndex = 1; $itemIndex <= 5; $itemIndex++) {
                    $customer = Customer::firstOrCreate(['email' => 'chat-test-customer-'.$itemIndex.'@example.invalid'], [
                        'name' => 'CHAT TEST Customer '.$itemIndex, 'mobile_no' => 'TEST-C-26050'.$itemIndex, 'created_by' => $actor,
                    ]);
                    $supplier = Supplier::firstOrCreate(['email' => 'chat-test-supplier-'.$itemIndex.'@example.invalid'], [
                        'name' => 'CHAT TEST Supplier '.$itemIndex, 'mobile_no' => 'TEST-S-26050'.$itemIndex, 'created_by' => $actor,
                    ]);
                    $cost = 10 + $itemIndex * 5;
                    $selling = $cost * 1.5;
                    $product = Product::firstOrCreate(['name' => 'CHAT TEST Product '.$itemIndex], [
                        'unit_id' => $unit->id, 'category_id' => $category->id, 'quantity' => 0, 'stock_level' => 10,
                        'cost_price' => $cost, 'selling_price' => $selling, 'created_by' => $actor,
                    ]);
                    if ($product->wasRecentlyCreated) {
                        ProductPrice::create(['product_id' => $product->id, 'cost_price' => $cost, 'selling_price' => $selling, 'created_by' => $actor]);
                    }
                    $status = $statuses[($monthIndex + $itemIndex - 1) % count($statuses)];
                    $purchaseDate = $month->addDays(1 + ($itemIndex - 1) * 4)->toDateString();
                    $saleDate = CarbonImmutable::parse($purchaseDate)->addDays(2)->toDateString();
                    $label = self::TAG.' '.$month->format('Y-m').' item '.$itemIndex;

                    if (!Purchase::withTrashed()->where('description', $label)->exists()) {
                        $this->purchase($product, $supplier, $purchaseDate, $label, 20 + $monthIndex * 5 + $itemIndex * 2, $status, $actor);
                        $created['purchases']++;
                    }
                    if (!Sale::withTrashed()->where('description', $label)->exists()) {
                        $this->sale($product, $customer, $saleDate, $label, 3 + $monthIndex + $itemIndex, $status, $actor);
                        $created['sales']++;
                    }
                    $reference = 'CHAT-TEST-EXP-'.$month->format('Ym').'-'.$itemIndex;
                    if (!ExpenseVoucher::withTrashed()->where('reference_no', $reference)->exists()) {
                        $expenseCategory = ExpenseCategory::firstOrCreate(['name' => 'CHAT TEST '.$expenseCategories[$itemIndex - 1]], ['created_by' => $actor]);
                        $expenseDate = CarbonImmutable::parse($purchaseDate)->addDay()->toDateString();
                        $this->expense($expenseCategory, $expenseDate, $reference, $label, $itemIndex * 100 + $monthIndex * 25, $actor);
                        $created['expenses']++;
                    }
                }
            }
            return $created;
        });

        $this->command?->info('Created '.$created['purchases'].' purchases, '.$created['sales'].' sales, and '.$created['expenses'].' expenses. Test tag: '.self::TAG);
        foreach (range(5, 9) as $monthNumber) {
            $month = sprintf('2026-%02d', $monthNumber);
            $from = $month.'-01';
            $to = CarbonImmutable::parse($from)->endOfMonth()->toDateString();
            $purchases = PurchasePayment::query()->join('purchases', 'purchases.id', '=', 'purchase_payments.purchase_id')
                ->where('purchases.description', 'like', self::TAG.'%')->whereBetween('purchases.date', [$from, $to]);
            $sales = Payment::query()->join('sales', 'sales.id', '=', 'payments.sale_id')
                ->where('sales.description', 'like', self::TAG.'%')->whereBetween('sales.date', [$from, $to]);
            $expenses = ExpenseVoucher::query()->where('notes', 'like', self::TAG.'%')->whereBetween('voucher_date', [$from, $to]);
            $this->command?->line($month.': purchases '.(clone $purchases)->count().' / '.number_format((float) $purchases->sum('purchase_payments.total_amount'), 2)
                .'; sales '.(clone $sales)->count().' / '.number_format((float) $sales->sum('payments.total_amount'), 2)
                .'; expenses '.(clone $expenses)->count().' / '.number_format((float) $expenses->sum('total_amount'), 2));
        }
    }

    protected function purchase(Product $product, Supplier $supplier, string $date, string $label, int $quantity, string $status, ?int $actor): void
    {
        $product = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
        $purchase = Purchase::create(['purchase_no' => $this->nextNumber('purchases', 'purchase_no'), 'date' => $date, 'description' => $label, 'created_by' => $actor]);
        $total = round($quantity * (float) $product->cost_price, 2);
        $detail = new PurchaseDetail();
        $detail->forceFill([
            'purchase_id' => $purchase->id, 'date' => $date, 'category_id' => $product->category_id,
            'product_id' => $product->id, 'unit_id' => $product->unit_id, 'buying_qty' => $quantity,
            'unit_cost_price' => $product->cost_price, 'buying_price' => $total,
        ])->save();
        $before = (float) $product->quantity;
        $product->quantity = $before + $quantity;
        $product->save();
        $this->movement($product, 'purchase', $purchase->id, $detail->id, $date, $quantity, $before, $label, $actor);
        $paid = $this->paid($total, $status);
        $payment = PurchasePayment::create([
            'purchase_id' => $purchase->id, 'supplier_id' => $supplier->id, 'paid_status' => $status,
            'total_amount' => $total, 'paid_amount' => $paid, 'due_amount' => round($total - $paid, 2), 'discount_amount' => 0,
        ]);
        PurchasePaymentDetail::create(['purchase_id' => $purchase->id, 'date' => $date, 'current_paid_amount' => $paid, 'updated_by' => $actor]);
        app(AccountingIntegrationService::class)->postPurchaseFromPayment($purchase, $payment, $actor);
    }

    protected function sale(Product $product, Customer $customer, string $date, string $label, int $quantity, string $status, ?int $actor): void
    {
        $product = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
        $before = (float) $product->quantity;
        if ($before < $quantity) {
            throw new \RuntimeException('Insufficient stock for test product '.$product->name.'. No test data has been committed.');
        }
        $sale = Sale::create(['sale_no' => $this->nextNumber('sales', 'sale_no'), 'date' => $date, 'description' => $label, 'created_by' => $actor]);
        $total = round($quantity * (float) $product->selling_price, 2);
        $detail = SaleDetail::create([
            'sale_id' => $sale->id, 'date' => $date, 'category_id' => $product->category_id,
            'product_id' => $product->id, 'unit_id' => $product->unit_id, 'selling_qty' => $quantity,
            'unit_selling_price' => $product->selling_price, 'selling_price' => $total,
        ]);
        $product->quantity = $before - $quantity;
        $product->save();
        $this->movement($product, 'sale', $sale->id, $detail->id, $date, $quantity, $before, $label, $actor);
        $paid = $this->paid($total, $status);
        $taxable = $total / 1.20;
        $payment = Payment::create([
            'sale_id' => $sale->id, 'customer_id' => $customer->id, 'paid_status' => $status,
            'total_amount' => $total, 'paid_amount' => $paid, 'due_amount' => round($total - $paid, 2), 'discount_amount' => 0,
            'taxable_amount' => round($taxable, 2), 'nhil_amount' => round($taxable * 0.025, 2),
            'getfund_amount' => round($taxable * 0.025, 2), 'vat_amount' => round($taxable * 0.15, 2),
            'cash_tendered' => $paid, 'change_due' => 0,
        ]);
        PaymentDetail::create(['sale_id' => $sale->id, 'date' => $date, 'current_paid_amount' => $paid, 'updated_by' => $actor]);
        app(AccountingIntegrationService::class)->postSaleFromPayment($sale, $payment, $actor);
    }

    protected function expense(ExpenseCategory $category, string $date, string $reference, string $label, float $amount, ?int $actor): void
    {
        $voucher = ExpenseVoucher::create([
            'voucher_date' => $date, 'reference_no' => $reference, 'total_amount' => $amount,
            'notes' => $label.' expense: '.$category->name, 'created_by' => $actor,
        ]);
        ExpenseVoucherItem::create([
            'expense_voucher_id' => $voucher->id, 'expense_category_id' => $category->id,
            'amount' => $amount, 'description' => $label.' expense: '.$category->name, 'created_by' => $actor,
        ]);
        app(AccountingIntegrationService::class)->postExpenseVoucher($voucher, $actor);
    }

    private function movement(Product $product, string $type, int $invoiceId, int $detailId, string $date, int $quantity, float $before, string $label, ?int $actor): void
    {
        app(StockLedgerService::class)->record([
            'product_id' => $product->id, 'movement_type' => $type, 'reference_type' => $type === 'sale' ? 'Sale' : 'purchase',
            'reference_id' => $invoiceId, 'reference_detail_id' => $detailId, 'movement_at' => $date,
            'qty_in' => $type === 'purchase' ? $quantity : 0, 'qty_out' => $type === 'sale' ? $quantity : 0,
            'balance_before' => $before, 'balance_after' => $product->quantity,
            'unit_cost' => $product->cost_price, 'unit_price' => $product->selling_price,
            'reason' => 'Business Chat synthetic test data', 'notes' => $label, 'created_by' => $actor,
        ]);
    }

    private function paid(float $total, string $status): float
    {
        return match ($status) {
            PaymentStatus::FULL_PAYMENT => $total,
            PaymentStatus::PARTIAL_PAYMENT => round($total * 0.4, 2),
            default => 0,
        };
    }

    private function nextNumber(string $table, string $column): string
    {
        $maximum = DB::table($table)->lockForUpdate()->selectRaw('COALESCE(MAX(CAST('.$column.' AS UNSIGNED)), 0) as number')->value('number');
        return str_pad((string) ((int) $maximum + 1), 2, '0', STR_PAD_LEFT);
    }
}