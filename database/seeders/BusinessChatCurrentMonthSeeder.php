<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\ExpenseVoucher;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Support\PaymentStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class BusinessChatCurrentMonthSeeder extends BusinessChatTestDataSeeder
{
    public function run(): void
    {
        $today = CarbonImmutable::today(config('app.timezone'));
        $tag = '[CHAT TEST CURRENT '.$today->format('Y-m').']';
        $created = DB::transaction(function () use ($today, $tag) {
            $actor = User::query()->orderBy('id')->value('id');
            $statuses = [PaymentStatus::FULL_PAYMENT, PaymentStatus::PARTIAL_PAYMENT, PaymentStatus::FULL_CREDIT];
            $categories = ['Transport', 'Utilities', 'Rent', 'Office Supplies', 'Maintenance'];
            $created = ['purchases' => 0, 'sales' => 0, 'expenses' => 0];
            for ($day = 1; $day <= $today->day; $day++) {
                $date = $today->startOfMonth()->addDays($day - 1)->toDateString();
                for ($entry = 1; $entry <= 2; $entry++) {
                    $sequence = ($day - 1) * 2 + $entry - 1;
                    $item = $sequence % 5 + 1;
                    $product = Product::where('name', 'CHAT TEST Product '.$item)->firstOrFail();
                    $customer = Customer::where('email', 'chat-test-customer-'.$item.'@example.invalid')->firstOrFail();
                    $supplier = Supplier::where('email', 'chat-test-supplier-'.$item.'@example.invalid')->firstOrFail();
                    $label = $tag.' '.$date.' item '.$entry;
                    $status = $statuses[$sequence % count($statuses)];
                    if (!Purchase::withTrashed()->where('description', $label)->exists()) {
                        $this->purchase($product, $supplier, $date, $label, 8 + $day + $entry, $status, $actor);
                        $created['purchases']++;
                    }
                    if (!Sale::withTrashed()->where('description', $label)->exists()) {
                        $this->sale($product, $customer, $date, $label, 2 + $entry, $status, $actor);
                        $created['sales']++;
                    }
                    $reference = 'CHAT-TEST-CURRENT-EXP-'.str_replace('-', '', $date).'-'.$entry;
                    if (!ExpenseVoucher::withTrashed()->where('reference_no', $reference)->exists()) {
                        $category = ExpenseCategory::firstOrCreate(['name' => 'CHAT TEST '.$categories[$item - 1]], ['created_by' => $actor]);
                        $this->expense($category, $date, $reference, $label, $day * 25 + $entry * 15, $actor);
                        $created['expenses']++;
                    }
                }
            }
            return $created;
        });
        $this->command?->info('Created '.$created['purchases'].' purchases, '.$created['sales'].' sales, and '.$created['expenses'].' expenses for '.$today->startOfMonth()->toDateString().' through '.$today->toDateString().'.');
        $this->command?->line('Test tag: '.$tag);
        $sales = Payment::query()->join('sales', 'sales.id', '=', 'payments.sale_id')->where('sales.description', 'like', $tag.'%');
        $purchases = PurchasePayment::query()->join('purchases', 'purchases.id', '=', 'purchase_payments.purchase_id')->where('purchases.description', 'like', $tag.'%');
        $expenses = ExpenseVoucher::where('notes', 'like', $tag.'%');
        $this->command?->line('Batch totals: purchases '.number_format((float) $purchases->sum('purchase_payments.total_amount'), 2)
            .'; sales '.number_format((float) $sales->sum('payments.total_amount'), 2)
            .'; expenses '.number_format((float) $expenses->sum('total_amount'), 2));
    }
}