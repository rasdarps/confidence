<?php

namespace App\Http\Controllers\Backend;

use App\DataTables\Admin\Accounting\AccountingAccountsDataTable;
use App\DataTables\Admin\Accounting\AccountingFiscalPeriodsDataTable;
use App\Http\Controllers\Controller;
use App\Models\AccountingAccount;
use App\Models\AccountingFiscalPeriod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AccountingSetupController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:accounting-coa-list')->only('coaIndex');
        $this->middleware('permission:accounting-coa-create')->only('coaStore');
        $this->middleware('permission:accounting-coa-edit')->only('coaUpdate');
        $this->middleware('permission:accounting-fiscal-period-list')->only('fiscalPeriodsIndex');
        $this->middleware('permission:accounting-fiscal-period-create|accounting-fiscal-period-close')->only('fiscalPeriodStore');
        $this->middleware('permission:accounting-fiscal-period-edit|accounting-fiscal-period-close')->only('fiscalPeriodUpdate');
        $this->middleware('permission:accounting-fiscal-period-close')->only(['fiscalPeriodClose', 'fiscalPeriodReopen']);
    }

    public function coaIndex(AccountingAccountsDataTable $dataTable)
    {
        return $dataTable->render('backend.accounting.coa_index');
    }

    public function coaStore(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:accounting_accounts,code'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:asset,liability,equity,income,expense'],
            'is_bank_account' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        AccountingAccount::create([
            'code' => $data['code'],
            'name' => $data['name'],
            'type' => $data['type'],
            'is_bank_account' => (bool) ($data['is_bank_account'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return redirect()->route('accounting.setup.coa.index')->with([
            'message' => 'Account created successfully.',
            'alert-type' => 'success',
        ]);
    }

    public function coaUpdate(Request $request, int $id)
    {
        $account = AccountingAccount::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_bank_account' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $account->update([
            'name' => $data['name'],
            'is_bank_account' => (bool) ($data['is_bank_account'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return redirect()->route('accounting.setup.coa.index')->with([
            'message' => 'Account updated successfully.',
            'alert-type' => 'success',
        ]);
    }

    public function fiscalPeriodsIndex(AccountingFiscalPeriodsDataTable $dataTable)
    {
        return $dataTable->render('backend.accounting.fiscal_periods');
    }

    public function fiscalPeriodStore(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $overlap = AccountingFiscalPeriod::query()
            ->where(function ($query) use ($data) {
                $query->whereDate('start_date', '<=', $data['end_date'])
                      ->whereDate('end_date', '>=', $data['start_date']);
            })
            ->first();

        if ($overlap) {
            $msg = sprintf(
                'Date range overlaps with existing fiscal period "%s" (%s to %s).',
                $overlap->name,
                $overlap->start_date ? Carbon::parse($overlap->start_date)->format('Y-m-d') : '',
                $overlap->end_date ? Carbon::parse($overlap->end_date)->format('Y-m-d') : ''
            );

            if ($request->expectsJson()) {
                throw ValidationException::withMessages(['start_date' => $msg]);
            }

            return redirect()->back()->withInput()->withErrors(['start_date' => $msg]);
        }

        AccountingFiscalPeriod::create([
            'name' => $data['name'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'is_closed' => false,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Fiscal period created successfully.']);
        }

        return redirect()->route('accounting.setup.fiscal-periods.index')->with([
            'message' => 'Fiscal period created successfully.',
            'alert-type' => 'success',
        ]);
    }

    public function fiscalPeriodUpdate(Request $request, int $id)
    {
        $period = AccountingFiscalPeriod::findOrFail($id);

        if ($period->is_closed) {
            $msg = 'Closed fiscal periods cannot be edited. Please reopen the period first.';
            if ($request->expectsJson()) {
                throw ValidationException::withMessages(['name' => $msg]);
            }
            return redirect()->back()->withInput()->withErrors(['name' => $msg]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $overlap = AccountingFiscalPeriod::query()
            ->where('id', '!=', $period->id)
            ->where(function ($query) use ($data) {
                $query->whereDate('start_date', '<=', $data['end_date'])
                      ->whereDate('end_date', '>=', $data['start_date']);
            })
            ->first();

        if ($overlap) {
            $msg = sprintf(
                'Date range overlaps with existing fiscal period "%s" (%s to %s).',
                $overlap->name,
                $overlap->start_date ? Carbon::parse($overlap->start_date)->format('Y-m-d') : '',
                $overlap->end_date ? Carbon::parse($overlap->end_date)->format('Y-m-d') : ''
            );

            if ($request->expectsJson()) {
                throw ValidationException::withMessages(['start_date' => $msg]);
            }

            return redirect()->back()->withInput()->withErrors(['start_date' => $msg]);
        }

        $period->update([
            'name' => $data['name'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Fiscal period updated successfully.']);
        }

        return redirect()->route('accounting.setup.fiscal-periods.index')->with([
            'message' => 'Fiscal period updated successfully.',
            'alert-type' => 'success',
        ]);
    }

    public function fiscalPeriodClose(int $id)
    {
        $period = AccountingFiscalPeriod::findOrFail($id);

        if ($period->is_closed) {
            return redirect()->route('accounting.setup.fiscal-periods.index')->with([
                'message' => 'Fiscal period is already closed.',
                'alert-type' => 'info',
            ]);
        }

        $period->update([
            'is_closed' => true,
            'closed_at' => now(),
            'closed_by' => auth()->id(),
        ]);

        return redirect()->route('accounting.setup.fiscal-periods.index')->with([
            'message' => 'Fiscal period closed successfully.',
            'alert-type' => 'success',
        ]);
    }

    public function fiscalPeriodReopen(int $id)
    {
        $period = AccountingFiscalPeriod::findOrFail($id);

        if (!$period->is_closed) {
            return redirect()->route('accounting.setup.fiscal-periods.index')->with([
                'message' => 'Fiscal period is already open.',
                'alert-type' => 'info',
            ]);
        }

        $period->update([
            'is_closed' => false,
            'closed_at' => null,
            'closed_by' => null,
        ]);

        return redirect()->route('accounting.setup.fiscal-periods.index')->with([
            'message' => 'Fiscal period reopened successfully.',
            'alert-type' => 'success',
        ]);
    }
}
