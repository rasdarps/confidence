<?php

namespace App\Http\Controllers\Backend;

use App\DataTables\Admin\Accounting\BankAccountsDataTable;
use App\DataTables\Admin\Accounting\BankTransactionsDataTable;
use App\Http\Controllers\Controller;
use App\Models\AccountingAccount;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\PurchasePayment;
use App\Models\PurchasePaymentDetail;
use App\Support\PaymentStatus;
use App\Services\Accounting\JournalPostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BankingController extends Controller
{
    public function __construct(private JournalPostingService $journalPostingService)
    {
        $this->middleware('permission:bank-account-list')->only('bankAccountsIndex');
        $this->middleware('permission:bank-account-create')->only('bankAccountStore');
        $this->middleware('permission:bank-account-edit')->only('bankAccountUpdate');
        $this->middleware('permission:bank-account-delete')->only('bankAccountDestroy');
        $this->middleware('permission:bank-transaction-list')->only('bankTransactionsIndex');
        $this->middleware('permission:bank-transaction-create')->only('bankTransactionStore');
        $this->middleware('permission:bank-transaction-edit')->only('bankTransactionUpdate');
        $this->middleware('permission:bank-transaction-delete')->only('bankTransactionDestroy');
    }

    public function bankAccountsIndex(BankAccountsDataTable $dataTable)
    {
        $glAccounts = AccountingAccount::query()->where('type', 'asset')->where('is_bank_account', true)->orderBy('code')->get();

        return $dataTable->render('backend.accounting.bank_accounts', compact('glAccounts'));
    }

    public function bankAccountStore(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bank_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:80'],
            'currency' => ['required', 'string', 'max:10'],
            'opening_balance' => ['required', 'numeric'],
            'gl_account_id' => ['required', 'exists:accounting_accounts,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $opening = round((float) $data['opening_balance'], 2);

        BankAccount::create([
            'name' => $data['name'],
            'bank_name' => $data['bank_name'] ?? null,
            'account_number' => $data['account_number'] ?? null,
            'currency' => $data['currency'] ?? 'GHS',
            'opening_balance' => $opening,
            'current_balance' => $opening,
            'gl_account_id' => (int) $data['gl_account_id'],
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return redirect()->route('accounting.setup.bank-accounts.index')->with([
            'message' => 'Bank account created successfully.',
            'alert-type' => 'success',
        ]);
    }

    public function bankAccountUpdate(Request $request, int $id)
    {
        $account = BankAccount::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bank_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:80'],
            'currency' => ['required', 'string', 'max:10'],
            'gl_account_id' => ['required', 'exists:accounting_accounts,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $account->update([
            'name' => $data['name'],
            'bank_name' => $data['bank_name'] ?? null,
            'account_number' => $data['account_number'] ?? null,
            'currency' => $data['currency'] ?? 'GHS',
            'gl_account_id' => (int) $data['gl_account_id'],
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return redirect()->route('accounting.setup.bank-accounts.index')->with([
            'message' => 'Bank account updated successfully.',
            'alert-type' => 'success',
        ]);
    }

    public function bankAccountDestroy(Request $request, int $id)
    {
        $account = BankAccount::findOrFail($id);

        $hasTransactions = BankTransaction::query()
            ->where('bank_account_id', $account->id)
            ->orWhere('counterparty_bank_account_id', $account->id)
            ->exists();

        $hasCapitalLoans = DB::table('capital_loan_entries')
            ->where('bank_account_id', $account->id)
            ->exists();

        if ($hasTransactions || $hasCapitalLoans) {
            $message = 'This bank account cannot be deleted because it has related entries.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            throw ValidationException::withMessages(['bank_account_id' => $message]);
        }

        $account->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Bank account deleted successfully.']);
        }

        return redirect()->route('accounting.setup.bank-accounts.index')->with([
            'message' => 'Bank account deleted successfully.',
            'alert-type' => 'success',
        ]);
    }

    public function bankTransactionsIndex(BankTransactionsDataTable $dataTable)
    {
        $bankAccounts = BankAccount::query()->where('is_active', true)->orderBy('name')->get();
        $supplierPayments = PurchasePayment::query()
            ->with(['supplier:id,name', 'purchase:id,purchase_no'])
            ->where('due_amount', '>', 0)
            ->orderByDesc('id')
            ->get();
        $cashInHandBalance = $this->cashInHandBalance();

        return $dataTable->render('backend.accounting.bank_transactions', compact('bankAccounts', 'supplierPayments', 'cashInHandBalance'));
    }

    public function bankTransactionStore(Request $request)
    {
        $data = $this->validateBankTransactionPayload($request);
        DB::transaction(fn () => $this->postBankTransaction($data));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Bank transaction posted successfully.',
                'cash_in_hand_balance' => $this->cashInHandBalance(),
            ]);
        }

        return redirect()->route('accounting.setup.bank-transactions.index')->with([
            'message' => 'Bank transaction posted successfully.',
            'alert-type' => 'success',
        ]);
    }

    public function bankTransactionUpdate(Request $request, int $id)
    {
        $target = BankTransaction::query()->findOrFail($id);
        if ((string) $target->type === 'transfer_in') {
            throw ValidationException::withMessages([
                'type' => 'Edit the transfer using the Transfer Out entry.',
            ]);
        }

        $data = $this->validateBankTransactionPayload($request);

        DB::transaction(function () use ($target, $data) {
            $group = BankTransaction::query()
                ->when($target->journal_id, function ($q) use ($target) {
                    $q->where('journal_id', $target->journal_id);
                }, function ($q) use ($target) {
                    $q->whereKey($target->id);
                })
                ->get(['id', 'bank_account_id', 'journal_id', 'source_type', 'source_id']);

            $affectedAccountIds = $group->pluck('bank_account_id')->filter()->unique()->values();
            $journalIds = $group->pluck('journal_id')->filter()->unique()->values();

            foreach ($group as $row) {
                $this->rollbackSupplierPaymentLink($row->source_type, $row->source_id);
            }

            BankTransaction::query()->whereIn('id', $group->pluck('id'))->delete();
            $this->deleteJournals($journalIds->all());

            foreach ($affectedAccountIds as $accountId) {
                $this->recalculateBankBalance((int) $accountId);
            }

            $this->postBankTransaction($data);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Bank transaction updated successfully.',
                'cash_in_hand_balance' => $this->cashInHandBalance(),
            ]);
        }

        return redirect()->route('accounting.setup.bank-transactions.index')->with([
            'message' => 'Bank transaction updated successfully.',
            'alert-type' => 'success',
        ]);
    }

    public function bankTransactionDestroy(Request $request, int $id)
    {
        $transaction = BankTransaction::query()->findOrFail($id);
        $deletedType = (string) $transaction->type;

        DB::transaction(function () use ($transaction) {
            $group = BankTransaction::query()
                ->when($transaction->journal_id, function ($q) use ($transaction) {
                    $q->where('journal_id', $transaction->journal_id);
                }, function ($q) use ($transaction) {
                    $q->whereKey($transaction->id);
                })
                ->get(['id', 'bank_account_id', 'journal_id', 'source_type', 'source_id']);

            $affectedAccountIds = $group->pluck('bank_account_id')->filter()->unique()->values();
            $journalIds = $group->pluck('journal_id')->filter()->unique()->values();

            foreach ($group as $row) {
                $this->rollbackSupplierPaymentLink($row->source_type, $row->source_id);
            }

            BankTransaction::query()->whereIn('id', $group->pluck('id'))->delete();
            $this->deleteJournals($journalIds->all());

            foreach ($affectedAccountIds as $accountId) {
                $this->recalculateBankBalance((int) $accountId);
            }
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $deletedType === 'deposit'
                    ? 'Bank deposit deleted and amount reversed to Cash In Hand.'
                    : 'Bank transaction deleted successfully.',
                'cash_in_hand_balance' => $this->cashInHandBalance(),
            ]);
        }

        return redirect()->route('accounting.setup.bank-transactions.index')->with([
            'message' => $deletedType === 'deposit'
                ? 'Bank deposit deleted and amount reversed to Cash In Hand.'
                : 'Bank transaction deleted successfully.',
            'alert-type' => 'success',
        ]);
    }

    private function buildJournalLinesForType(string $type, BankAccount $bankAccount, float $amount, ?int $counterpartyId, ?int $supplierId = null): array
    {
        $cashOnHand = $this->accountIdByCode('1000');
        $ar = $this->accountIdByCode('1100');
        $ap = $this->accountIdByCode('2000');
        $equity = $this->accountIdByCode('3000');
        $bankCharges = $this->accountIdByCode('5300');
        $interestIncome = $this->accountIdByCode('5400');

        $lines = [];
        $bankGl = (int) $bankAccount->gl_account_id;
        $balanceDelta = 0.0;
        $counterpartyDelta = null;

        switch ($type) {
            case 'deposit':
                $lines[] = ['account_id' => $bankGl, 'debit' => $amount, 'credit' => 0, 'description' => 'Bank deposit'];
                $lines[] = ['account_id' => $cashOnHand, 'debit' => 0, 'credit' => $amount, 'description' => 'Cash transferred to bank'];
                $balanceDelta = $amount;
                break;
            case 'withdrawal':
                $lines[] = ['account_id' => $cashOnHand, 'debit' => $amount, 'credit' => 0, 'description' => 'Cash withdrawal'];
                $lines[] = ['account_id' => $bankGl, 'debit' => 0, 'credit' => $amount, 'description' => 'Bank withdrawal'];
                $balanceDelta = -$amount;
                break;
            case 'transfer_out':
                $counterparty = BankAccount::findOrFail((int) $counterpartyId);
                if (!$counterparty->gl_account_id) {
                    throw ValidationException::withMessages([
                        'counterparty_bank_account_id' => 'Counterparty bank account has no linked GL account.',
                    ]);
                }
                $lines[] = ['account_id' => (int) $counterparty->gl_account_id, 'debit' => $amount, 'credit' => 0, 'description' => 'Transfer in to counterparty'];
                $lines[] = ['account_id' => $bankGl, 'debit' => 0, 'credit' => $amount, 'description' => 'Transfer out from bank'];
                $balanceDelta = -$amount;
                $counterpartyDelta = $amount;
                break;
            case 'bank_fee':
                $lines[] = ['account_id' => $bankCharges, 'debit' => $amount, 'credit' => 0, 'description' => 'Bank fee'];
                $lines[] = ['account_id' => $bankGl, 'debit' => 0, 'credit' => $amount, 'description' => 'Bank fee deduction'];
                $balanceDelta = -$amount;
                break;
            case 'interest_income':
                $lines[] = ['account_id' => $bankGl, 'debit' => $amount, 'credit' => 0, 'description' => 'Interest received'];
                $lines[] = ['account_id' => $interestIncome, 'debit' => 0, 'credit' => $amount, 'description' => 'Interest income'];
                $balanceDelta = $amount;
                break;
            case 'receipt':
                $lines[] = ['account_id' => $bankGl, 'debit' => $amount, 'credit' => 0, 'description' => 'Bank receipt'];
                $lines[] = ['account_id' => $ar, 'debit' => 0, 'credit' => $amount, 'description' => 'AR receipt adjustment'];
                $balanceDelta = $amount;
                break;
            case 'payment':
                $apLine = ['account_id' => $ap, 'debit' => $amount, 'credit' => 0, 'description' => 'AP payment'];
                if (!is_null($supplierId) && $supplierId > 0) {
                    $apLine['entity_type'] = 'supplier';
                    $apLine['entity_id'] = $supplierId;
                }
                $lines[] = $apLine;
                $lines[] = ['account_id' => $bankGl, 'debit' => 0, 'credit' => $amount, 'description' => 'Bank payment'];
                $balanceDelta = -$amount;
                break;
            case 'reconciliation_adjustment':
                $lines[] = ['account_id' => $bankGl, 'debit' => $amount, 'credit' => 0, 'description' => 'Reconciliation adjustment'];
                $lines[] = ['account_id' => $equity, 'debit' => 0, 'credit' => $amount, 'description' => 'Reconciliation balancing entry'];
                $balanceDelta = $amount;
                break;
            default:
                throw ValidationException::withMessages([
                    'type' => 'Unsupported transaction type.',
                ]);
        }

        return [$lines, $balanceDelta, $counterpartyDelta];
    }

    private function accountIdByCode(string $code): int
    {
        $id = AccountingAccount::query()->where('code', $code)->value('id');

        if (!$id) {
            throw ValidationException::withMessages([
                'type' => 'Missing required chart of accounts code: ' . $code,
            ]);
        }

        return (int) $id;
    }

    private function validateBankTransactionPayload(Request $request): array
    {
        $data = $request->validate([
            'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            'transaction_date' => ['required', 'date'],
            'type' => ['required', 'in:deposit,withdrawal,transfer_out,bank_fee,interest_income,receipt,payment,reconciliation_adjustment'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'purchase_payment_id' => ['nullable', 'exists:purchase_payments,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'reference_no' => ['nullable', 'string', 'max:120'],
            'counterparty_bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
        ]);

        if ((string) $data['type'] === 'transfer_out' && empty($data['counterparty_bank_account_id'])) {
            throw ValidationException::withMessages([
                'counterparty_bank_account_id' => 'Counterparty bank account is required for transfer out.',
            ]);
        }

        if ((string) $data['type'] === 'transfer_out' && (int) $data['counterparty_bank_account_id'] === (int) $data['bank_account_id']) {
            throw ValidationException::withMessages([
                'counterparty_bank_account_id' => 'Source and counterparty account cannot be the same.',
            ]);
        }

        if ((string) $data['type'] === 'payment' && empty($data['purchase_payment_id'])) {
            throw ValidationException::withMessages([
                'purchase_payment_id' => 'Select an outstanding supplier invoice for bank payment.',
            ]);
        }

        return $data;
    }

    private function postBankTransaction(array $data): void
    {
        $bankAccount = BankAccount::findOrFail((int) $data['bank_account_id']);
        if (!$bankAccount->gl_account_id) {
            throw ValidationException::withMessages([
                'bank_account_id' => 'Selected bank account has no linked GL account.',
            ]);
        }

        $amount = round((float) $data['amount'], 2);
        $linkedPurchasePayment = null;
        $linkedSupplierId = null;

        if ((string) $data['type'] === 'payment' && !empty($data['purchase_payment_id'])) {
            $linkedPurchasePayment = PurchasePayment::query()
                ->with('purchase:id,purchase_no')
                ->lockForUpdate()
                ->findOrFail((int) $data['purchase_payment_id']);

            $currentDue = round((float) $linkedPurchasePayment->due_amount, 2);
            if ($currentDue <= 0) {
                throw ValidationException::withMessages([
                    'purchase_payment_id' => 'Selected supplier invoice is already fully settled.',
                ]);
            }

            if ($amount > $currentDue) {
                throw ValidationException::withMessages([
                    'amount' => 'Bank payment amount cannot exceed selected supplier due amount.',
                ]);
            }

            $linkedSupplierId = (int) ($linkedPurchasePayment->supplier_id ?? 0) ?: null;

            if (empty($data['description'])) {
                $purchaseNo = optional($linkedPurchasePayment->purchase)->purchase_no;
                if ($purchaseNo) {
                    $data['description'] = 'Supplier bank payment for purchase #' . $purchaseNo;
                }
            }
        }

        [$lines, $balanceDelta, $counterpartyDelta] = $this->buildJournalLinesForType(
            $data['type'],
            $bankAccount,
            $amount,
            $data['counterparty_bank_account_id'] ?? null,
            $linkedSupplierId
        );

        $journal = $this->journalPostingService->postJournal(
            sourceType: 'bank_transaction',
            sourceId: null,
            referenceNo: (string) ($data['reference_no'] ?? ''),
            description: (string) ($data['description'] ?? ('Bank transaction: ' . $data['type'])),
            postingDate: (string) $data['transaction_date'],
            lines: $lines,
            postedBy: auth()->id()
        );

        $txn = BankTransaction::create([
            'bank_account_id' => $bankAccount->id,
            'transaction_date' => $data['transaction_date'],
            'type' => $data['type'],
            'amount' => $amount,
            'description' => $data['description'] ?? null,
            'reference_no' => $data['reference_no'] ?? null,
            'counterparty_bank_account_id' => $data['counterparty_bank_account_id'] ?? null,
            'journal_id' => $journal->id,
            'created_by' => auth()->id(),
        ]);

        if ($linkedPurchasePayment) {
            $newPaidAmount = round((float) $linkedPurchasePayment->paid_amount + $amount, 2);
            $newDueAmount = round(max(0, (float) $linkedPurchasePayment->total_amount - $newPaidAmount), 2);

            $linkedPurchasePayment->paid_amount = $newPaidAmount;
            $linkedPurchasePayment->due_amount = $newDueAmount;
            $linkedPurchasePayment->paid_status = $newDueAmount <= 0
                ? PaymentStatus::FULL_PAYMENT
                : PaymentStatus::PARTIAL_PAYMENT;
            $linkedPurchasePayment->bank_account_id = $bankAccount->id;
            $linkedPurchasePayment->save();

            $paymentDetail = PurchasePaymentDetail::create([
                'purchase_id' => $linkedPurchasePayment->purchase_id,
                'date' => $data['transaction_date'],
                'current_paid_amount' => $amount,
                'updated_by' => auth()->id(),
                'bank_account_id' => $bankAccount->id,
                'accounting_status' => 'posted',
                'accounting_journal_id' => $journal->id,
                'accounting_posted_at' => now(),
            ]);

            $txn->source_type = 'supplier_payment';
            $txn->source_id = $paymentDetail->id;
            $txn->save();
        }

        $bankAccount->current_balance = round((float) $bankAccount->current_balance + $balanceDelta, 2);
        $bankAccount->save();

        if (!is_null($counterpartyDelta) && !empty($data['counterparty_bank_account_id'])) {
            $counterparty = BankAccount::find((int) $data['counterparty_bank_account_id']);
            if ($counterparty) {
                $counterparty->current_balance = round((float) $counterparty->current_balance + $counterpartyDelta, 2);
                $counterparty->save();

                BankTransaction::create([
                    'bank_account_id' => $counterparty->id,
                    'transaction_date' => $data['transaction_date'],
                    'type' => 'transfer_in',
                    'amount' => $amount,
                    'description' => 'Auto counterpart for transfer out #' . $txn->id,
                    'reference_no' => $data['reference_no'] ?? null,
                    'counterparty_bank_account_id' => $bankAccount->id,
                    'journal_id' => $journal->id,
                    'created_by' => auth()->id(),
                ]);
            }
        }
    }

    private function cashInHandBalance(): float
    {
        $cashAccountId = AccountingAccount::query()->where('code', '1000')->value('id');

        if (!$cashAccountId) {
            return 0.0;
        }

        $row = DB::table('accounting_journal_lines as l')
            ->join('accounting_journals as j', 'j.id', '=', 'l.journal_id')
            ->where('l.account_id', (int) $cashAccountId)
            ->where('j.status', 'posted')
            ->selectRaw('ROUND(COALESCE(SUM(l.debit),0),2) as total_debit, ROUND(COALESCE(SUM(l.credit),0),2) as total_credit')
            ->first();

        return round((float) ($row->total_debit ?? 0) - (float) ($row->total_credit ?? 0), 2);
    }

    private function deleteJournals(array $journalIds): void
    {
        if (empty($journalIds)) {
            return;
        }

        DB::table('accounting_journal_lines')->whereIn('journal_id', $journalIds)->delete();
        DB::table('accounting_journals')->whereIn('id', $journalIds)->delete();
    }

    private function recalculateBankBalance(int $bankAccountId): void
    {
        $account = BankAccount::query()->find($bankAccountId);
        if (!$account) {
            return;
        }

        $transactionDelta = BankTransaction::query()
            ->where('bank_account_id', $bankAccountId)
            ->selectRaw("COALESCE(SUM(CASE
                WHEN type IN ('deposit', 'transfer_in', 'interest_income', 'receipt', 'reconciliation_adjustment') THEN amount
                WHEN type IN ('withdrawal', 'transfer_out', 'bank_fee', 'payment') THEN -amount
                ELSE 0 END), 0) as delta")
            ->value('delta');

        $capitalDelta = DB::table('capital_loan_entries')
            ->where('bank_account_id', $bankAccountId)
            ->where('receive_into', 'bank')
            ->selectRaw('COALESCE(SUM(amount), 0) as delta')
            ->value('delta');

        $openingBalance = (float) ($account->opening_balance ?? 0);
        $newBalance = round($openingBalance + (float) $transactionDelta + (float) $capitalDelta, 2);

        $account->current_balance = $newBalance;
        $account->save();
    }

    private function rollbackSupplierPaymentLink(?string $sourceType, $sourceId): void
    {
        if ((string) $sourceType !== 'supplier_payment' || empty($sourceId)) {
            return;
        }

        $detail = PurchasePaymentDetail::query()->lockForUpdate()->find((int) $sourceId);
        if (!$detail) {
            return;
        }

        $payment = PurchasePayment::query()->lockForUpdate()->where('purchase_id', $detail->purchase_id)->first();
        if ($payment) {
            $rollbackAmount = round((float) $detail->current_paid_amount, 2);
            $newPaidAmount = round(max(0, (float) $payment->paid_amount - $rollbackAmount), 2);
            $newDueAmount = round(max(0, (float) $payment->total_amount - $newPaidAmount), 2);

            $payment->paid_amount = $newPaidAmount;
            $payment->due_amount = $newDueAmount;
            $payment->paid_status = $newDueAmount <= 0
                ? PaymentStatus::FULL_PAYMENT
                : ($newPaidAmount > 0 ? PaymentStatus::PARTIAL_PAYMENT : PaymentStatus::FULL_CREDIT);
            $payment->save();
        }

        $detail->delete();
    }
}
