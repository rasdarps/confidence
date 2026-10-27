<?php

namespace App\Http\Controllers\Backend;

use App\DataTables\Admin\Settings\CashReconciliationDataTable;
use App\Http\Controllers\Controller;
use App\Models\CashReconciliation;
use App\Models\ExpenseVoucherItem;
use App\Models\Payment;
use App\Services\Accounting\AccountingIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class CashReconciliationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:cash-reconciliation-list|cash-reconciliation-show', ['only' => ['index']]);
        $this->middleware('permission:cash-reconciliation-create', ['only' => ['create', 'store', 'expected']]);
        $this->middleware('permission:cash-reconciliation-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:cash-reconciliation-show', ['only' => ['show']]);
        $this->middleware('permission:cash-reconciliation-approve', ['only' => ['approve']]);
        $this->middleware('permission:cash-reconciliation-reverse-approval', ['only' => ['reverseApproval']]);
    }

    public function index(CashReconciliationDataTable $dataTable)
    {
        $currentUser = Auth::user();
        $viewerIsSuperAdmin = $currentUser && strtolower((string) ($currentUser->user_type ?? '')) === 'super admin';
        $viewerCanSeeAllCashiers = $currentUser
            && ($viewerIsSuperAdmin || $currentUser->hasRole('Manager') || $currentUser->hasRole('Supervisor'));

        $isRestrictedToOwnReconciles = ! $viewerCanSeeAllCashiers;

        return $dataTable->render('backend.cash_reconciliation.index', compact('isRestrictedToOwnReconciles'));
    }

    public function create()
    {
        $businessDate = now()->format('Y-m-d');
        $openingFloat = 0;

        $expected = $this->computeExpectedForUser(Auth::id(), $businessDate, $openingFloat);

        return view('backend.cash_reconciliation.create', compact('businessDate', 'openingFloat', 'expected'));
    }

    public function edit($routeId)
    {
        try {
            $decryptedId = $routeId;

            try {
                $decryptedId = Crypt::decryptString((string) $routeId);
            } catch (\Throwable $e) {
                // Keep the original route value when it is already numeric.
            }

            $reconciliation = CashReconciliation::with(['user', 'createdBy', 'approvedBy', 'reversedBy'])
                ->find($decryptedId);

            if (! $reconciliation && is_numeric($routeId)) {
                $reconciliation = CashReconciliation::with(['user', 'createdBy', 'approvedBy', 'reversedBy'])
                    ->find((int) $routeId);
            }

            if (! $reconciliation) {
                return redirect()->route('cash-reconciliations.index')->with('error', 'Cash reconciliation not found.');
            }

            if ($reconciliation->status === 'approved') {
                return redirect()->route('cash-reconciliations.show', $reconciliation->id)
                    ->with('error', 'Approved reconciliation is locked. Reverse approval before editing.');
            }

            $expected = $this->computeExpectedForUser(
                (int) $reconciliation->user_id,
                (string) $reconciliation->business_date->format('Y-m-d'),
                (float) $reconciliation->opening_float
            );

            return view('backend.cash_reconciliation.edit', compact('reconciliation', 'expected'));
        } catch (\Throwable $e) {
            return redirect()->route('cash-reconciliations.index')->with('error', 'Invalid reconciliation ID.');
        }
    }

    public function expected(Request $request)
    {
        $validated = $request->validate([
            'business_date' => 'required|date',
            'opening_float' => 'nullable|numeric|min:0',
        ]);

        $openingFloat = (float) ($validated['opening_float'] ?? 0);
        $expected = $this->computeExpectedForUser(Auth::id(), $validated['business_date'], $openingFloat);

        return response()->json($expected);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'business_date' => 'required|date',
            'opening_float' => 'required|numeric|min:0',
            'physical_counted_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $userId = Auth::id();

        $existing = CashReconciliation::where('business_date', $validated['business_date'])
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            return redirect()->back()->withInput()->with('error', 'A reconciliation already exists for this cashier and date. Please edit the existing record if it has been reopened.');
        }

        $openingFloat = (float) $validated['opening_float'];
        $physicalCash = (float) $validated['physical_counted_cash'];
        $expected = $this->computeExpectedForUser($userId, $validated['business_date'], $openingFloat);

        $variance = round($physicalCash - $expected['expected_closing_cash'], 2);
        $varianceType = 'balanced';

        if ($variance > 0) {
            $varianceType = 'overage';
        } elseif ($variance < 0) {
            $varianceType = 'shortage';
        }

        $outletId = Auth::user()->outlet_id ?? \App\Models\Outlet::where('is_central', true)->value('id') ?? 1;
        CashReconciliation::create([
            'outlet_id' => $outletId,
            'business_date' => $validated['business_date'],
            'user_id' => $userId,
            'opening_float' => $openingFloat,
            'expected_cash_sales' => $expected['expected_cash_sales'],
            'expected_cash_expenses' => $expected['expected_cash_expenses'],
            'expected_closing_cash' => $expected['expected_closing_cash'],
            'physical_counted_cash' => $physicalCash,
            'variance' => $variance,
            'variance_type' => $varianceType,
            'status' => 'submitted',
            'notes' => $validated['notes'] ?? null,
            'created_by' => $userId,
        ]);

        return redirect()->route('cash-reconciliations.index')->with('success', 'Daily cash reconciliation submitted successfully.');
    }

    public function show($routeId)
    {
        try {
            $decryptedId = $routeId;

            try {
                $decryptedId = Crypt::decryptString((string) $routeId);
            } catch (\Throwable $e) {
                // Keep the original route value when it is already numeric.
            }

            $reconciliation = CashReconciliation::with(['user', 'createdBy', 'approvedBy', 'reversedBy'])
                ->find($decryptedId);

            if (! $reconciliation && is_numeric($routeId)) {
                $reconciliation = CashReconciliation::with(['user', 'createdBy', 'approvedBy', 'reversedBy'])
                    ->find((int) $routeId);
            }

            if (! $reconciliation) {
                return redirect()->route('cash-reconciliations.index')->with('error', 'Cash reconciliation not found.');
            }

            return view('backend.cash_reconciliation.show', compact('reconciliation'));
        } catch (\Throwable $e) {
            return redirect()->route('cash-reconciliations.index')->with('error', 'Invalid reconciliation ID.');
        }
    }

    public function update(Request $request, $routeId)
    {
        try {
            $reconciliation = $this->findReconciliation($routeId);

            if (! $reconciliation) {
                return redirect()->route('cash-reconciliations.index')->with('error', 'Cash reconciliation not found.');
            }

            if ($reconciliation->status === 'approved') {
                return redirect()->route('cash-reconciliations.show', $reconciliation->id)
                    ->with('error', 'Approved reconciliation is locked. Reverse approval before editing.');
            }

            $validated = $request->validate([
                'business_date' => 'required|date',
                'opening_float' => 'required|numeric|min:0',
                'physical_counted_cash' => 'required|numeric|min:0',
                'notes' => 'nullable|string|max:1000',
            ]);

            $duplicateExists = CashReconciliation::where('business_date', $validated['business_date'])
                ->where('user_id', $reconciliation->user_id)
                ->where('id', '!=', $reconciliation->id)
                ->exists();

            if ($duplicateExists) {
                return redirect()->back()->withInput()->with('error', 'Another reconciliation already exists for this cashier and date.');
            }

            $expected = $this->computeExpectedForUser((int) $reconciliation->user_id, $validated['business_date'], (float) $validated['opening_float']);
            $variance = round((float) $validated['physical_counted_cash'] - $expected['expected_closing_cash'], 2);

            $varianceType = 'balanced';
            if ($variance > 0) {
                $varianceType = 'overage';
            } elseif ($variance < 0) {
                $varianceType = 'shortage';
            }

            $reconciliation->update([
                'business_date' => $validated['business_date'],
                'opening_float' => (float) $validated['opening_float'],
                'expected_cash_sales' => $expected['expected_cash_sales'],
                'expected_cash_expenses' => $expected['expected_cash_expenses'],
                'expected_closing_cash' => $expected['expected_closing_cash'],
                'physical_counted_cash' => (float) $validated['physical_counted_cash'],
                'variance' => $variance,
                'variance_type' => $varianceType,
                'status' => 'submitted',
                'notes' => $validated['notes'] ?? null,
                'updated_by' => Auth::id(),
            ]);

            return redirect()->route('cash-reconciliations.index')->with('success', 'Cash reconciliation updated and resubmitted successfully.');
        } catch (\Throwable $e) {
            return redirect()->route('cash-reconciliations.index')->with('error', 'Invalid reconciliation ID.');
        }
    }

    public function approve(Request $request, $routeId)
    {
        try {
            $reconciliation = $this->findReconciliation($routeId);

            if (! $reconciliation) {
                return redirect()->route('cash-reconciliations.index')->with('error', 'Cash reconciliation not found.');
            }

            if ($reconciliation->status === 'approved') {
                return redirect()->back()->with('error', 'Reconciliation is already approved.');
            }

            DB::transaction(function () use ($reconciliation) {
                $reconciliation->update([
                    'status' => 'approved',
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                    'updated_by' => Auth::id(),
                ]);

                app(AccountingIntegrationService::class)->postCashReconciliationVariance($reconciliation->fresh(), Auth::id());
            });

            return redirect()->route('cash-reconciliations.show', $reconciliation->id)
                ->with('success', 'Reconciliation approved and locked successfully.');
        } catch (\Throwable $e) {
            return redirect()->route('cash-reconciliations.index')->with('error', 'Invalid reconciliation ID.');
        }
    }

    public function reverseApproval(Request $request, $routeId)
    {
        try {
            $reconciliation = $this->findReconciliation($routeId);

            if (! $reconciliation) {
                return redirect()->route('cash-reconciliations.index')->with('error', 'Cash reconciliation not found.');
            }

            if ($reconciliation->status !== 'approved') {
                return redirect()->back()->with('error', 'Only approved reconciliations can be reversed.');
            }

            $validated = $request->validate([
                'reversal_reason' => 'required|string|max:1000',
            ]);

            DB::transaction(function () use ($reconciliation, $validated) {
                if ((string) $reconciliation->accounting_status === 'posted' && (int) $reconciliation->accounting_journal_id > 0) {
                    app(AccountingIntegrationService::class)->reverseCashReconciliationVariance($reconciliation, $validated['reversal_reason'], Auth::id());
                }

                $reconciliation->update([
                    'status' => 'reopened',
                    'reversed_by' => Auth::id(),
                    'reversed_at' => now(),
                    'reversal_reason' => $validated['reversal_reason'],
                    'updated_by' => Auth::id(),
                ]);
            });

            return redirect()->route('cash-reconciliations.show', $reconciliation->id)
                ->with('success', 'Approval reversed. Cashier can now edit and resubmit.');
        } catch (\Throwable $e) {
            return redirect()->route('cash-reconciliations.index')->with('error', 'Invalid reconciliation ID.');
        }
    }

    private function findReconciliation($routeId, array $relations = [])
    {
        $query = CashReconciliation::query();

        if (! empty($relations)) {
            $query->with($relations);
        }

        if (is_numeric($routeId)) {
            $reconciliation = $query->find((int) $routeId);

            if ($reconciliation) {
                return $reconciliation;
            }
        }

        try {
            $decryptedId = Crypt::decryptString((string) $routeId);

            if (is_numeric($decryptedId)) {
                return $query->find((int) $decryptedId);
            }
        } catch (\Throwable $e) {
            // Ignore and return null below.
        }

        return null;
    }

    private function computeExpectedForUser(int $userId, string $businessDate, float $openingFloat): array
    {
        $expectedCashSales = (float) Payment::query()
            ->join('sales', 'sales.id', '=', 'payments.sale_id')
            ->where('sales.created_by', $userId)
            ->whereDate('sales.date', $businessDate)
            ->whereNull('sales.cancelled_at')
            ->sum('payments.paid_amount');

        $expectedCashExpenses = (float) ExpenseVoucherItem::query()
            ->whereHas('voucher', function ($q) use ($userId, $businessDate) {
                $q->where('created_by', $userId)
                    ->whereDate('voucher_date', $businessDate);
            })
            ->sum('amount');

        $expectedClosingCash = round($openingFloat + $expectedCashSales - $expectedCashExpenses, 2);

        return [
            'expected_cash_sales' => round($expectedCashSales, 2),
            'expected_cash_expenses' => round($expectedCashExpenses, 2),
            'expected_closing_cash' => $expectedClosingCash,
        ];
    }
}

