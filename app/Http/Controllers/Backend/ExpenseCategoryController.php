<?php

namespace App\Http\Controllers\Backend;

use App\DataTables\Admin\Settings\ExpenseCategoryDataTable;
use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use App\Models\ExpenseVoucherItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ExpenseCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:expense-category-list', ['only' => ['index']]);
        $this->middleware('permission:expense-category-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:expense-category-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:expense-category-delete', ['only' => ['destroy']]);
    }

    public function index(ExpenseCategoryDataTable $dataTable)
    {
        return $dataTable->render('backend.expense_category.index');
    }

    public function create()
    {
        return view('backend.expense_category.create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('expense_categories', 'name')->whereNull('deleted_at')],
        ], [
            'name.required' => 'Please enter expense category name',
            'name.unique' => 'This expense category already exists',
        ]);

        try {
            $data = $validatedData;
            $data['created_by'] = Auth::id();

            DB::transaction(function () use ($data) {
                ExpenseCategory::create($data);
            });

            return response()->json([
                'status' => 200,
                'message' => "Expense category '{$data['name']}' was successfully created",
                'redirect' => route('expense-categories.index'),
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
            $expenseCategory = ExpenseCategory::find($id);

            if (! $expenseCategory) {
                return redirect()->route('expense-categories.index')->with('error', 'Expense category not found.');
            }

            return view('backend.expense_category.edit', compact('expenseCategory'));
        } catch (\Exception $e) {
            return redirect()->route('expense-categories.index')->with('error', 'Invalid expense category ID.');
        }
    }

    public function update(Request $request, $encryptedId)
    {
        try {
            $id = Crypt::decrypt($encryptedId);
            $expenseCategory = ExpenseCategory::find($id);

            if (! $expenseCategory) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Expense category not found.',
                ], 404);
            }

            $validatedData = $request->validate([
                'name' => ['required', 'string', 'max:255', Rule::unique('expense_categories', 'name')->ignore($id)->whereNull('deleted_at')],
            ], [
                'name.required' => 'Please enter expense category name',
                'name.unique' => 'This expense category already exists',
            ]);

            DB::transaction(function () use ($expenseCategory, $validatedData) {
                $expenseCategory->update([
                    'name' => $validatedData['name'],
                    'updated_by' => Auth::id(),
                ]);
            });

            return response()->json([
                'status' => 200,
                'message' => "Expense category '{$validatedData['name']}' was successfully updated",
                'redirect' => route('expense-categories.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 400,
                'message' => 'Failed to update expense category.',
            ], 400);
        }
    }

    public function destroy($encryptedId)
    {
        try {
            $id = Crypt::decrypt($encryptedId);
            $expenseCategory = ExpenseCategory::find($id);

            if (! $expenseCategory) {
                $message = 'Expense category not found.';
                if (request()->expectsJson() || request()->ajax()) {
                    return response()->json(['status' => 404, 'message' => $message], 404);
                }

                return redirect()->route('expense-categories.index')->with(['message' => $message, 'alert-type' => 'error']);
            }

            if (
                ExpenseVoucherItem::withTrashed()->where('expense_category_id', $expenseCategory->id)->exists()
            ) {
                $message = 'Cannot delete category with existing expenses tied to it.';
                if (request()->expectsJson() || request()->ajax()) {
                    return response()->json(['status' => 422, 'message' => $message], 422);
                }

                return redirect()->route('expense-categories.index')->with(['message' => $message, 'alert-type' => 'error']);
            }

            DB::transaction(function () use ($expenseCategory) {
                $expenseCategory->delete();
            });

            $message = 'Expense category was successfully deleted.';

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 200,
                    'message' => $message,
                ]);
            }

            return redirect()->route('expense-categories.index')->with([
                'message' => $message,
                'alert-type' => 'success',
            ]);
        } catch (\Exception $e) {
            $message = 'Failed to delete expense category.';

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 400,
                    'message' => $message,
                ], 400);
            }

            return redirect()->route('expense-categories.index')->with([
                'message' => $message,
                'alert-type' => 'error',
            ]);
        }
    }
}
