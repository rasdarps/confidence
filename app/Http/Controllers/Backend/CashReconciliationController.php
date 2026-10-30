<?php

namespace App\Http\Controllers\Backend;

use App\DataTables\Admin\Settings\CashReconciliationDataTable;
use App\Http\Controllers\Controller;
use App\Models\CashReconciliation;
use App\Models\ExpenseVoucherItem;
use App\Models\Outlet;
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
        $assignedOutletId = $currentUser ? $currentUser->assignedOutletId() : null;
        $outlets = Outlet::where('is_active', true)->orderBy('is_central', 'desc')->orderBy('name')->get();

        return $dataTable->render('backend.cash_reconciliation.index', compact('isRestrictedToOwnReconciles', 'outlets', 'assignedOutletId'));
    }

    public function create()
    {
        $currentUser = Auth::user();
        $assignedOutletId = $currentUser ? $currentUser->assignedOutletId() : null;
        $outlets = Outlet::where('is_active', true)->orderBy('is_central', 'desc')->orderBy('name')->get();

        if ($assignedOutletId) {
            $currentOutletId = $assignedOutletId;
            $canChangeOutlet = false;
        } else {
            $central = $outlets->firstWhere('is_central', true);
            $currentOutletId = $central ? $central->id : ($outlets->first()?->id ?? 1);
            $canChangeOutlet = true;
        }

        $currentOutlet = $outlets->firstWhere('id', $currentOutletId);
        $businessDate = now()->format('Y-m-d');
        $openingFloat = 0;

        $expected = $this->computeExpectedForUser(Auth::id(), $businessDate, $openingFloat, $currentOutletId);

        return view('backend.cash_reconciliation.create', compact('businessDate', 'openingFloat', 'expected', 'outlets', 'currentOutletId', 'currentOutlet', 'canChangeOutlet'));
    }

    public function edit($routeId)
    {
        try {
            $reconciliation = $this->findReconciliation($routeId, ['user', 'createdBy', 'approvedBy', 'reversedBy', 'outlet']);

            if (! $reconciliation) {
                return redirect()->route('cash-reconciliations.index')->with('error', 'Cash reconciliation not found.');
            }

            $currentUser = Auth::user();
            $assignedOutletId = $currentUser ? $currentUser->assignedOutletId() : null;
            $isSuperAdmin = $currentUser && strtolower((string) ($currentUser->user_type ?? '')) === 'super admin';

            if ($assignedOutletId && ! $isSuperAdmin && (int) $reconciliation->outlet_id !== (int) $assignedOutletId) {
                return redirect()->route('cash-reconciliations.index')->with('error', 'You do not have access to reconciliations from other branches.');
            }

            if ($reconciliation->status === 'approved') {
                return redirect()->route('cash-reconciliations.show', $reconciliation->id)
                    ->with('error', 'Approved reconciliation is locked. Reverse approval before editing.');
            }

            $expected = $this->computeExpectedForUser(
                (int) $reconciliation->user_id,
                (string) $reconciliation->business_date->format('Y-m-d'),
                (float) $reconciliation->opening_float,
                (int) $reconciliation->outlet_id
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
            'outlet_id' => 'nullable|exists:outlets,id',
        ]);

        $openingFloat = (float) ($validated['opening_float'] ?? 0);
        $currentUser = Auth::user();
        $assignedOutletId = $currentUser ? $currentUser->assignedOutletId() : null;
        $outletId = $assignedOutletId ?: ((int) ($validated['outlet_id'] ?? 0) ?: null);

        $expected = $this->computeExpectedForUser(Auth::id(), $validated['business_date'], $openingFloat, $outletId);

        return response()->json($expected);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'outlet_id' => 'nullable|exists:outlets,id',
            'business_date' => 'required|date',
            'opening_float' => 'required|numeric|min:0',
            'physical_counted_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $userId = Auth::id();
        $currentUser = Auth::user();
        $assignedOutletId = $currentUser ? $currentUser->assignedOutletId() : null;
        $outletId = $assignedOutletId ?: ((int) $request->input('outlet_id') ?: (Outlet::where('is_central', true)->value('id') ?? 1));

        $existing = CashReconciliation::where('business_date', $validated['business_date'])
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            return redirect()->back()->withInput()->with('error', 'A reconciliation already exists for this cashier and date. Please edit the existing record if it has been reopened.');
        }

        $openingFloat = (float) $validated['opening_float'];
        $physicalCash = (float) $validated['physical_counted_cash'];
        $expected = $this->computeExpectedForUser($userId, $validated['business_date'], $openingFloat, $outletId);

        $variance = round($physicalCash - $expected['expected_closing_cash'], 2);
        $varianceType = 'balanced';

        if ($variance > 0) {
            $varianceType = 'overage';
        } elseif ($variance < 0) {
            $varianceType = 'shortage';
        }

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
            $reconciliation = $this->findReconciliation($routeId, ['user', 'createdBy', 'approvedBy', 'reversedBy', 'outlet']);

            if (! $reconciliation) {
                return redirect()->route('cash-reconciliations.index')->with('error', 'Cash reconciliation not found.');
            }

            $currentUser = Auth::user();
            $assignedOutletId = $currentUser ? $currentUser->assignedOutletId() : null;
            $isSuperAdmin = $currentUser && strtolower((string) ($currentUser->user_type ?? '')) === 'super admin';

            if ($assignedOutletId && ! $isSuperAdmin && (int) $reconciliation->outlet_id !== (int) $assignedOutletId) {
                return redirect()->route('cash-reconciliations.index')->with('error', 'You do not have access to reconciliations from other branches.');
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

            $currentUser = Auth::user();
            $assignedOutletId = $currentUser ? $currentUser->assignedOutletId() : null;
            $isSuperAdmin = $currentUser && strtolower((string) ($currentUser->user_type ?? '')) === 'super admin';

            if ($assignedOutletId && ! $isSuperAdmin && (int) $reconciliation->outlet_id !== (int) $assignedOutletId) {
                return redirect()->route('cash-reconciliations.index')->with('error', 'You do not have access to reconciliations from other branches.');
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

            $expected = $this->computeExpectedForUser((int) $reconciliation->user_id, $validated['business_date'], (float) $validated['opening_float'], (int) $reconciliation->outlet_id);
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

            $currentUser = Auth::user();
            $assignedOutletId = $currentUser ? $currentUser->assignedOutletId() : null;
            $isSuperAdmin = $currentUser && strtolower((string) ($currentUser->user_type ?? '')) === 'super admin';

            if ($assignedOutletId && ! $isSuperAdmin && (int) $reconciliation->outlet_id !== (int) $assignedOutletId) {
                return redirect()->route('cash-reconciliations.index')->with('error', 'You do not have access to approve reconciliations from other branches.');
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

            $currentUser = Auth::user();
            $assignedOutletId = $currentUser ? $currentUser->assignedOutletId() : null;
            $isSuperAdmin = $currentUser && strtolower((string) ($currentUser->user_type ?? '')) === 'super admin';

            if ($assignedOutletId && ! $isSuperAdmin && (int) $reconciliation->outlet_id !== (int) $assignedOutletId) {
                return redirect()->route('cash-reconciliations.index')->with('error', 'You do not have access to reverse reconciliations from other branches.');
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

    private function computeExpectedForUser(int $userId, string $businessDate, float $openingFloat, ?int $outletId = null): array
    {
        $salesQuery = Payment::query()
            ->join('sales', 'sales.id', '=', 'payments.sale_id')
            ->where('sales.created_by', $userId)
            ->whereDate('sales.date', $businessDate)
            ->whereNull('sales.cancelled_at');

        if ($outletId) {
            $salesQuery->where('sales.outlet_id', $outletId);
        }

        $expectedCashSales = (float) $salesQuery->sum('payments.paid_amount');

        $expensesQuery = ExpenseVoucherItem::query()
            ->whereHas('voucher', function ($q) use ($userId, $businessDate, $outletId) {
                $q->where('created_by', $userId)
                    ->whereDate('voucher_date', $businessDate);
                if ($outletId) {
                    $q->where('outlet_id', $outletId);
                }
            });

        $expectedCashExpenses = (float) $expensesQuery->sum('amount');

        $expectedClosingCash = round($openingFloat + $expectedCashSales - $expectedCashExpenses, 2);

        return [
            'expected_cash_sales' => round($expectedCashSales, 2),
            'expected_cash_expenses' => round($expectedCashExpenses, 2),
            'expected_closing_cash' => $expectedClosingCash,
        ];
    }
}

