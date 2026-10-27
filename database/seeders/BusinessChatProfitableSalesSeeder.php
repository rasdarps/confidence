<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\PurchaseDetail;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Services\Accounting\JournalPostingService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BusinessChatProfitableSalesSeeder extends Seeder
{
    public function run(): void
    {
        $changed = DB::transaction(function () {
            $changed = 0;
            $accounts = DB::table('accounting_accounts')->whereIn('code', ['1000', '1100', '4000'])->pluck('id', 'code');
            foreach (range(5, 10) as $month) {
                $from = sprintf('2026-%02d-01', $month);
                $to = CarbonImmutable::parse($from)->endOfMonth()->min(CarbonImmutable::today())->toDateString();
                if ($this->profit($from, $to) > 0) continue;
                $tag = $month === 10 ? '[CHAT TEST CURRENT 2026-10]' : BusinessChatTestDataSeeder::TAG;
                $sales = Sale::query()->where('description', 'like', $tag.'%')->whereBetween('date', [$from, $to])->whereNull('cancelled_at')->lockForUpdate()->get();
                if ($sales->isEmpty()) throw new RuntimeException('No labelled test sales found for '.$from.'. No changes committed.');
                foreach ($sales as $sale) {
                    $reference = $sale->sale_no.'-TEST-REPRICE-V1';
                    if (DB::table('accounting_journals')->where('source_type', 'sale')->where('source_id', $sale->id)->where('reference_no', $reference)->exists()) continue;
                    if ($sale->printed_at) throw new RuntimeException('Test sale '.$sale->sale_no.' was already printed; refusing to change its receipt.');
                    $payment = Payment::where('sale_id', $sale->id)->lockForUpdate()->firstOrFail();
                    $receipts = PaymentDetail::where('sale_id', $sale->id)->lockForUpdate()->get();
                    if ($receipts->count() !== 1 || abs((float) $receipts->first()->current_paid_amount - (float) $payment->paid_amount) > 0.001) {
                        throw new RuntimeException('Test sale '.$sale->sale_no.' has changed payment history; refusing to adjust it.');
                    }
                    $details = SaleDetail::with('product')->where('sale_id', $sale->id)->lockForUpdate()->get();
                    $total = 0;
                    $newLines = [];
                    foreach ($details as $detail) {
                        if (!$detail->product || !preg_match('/^CHAT TEST Product [1-5]$/', $detail->product->name)) {
                            throw new RuntimeException('Refusing to change a non-test product on sale '.$sale->sale_no.'.');
                        }
                        $cost = (float) PurchaseDetail::query()->join('purchases', 'purchases.id', '=', 'purchase_details.purchase_id')
                            ->where('purchase_details.product_id', $detail->product_id)->whereNull('purchases.cancelled_at')
                            ->whereDate('purchase_details.date', '<=', $sale->date)->orderByDesc('purchase_details.date')->orderByDesc('purchase_details.id')->value('purchase_details.unit_cost_price');
                        if ($cost <= 0) throw new RuntimeException('Missing cost for test sale '.$sale->sale_no.'.');
                        $price = max((float) $detail->unit_selling_price, round($cost * 4, 2));
                        $line = round((float) $detail->selling_qty * $price, 2);
                        $total += $line;
                        $newLines[] = [$detail, $price, $line];
                    }
                    $total = round($total, 2);
                    $oldTotal = (float) $payment->total_amount;
                    if ($total <= $oldTotal) continue;
                    if ($oldTotal <= 0) throw new RuntimeException('Invalid original test sale total.');
                    $oldPaid = (float) $payment->paid_amount;
                    $oldDue = (float) $payment->due_amount;
                    $newPaid = round($oldPaid / $oldTotal * $total, 2);
                    $newDue = round($total - $newPaid, 2);
                    $lines = [];
                    foreach (['1000' => $newPaid - $oldPaid, '1100' => $newDue - $oldDue] as $code => $delta) {
                        if ($delta <= 0) continue;
                        if (!isset($accounts[$code])) throw new RuntimeException('Missing accounting account '.$code.'.');
                        $lines[] = ['account_id' => $accounts[$code], 'debit' => round($delta, 2), 'credit' => 0,
                            'description' => 'Synthetic sale price adjustment', 'entity_type' => $code === 1100 ? 'customer' : null,
                            'entity_id' => $code === 1100 ? $payment->customer_id : null];
                    }
                    if (!isset($accounts['4000'])) throw new RuntimeException('Missing sales revenue account.');
                    $lines[] = ['account_id' => $accounts['4000'], 'debit' => 0, 'credit' => round($total - $oldTotal, 2), 'description' => 'Synthetic sale revenue increase'];
                    app(JournalPostingService::class)->postJournal('sale', $sale->id, $reference, 'CHAT TEST synthetic price increase for Sale #'.$sale->sale_no, $sale->date->toDateString(), $lines, $sale->created_by);
                    foreach ($newLines as [$detail, $price, $line]) {
                        $detail->update(['unit_selling_price' => $price, 'selling_price' => $line]);
                    }
                    $taxable = $total / 1.20;
                    $payment->update([
                        'total_amount' => $total, 'paid_amount' => $newPaid, 'due_amount' => $newDue,
                        'taxable_amount' => round($taxable, 2), 'nhil_amount' => round($taxable * 0.025, 2),
                        'getfund_amount' => round($taxable * 0.025, 2), 'vat_amount' => round($taxable * 0.15, 2),
                        'cash_tendered' => $newPaid, 'change_due' => 0,
                    ]);
                    $receipts->first()->update(['current_paid_amount' => $newPaid]);
                    Log::info('Synthetic Business Chat sale repriced', ['sale_id' => $sale->id, 'old_total' => $oldTotal, 'new_total' => $total, 'old_paid' => $oldPaid, 'new_paid' => $newPaid, 'old_due' => $oldDue, 'new_due' => $newDue]);
                    $changed++;
                }
                if ($this->profit($from, $to) <= 0) throw new RuntimeException('The test price increase is insufficient to make '.$from.' profitable. All changes rolled back.');
            }
            return $changed;
        });
        $this->command?->info('Increased '.$changed.' synthetic test sales. Genuine sales, stock quantities, and original journals were left unchanged.');
        foreach (range(5, 10) as $month) {
            $from = sprintf('2026-%02d-01', $month);
            $to = CarbonImmutable::parse($from)->endOfMonth()->min(CarbonImmutable::today())->toDateString();
            $this->command?->line(substr($from, 0, 7).' net profit: '.number_format($this->profit($from, $to), 2));
        }
    }

    private function profit(string $from, string $to): float
    {
        return round((float) DB::table('accounting_journal_lines as lines')->join('accounting_journals as journals', 'journals.id', '=', 'lines.journal_id')
            ->join('accounting_accounts as accounts', 'accounts.id', '=', 'lines.account_id')->where('journals.status', 'posted')
            ->whereBetween('journals.posting_date', [$from, $to])->whereIn('accounts.type', ['income', 'expense'])
            ->selectRaw('COALESCE(SUM(lines.credit - lines.debit), 0) as profit')->value('profit'), 2);
    }
}