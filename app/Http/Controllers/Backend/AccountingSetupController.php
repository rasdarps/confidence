<?php

namespace App\Http\Controllers\Backend;

use App\DataTables\Admin\Accounting\AccountingAccountsDataTable;
use App\DataTables\Admin\Accounting\AccountingFiscalPeriodsDataTable;
use App\Http\Controllers\Controller;
use App\Models\AccountingAccount;
use App\Models\AccountingFiscalPeriod;
use Illuminate\Http\Request;

class AccountingSetupController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:accounting-coa-list')->only('coaIndex');
        $this->middleware('permission:accounting-coa-create')->only('coaStore');
        $this->middleware('permission:accounting-coa-edit')->only('coaUpdate');
        $this->middleware('permission:accounting-fiscal-period-list')->only('fiscalPeriodsIndex');
        $this->middleware('permission:accounting-fiscal-period-close')->only('fiscalPeriodClose');
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
}
