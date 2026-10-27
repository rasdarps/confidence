<?php

namespace App\Http\Controllers\Backend;

use App\DataTables\Admin\Settings\ExpenseVoucherDataTable;
use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use App\Models\ExpenseVoucher;
use App\Models\ExpenseVoucherItem;
use App\Services\Accounting\AccountingIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ExpenseVoucherController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:expense-list', ['only' => ['index', 'show', 'print']]);
        $this->middleware('permission:expense-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:expense-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:expense-delete', ['only' => ['destroy']]);
    }

    public function index(ExpenseVoucherDataTable $dataTable)
    {
        return $dataTable->render('backend.expense_voucher.index');
    }

    public function create()
    {
        $expenseCategories = ExpenseCategory::orderBy('name')->get();
        return view('backend.expense_voucher.create', compact('expenseCategories'));
    }

    public function show($encryptedId)
    {
        try {
            $id = Crypt::decrypt($encryptedId);
            $voucher = ExpenseVoucher::with(['items.category', 'createdBy'])->find($id);

            if (! $voucher) {
                return redirect()->route('expense-vouchers.index')->with('error', 'Expense voucher not found.');
            }

            return view('backend.expense_voucher.show', compact('voucher'));
        } catch (\Exception $e) {
            return redirect()->route('expense-vouchers.index')->with('error', 'Invalid voucher ID.');
        }
    }

    public function print($encryptedId)
    {
        try {
            $id = Crypt::decrypt($encryptedId);
            $voucher = ExpenseVoucher::with(['items.category', 'createdBy'])->find($id);

            if (! $voucher) {
                return redirect()->route('expense-vouchers.index')->with('error', 'Expense voucher not found.');
            }

            return view('backend.expense_voucher.print', compact('voucher'));
        } catch (\Exception $e) {
            return redirect()->route('expense-vouchers.index')->with('error', 'Invalid voucher ID.');
        }
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'voucher_date' => 'required|date',
            'reference_no' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('expense_vouchers', 'reference_no')->whereNull('deleted_at'),
            ],
            'notes' => 'nullable|string|max:1000',
            'expense_category_id' => 'required|array|min:1',
            'expense_category_id.*' => [
                'required',
                Rule::exists('expense_categories', 'id')->whereNull('deleted_at'),
            ],
            'amount' => 'required|array|min:1',
            'amount.*' => 'required|numeric|min:0.01',
            'description' => 'nullable|array',
            'description.*' => 'nullable|string|max:1000',
        ], [
            'voucher_date.required' => 'Please select voucher date',
            'expense_category_id.required' => 'Please add at least one expense line',
            'expense_category_id.*.required' => 'Please select category for each line',
            'amount.*.required' => 'Please enter amount for each line',
            'amount.*.min' => 'Line amount must be greater than zero',
            'reference_no.unique' => 'Reference number already exists',
        ]);

        try {
            DB::transaction(function () use ($validatedData) {
                $lineTotal = collect($validatedData['amount'])->map(fn ($v) => (float) $v)->sum();

                $voucher = ExpenseVoucher::create([
                    'voucher_date' => $validatedData['voucher_date'],
                    'reference_no' => $validatedData['reference_no'] ?? null,
                    'total_amount' => $lineTotal,
                    'notes' => $validatedData['notes'] ?? null,
                    'created_by' => Auth::id(),
                ]);

                foreach ($validatedData['expense_category_id'] as $index => $categoryId) {
                    ExpenseVoucherItem::create([
                        'expense_voucher_id' => $voucher->id,
                        'expense_category_id' => $categoryId,
                        'amount' => $validatedData['amount'][$index],
                        'description' => $validatedData['description'][$index] ?? null,
                        'created_by' => Auth::id(),
                    ]);
                }

                app(AccountingIntegrationService::class)->postExpenseVoucher($voucher, Auth::id());
            });

            return response()->json([
                'status' => 200,
                'message' => 'Expense voucher was successfully created',
                'redirect' => route('expense-vouchers.index'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 400,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function edit($encryptedId)
    {
        try {
            $id = Crypt::decrypt($encryptedId);
            $voucher = ExpenseVoucher::with('items')->find($id);

            if (! $voucher) {
                return redirect()->route('expense-vouchers.index')->with('error', 'Expense voucher not found.');
            }

            $expenseCategories = ExpenseCategory::orderBy('name')->get();
            return view('backend.expense_voucher.edit', compact('voucher', 'expenseCategories'));
        } catch (\Exception $e) {
            return redirect()->route('expense-vouchers.index')->with('error', 'Invalid voucher ID.');
        }
    }

    public function update(Request $request, $encryptedId)
    {
        try {
            $id = Crypt::decrypt($encryptedId);
            $voucher = ExpenseVoucher::with('items')->find($id);

            if (! $voucher) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Expense voucher not found.',
                ], 404);
            }

            $validatedData = $request->validate([
                'voucher_date' => 'required|date',
                'reference_no' => [
                    'nullable',
                    'string',
                    'max:100',
                    Rule::unique('expense_vouchers', 'reference_no')->ignore($voucher->id)->whereNull('deleted_at'),
                ],
                'notes' => 'nullable|string|max:1000',
                'item_id' => 'nullable|array',
                'item_id.*' => 'nullable|integer',
                'expense_category_id' => 'required|array|min:1',
                'expense_category_id.*' => [
                    'required',
                    Rule::exists('expense_categories', 'id')->whereNull('deleted_at'),
                ],
                'amount' => 'required|array|min:1',
                'amount.*' => 'required|numeric|min:0.01',
                'description' => 'nullable|array',
                'description.*' => 'nullable|string|max:1000',
            ]);

            DB::transaction(function () use ($voucher, $validatedData) {
                if ((string) $voucher->accounting_status === 'posted' && (int) $voucher->accounting_journal_id > 0) {
                    app(AccountingIntegrationService::class)->reverseExpenseVoucher($voucher, 'Expense voucher updated', Auth::id());
                }

                $lineTotal = collect($validatedData['amount'])->map(fn ($v) => (float) $v)->sum();

                $voucher->update([
                    'voucher_date' => $validatedData['voucher_date'],
                    'reference_no' => $validatedData['reference_no'] ?? null,
                    'total_amount' => $lineTotal,
                    'notes' => $validatedData['notes'] ?? null,
                    'updated_by' => Auth::id(),
                ]);

                $submittedItemIds = collect($validatedData['item_id'] ?? [])
                    ->filter(fn ($id) => ! empty($id))
                    ->map(fn ($id) => (int) $id)
                    ->values();

                // Soft-delete removed lines so deleted_by can be captured by Auditable.
                $voucher->items()
                    ->whereNotIn('id', $submittedItemIds->all())
                    ->get()
                    ->each(function ($item) {
                        $item->delete();
                    });

                foreach ($validatedData['expense_category_id'] as $index => $categoryId) {
                    $itemId = $validatedData['item_id'][$index] ?? null;

                    if (! empty($itemId)) {
                        $item = ExpenseVoucherItem::where('expense_voucher_id', $voucher->id)
                            ->where('id', (int) $itemId)
                            ->first();

                        if ($item) {
                            $item->update([
                                'expense_category_id' => $categoryId,
                                'amount' => $validatedData['amount'][$index],
                                'description' => $validatedData['description'][$index] ?? null,
                                'updated_by' => Auth::id(),
                            ]);
                            continue;
                        }
                    }

                    ExpenseVoucherItem::create([
                        'expense_voucher_id' => $voucher->id,
                        'expense_category_id' => $categoryId,
                        'amount' => $validatedData['amount'][$index],
                        'description' => $validatedData['description'][$index] ?? null,
                        'created_by' => Auth::id(),
                    ]);
                }

                app(AccountingIntegrationService::class)->postExpenseVoucher($voucher->fresh(), Auth::id());
            });

            return response()->json([
                'status' => 200,
                'message' => 'Expense voucher was successfully updated',
                'redirect' => route('expense-vouchers.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 400,
                'message' => 'Failed to update expense voucher.',
            ], 400);
        }
    }

    public function destroy($encryptedId)
    {
        try {
            $id = Crypt::decrypt($encryptedId);
            $voucher = ExpenseVoucher::with('items')->find($id);

            if (! $voucher) {
                $message = 'Expense voucher not found.';
                if (request()->expectsJson() || request()->ajax()) {
                    return response()->json(['status' => 404, 'message' => $message], 404);
                }

                return redirect()->route('expense-vouchers.index')->with(['message' => $message, 'alert-type' => 'error']);
            }

            DB::transaction(function () use ($voucher) {
                if ((string) $voucher->accounting_status === 'posted' && (int) $voucher->accounting_journal_id > 0) {
                    app(AccountingIntegrationService::class)->reverseExpenseVoucher($voucher, 'Expense voucher deleted', Auth::id());
                }

                $voucher->items()->delete();
                $voucher->delete();
            });

            $message = 'Expense voucher was successfully deleted.';

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 200,
                    'message' => $message,
                ]);
            }

            return redirect()->route('expense-vouchers.index')->with([
                'message' => $message,
                'alert-type' => 'success',
            ]);
        } catch (\Exception $e) {
            $message = 'Failed to delete expense voucher.';

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 400,
                    'message' => $message,
                ], 400);
            }

            return redirect()->route('expense-vouchers.index')->with([
                'message' => $message,
                'alert-type' => 'error',
            ]);
        }
    }
}
