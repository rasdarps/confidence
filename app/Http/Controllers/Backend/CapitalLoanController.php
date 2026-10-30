<?php

namespace App\Http\Controllers\Backend;

use App\DataTables\Admin\Accounting\CapitalLoanEntriesDataTable;
use App\Http\Controllers\Controller;
use App\Models\AccountingAccount;
use App\Models\BankAccount;
use App\Models\CapitalLoanEntry;
use App\Models\Outlet;
use App\Services\Accounting\JournalPostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CapitalLoanController extends Controller
{
    public function __construct(private JournalPostingService $journalPostingService)
    {
        $this->middleware('permission:capital-loan-list')->only('index');
        $this->middleware('permission:capital-loan-create')->only('store');
        $this->middleware('permission:capital-loan-edit')->only('update');
        $this->middleware('permission:capital-loan-delete')->only('destroy');
    }

    public function index(CapitalLoanEntriesDataTable $dataTable)
    {
        $bankAccounts = BankAccount::query()->where('is_active', true)->orderBy('name')->get();
        $outlets = Outlet::active()
            ->orderByDesc('is_central')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'type', 'is_central']);

        $defaultOutletId = (int) (Outlet::centralWarehouseId() ?: auth()->user()?->outlet_id ?: ($outlets->first()?->id ?? 0));

        return $dataTable->render('backend.accounting.capital_loans', compact('bankAccounts', 'outlets', 'defaultOutletId'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'outlet_id' => ['nullable', 'exists:outlets,id'],
            'transaction_date' => ['required', 'date'],
            'entry_type' => ['required', 'in:owner_capital,bank_loan,individual_loan'],
            'receive_into' => ['required', 'in:cash,bank'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'lender_name' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference_no' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        if ($data['receive_into'] === 'bank' && empty($data['bank_account_id'])) {
            throw ValidationException::withMessages([
                'bank_account_id' => 'Please select a bank account when receive-into is Bank.',
            ]);
        }

        if (in_array($data['entry_type'], ['bank_loan', 'individual_loan'], true) && empty($data['lender_name'])) {
            throw ValidationException::withMessages([
                'lender_name' => 'Lender/Source is required for loan entries.',
            ]);
        }

        $amount = round((float) $data['amount'], 2);

        DB::transaction(function () use ($data, $amount) {
            $bankAccount = null;
            $debitAccountId = $this->accountIdByCode('1000');

            if ($data['receive_into'] === 'bank') {
                $bankAccount = BankAccount::findOrFail((int) $data['bank_account_id']);
                if (!$bankAccount->gl_account_id) {
                    throw ValidationException::withMessages([
                        'bank_account_id' => 'Selected bank account has no linked GL account.',
                    ]);
                }
                $debitAccountId = (int) $bankAccount->gl_account_id;
            }

            $creditAccountId = $this->creditAccountIdByType((string) $data['entry_type']);

            $entry = CapitalLoanEntry::create([
                'transaction_date' => $data['transaction_date'],
                'entry_type' => $data['entry_type'],
                'receive_into' => $data['receive_into'],
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'lender_name' => $data['lender_name'] ?? null,
                'amount' => $amount,
                'reference_no' => $data['reference_no'] ?? null,
                'description' => $data['description'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $referenceNo = (string) ($data['reference_no'] ?? ('CAP-LOAN-' . $entry->id));
            $entryTypeLabel = match ((string) $data['entry_type']) {
                'owner_capital' => 'Owner Capital',
                'bank_loan' => 'Bank Loan',
                'individual_loan' => 'Individual Loan',
                default => 'Capital/Loan',
            };

            $resolvedOutletId = !empty($data['outlet_id'])
                ? (int) $data['outlet_id']
                : (int) (Outlet::centralWarehouseId() ?: auth()->user()?->outlet_id);

            $journal = $this->journalPostingService->postJournal(
                sourceType: 'capital_loan_entry',
                sourceId: (int) $entry->id,
                referenceNo: $referenceNo,
                description: $entryTypeLabel . ' posting',
                postingDate: (string) $data['transaction_date'],
                lines: [
                    [
                        'account_id' => $debitAccountId,
                        'debit' => $amount,
                        'credit' => 0,
                        'description' => $entryTypeLabel . ' funds received',
                    ],
                    [
                        'account_id' => $creditAccountId,
                        'debit' => 0,
                        'credit' => $amount,
                        'description' => $entryTypeLabel . ' source recognition',
                    ],
                ],
                postedBy: auth()->id(),
                outletId: $resolvedOutletId > 0 ? $resolvedOutletId : null
            );

            $entry->update([
                'accounting_status' => 'posted',
                'accounting_journal_id' => $journal->id,
                'accounting_posted_at' => now(),
            ]);

            if ($bankAccount) {
                $bankAccount->current_balance = round((float) $bankAccount->current_balance + $amount, 2);
                $bankAccount->save();
            }
        });

        return redirect()->route('accounting.setup.capital-loans.index')->with([
            'message' => 'Capital/Loan entry posted successfully.',
            'alert-type' => 'success',
        ]);
    }

    public function update(Request $request, int $id)
    {
        $entry = CapitalLoanEntry::query()->findOrFail($id);

        $data = $request->validate([
            'outlet_id' => ['nullable', 'exists:outlets,id'],
            'transaction_date' => ['required', 'date'],
            'entry_type' => ['required', 'in:owner_capital,bank_loan,individual_loan'],
            'receive_into' => ['required', 'in:cash,bank'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'lender_name' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference_no' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        if ($data['receive_into'] === 'bank' && empty($data['bank_account_id'])) {
            throw ValidationException::withMessages([
                'bank_account_id' => 'Please select a bank account when receive-into is Bank.',
            ]);
        }

        if (in_array($data['entry_type'], ['bank_loan', 'individual_loan'], true) && empty($data['lender_name'])) {
            throw ValidationException::withMessages([
                'lender_name' => 'Lender/Source is required for loan entries.',
            ]);
        }

        $amount = round((float) $data['amount'], 2);

        DB::transaction(function () use ($entry, $data, $amount) {
            $oldBankAccountId = $entry->bank_account_id ? (int) $entry->bank_account_id : null;
            $oldJournalId = $entry->accounting_journal_id ? (int) $entry->accounting_journal_id : null;

            if ($oldJournalId) {
                DB::table('accounting_journal_lines')->where('journal_id', $oldJournalId)->delete();
                DB::table('accounting_journals')->where('id', $oldJournalId)->delete();
            }

            $bankAccount = null;
            $debitAccountId = $this->accountIdByCode('1000');
            if ($data['receive_into'] === 'bank') {
                $bankAccount = BankAccount::findOrFail((int) $data['bank_account_id']);
                if (!$bankAccount->gl_account_id) {
                    throw ValidationException::withMessages([
                        'bank_account_id' => 'Selected bank account has no linked GL account.',
                    ]);
                }

                $debitAccountId = (int) $bankAccount->gl_account_id;
            }

            $creditAccountId = $this->creditAccountIdByType((string) $data['entry_type']);
            $entryTypeLabel = match ((string) $data['entry_type']) {
                'owner_capital' => 'Owner Capital',
                'bank_loan' => 'Bank Loan',
                'individual_loan' => 'Individual Loan',
                default => 'Capital/Loan',
            };

            $entry->update([
                'transaction_date' => $data['transaction_date'],
                'entry_type' => $data['entry_type'],
                'receive_into' => $data['receive_into'],
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'lender_name' => $data['lender_name'] ?? null,
                'amount' => $amount,
                'reference_no' => $data['reference_no'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            $resolvedOutletId = !empty($data['outlet_id'])
                ? (int) $data['outlet_id']
                : (int) (Outlet::centralWarehouseId() ?: auth()->user()?->outlet_id);

            $referenceNo = (string) ($data['reference_no'] ?? ('CAP-LOAN-' . $entry->id));
            $journal = $this->journalPostingService->postJournal(
                sourceType: 'capital_loan_entry',
                sourceId: (int) $entry->id,
                referenceNo: $referenceNo,
                description: $entryTypeLabel . ' posting',
                postingDate: (string) $data['transaction_date'],
                lines: [
                    [
                        'account_id' => $debitAccountId,
                        'debit' => $amount,
                        'credit' => 0,
                        'description' => $entryTypeLabel . ' funds received',
                    ],
                    [
                        'account_id' => $creditAccountId,
                        'debit' => 0,
                        'credit' => $amount,
                        'description' => $entryTypeLabel . ' source recognition',
                    ],
                ],
                postedBy: auth()->id(),
                outletId: $resolvedOutletId > 0 ? $resolvedOutletId : null
            );

            $entry->update([
                'accounting_status' => 'posted',
                'accounting_journal_id' => $journal->id,
                'accounting_posted_at' => now(),
            ]);

            $newBankAccountId = $entry->bank_account_id ? (int) $entry->bank_account_id : null;
            $recalcIds = collect([$oldBankAccountId, $newBankAccountId])->filter()->unique()->values();
            foreach ($recalcIds as $bankAccountId) {
                $this->recalculateBankBalance((int) $bankAccountId);
            }
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Capital/Loan entry updated successfully.']);
        }

        return redirect()->route('accounting.setup.capital-loans.index')->with([
            'message' => 'Capital/Loan entry updated successfully.',
            'alert-type' => 'success',
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        $entry = CapitalLoanEntry::query()->findOrFail($id);

        DB::transaction(function () use ($entry) {
            $bankAccountId = $entry->bank_account_id ? (int) $entry->bank_account_id : null;
            $journalId = $entry->accounting_journal_id ? (int) $entry->accounting_journal_id : null;

            $entry->delete();

            if ($journalId) {
                DB::table('accounting_journal_lines')->where('journal_id', $journalId)->delete();
                DB::table('accounting_journals')->where('id', $journalId)->delete();
            }

            if ($bankAccountId) {
                $this->recalculateBankBalance($bankAccountId);
            }
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Capital/Loan entry deleted successfully.']);
        }

        return redirect()->route('accounting.setup.capital-loans.index')->with([
            'message' => 'Capital/Loan entry deleted successfully.',
            'alert-type' => 'success',
        ]);
    }

    private function creditAccountIdByType(string $entryType): int
    {
        return match ($entryType) {
            'owner_capital' => $this->accountIdByCode('3000'),
            'bank_loan' => $this->accountIdByCode('2300'),
            'individual_loan' => $this->accountIdByCode('2310'),
            default => throw ValidationException::withMessages(['entry_type' => 'Unsupported capital/loan type.']),
        };
    }

    private function accountIdByCode(string $code): int
    {
        $id = AccountingAccount::query()->where('code', $code)->value('id');

        if (!$id) {
            throw ValidationException::withMessages([
                'entry_type' => 'Missing required chart of accounts code: ' . $code,
            ]);
        }

        return (int) $id;
    }

    private function recalculateBankBalance(int $bankAccountId): void
    {
        $account = BankAccount::query()->find($bankAccountId);
        if (!$account) {
            return;
        }

        $transactionDelta = DB::table('bank_transactions')
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

        $account->current_balance = round((float) $account->opening_balance + (float) $transactionDelta + (float) $capitalDelta, 2);
        $account->save();
    }
}
