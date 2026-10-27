<?php

namespace App\Http\Controllers\Backend;

use App\DataTables\Admin\Settings\StockAdjustmentDataTable;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Services\Accounting\AccountingIntegrationService;
use App\Services\StockLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StockAdjustmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:stock-adjustment-list', ['only' => ['index', 'show']]);
        $this->middleware('permission:stock_adjustment-print', ['only' => ['print']]);
        $this->middleware('permission:stock-adjustment-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:stock-adjustment-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:stock-adjustment-delete', ['only' => ['destroy']]);
    }

    public function index(StockAdjustmentDataTable $dataTable)
    {
        return $dataTable->render('backend.stock_adjustment.index');
    }

    public function create()
    {
        $products = Product::orderBy('name')->get(['id', 'name', 'barcode', 'quantity', 'cost_price']);
        return view('backend.stock_adjustment.create', compact('products'));
    }

    public function show($encryptedId)
    {
        try {
            $id = Crypt::decrypt($encryptedId);
            $adjustment = StockAdjustment::with(['items.product', 'createdBy'])->find($id);

            if (! $adjustment) {
                return redirect()->route('stock-adjustments.index')->with('error', 'Stock adjustment not found.');
            }

            return view('backend.stock_adjustment.show', compact('adjustment'));
        } catch (\Exception $e) {
            return redirect()->route('stock-adjustments.index')->with('error', 'Invalid stock adjustment ID.');
        }
    }

    public function print($encryptedId)
    {
        try {
            $id = Crypt::decrypt($encryptedId);
            $adjustment = StockAdjustment::with(['items.product', 'createdBy'])->find($id);

            if (! $adjustment) {
                return redirect()->route('stock-adjustments.index')->with('error', 'Stock adjustment not found.');
            }

            return view('backend.stock_adjustment.print', compact('adjustment'));
        } catch (\Exception $e) {
            return redirect()->route('stock-adjustments.index')->with('error', 'Invalid stock adjustment ID.');
        }
    }

    public function store(Request $request)
    {
        $validatedData = $this->validateRequest($request, null);

        try {
            DB::transaction(function () use ($validatedData) {
                $totalQty = collect($validatedData['qty'])->map(fn ($v) => (float) $v)->sum();
                $totalValue = collect($validatedData['amount'])->map(fn ($v) => (float) $v)->sum();

                $adjustment = StockAdjustment::create([
                    'adjustment_date' => $validatedData['adjustment_date'],
                    'reference_no' => $validatedData['reference_no'] ?? null,
                    'adjustment_type' => $validatedData['adjustment_type'],
                    'reason' => $validatedData['reason'] ?? null,
                    'notes' => $validatedData['notes'] ?? null,
                    'total_qty' => $totalQty,
                    'total_value' => $totalValue,
                    'created_by' => Auth::id(),
                ]);

                $this->applyLines($adjustment, $validatedData);
                app(AccountingIntegrationService::class)->postStockAdjustment($adjustment->fresh('items'), Auth::id());
            });

            return response()->json([
                'status' => 200,
                'message' => 'Stock adjustment was successfully created',
                'redirect' => route('stock-adjustments.index'),
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
            $adjustment = StockAdjustment::with('items')->find($id);

            if (! $adjustment) {
                return redirect()->route('stock-adjustments.index')->with('error', 'Stock adjustment not found.');
            }

            $products = Product::orderBy('name')->get(['id', 'name', 'barcode', 'quantity', 'cost_price']);
            return view('backend.stock_adjustment.edit', compact('adjustment', 'products'));
        } catch (\Exception $e) {
            return redirect()->route('stock-adjustments.index')->with('error', 'Invalid stock adjustment ID.');
        }
    }

    public function update(Request $request, $encryptedId)
    {
        try {
            $id = Crypt::decrypt($encryptedId);
            $adjustment = StockAdjustment::with('items')->find($id);

            if (! $adjustment) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Stock adjustment not found.',
                ], 404);
            }

            $validatedData = $this->validateRequest($request, $adjustment->id);

            DB::transaction(function () use ($adjustment, $validatedData) {
                if ((string) $adjustment->accounting_status === 'posted' && (int) $adjustment->accounting_journal_id > 0) {
                    app(AccountingIntegrationService::class)->reverseStockAdjustment($adjustment, 'Stock adjustment updated', Auth::id());
                }

                $this->reverseLines($adjustment);

                $totalQty = collect($validatedData['qty'])->map(fn ($v) => (float) $v)->sum();
                $totalValue = collect($validatedData['amount'])->map(fn ($v) => (float) $v)->sum();

                $adjustment->update([
                    'adjustment_date' => $validatedData['adjustment_date'],
                    'reference_no' => $validatedData['reference_no'] ?? null,
                    'adjustment_type' => $validatedData['adjustment_type'],
                    'reason' => $validatedData['reason'] ?? null,
                    'notes' => $validatedData['notes'] ?? null,
                    'total_qty' => $totalQty,
                    'total_value' => $totalValue,
                    'updated_by' => Auth::id(),
                ]);

                $adjustment->items()->forceDelete();
                $this->applyLines($adjustment, $validatedData);
                app(AccountingIntegrationService::class)->postStockAdjustment($adjustment->fresh('items'), Auth::id());
            });

            return response()->json([
                'status' => 200,
                'message' => 'Stock adjustment was successfully updated',
                'redirect' => route('stock-adjustments.index'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 400,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function destroy($encryptedId)
    {
        try {
            $id = Crypt::decrypt($encryptedId);
            $adjustment = StockAdjustment::with('items')->find($id);

            if (! $adjustment) {
                $message = 'Stock adjustment not found.';
                if (request()->expectsJson() || request()->ajax()) {
                    return response()->json(['status' => 404, 'message' => $message], 404);
                }

                return redirect()->route('stock-adjustments.index')->with(['message' => $message, 'alert-type' => 'error']);
            }

            DB::transaction(function () use ($adjustment) {
                if ((string) $adjustment->accounting_status === 'posted' && (int) $adjustment->accounting_journal_id > 0) {
                    app(AccountingIntegrationService::class)->reverseStockAdjustment($adjustment, 'Stock adjustment deleted', Auth::id());
                }

                $this->reverseLines($adjustment);
                $adjustment->items()->delete();
                $adjustment->delete();
            });

            $message = 'Stock adjustment was successfully deleted.';

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 200,
                    'message' => $message,
                ]);
            }

            return redirect()->route('stock-adjustments.index')->with([
                'message' => $message,
                'alert-type' => 'success',
            ]);
        } catch (\Throwable $e) {
            $message = $e->getMessage() ?: 'Failed to delete stock adjustment.';

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 400,
                    'message' => $message,
                ], 400);
            }

            return redirect()->route('stock-adjustments.index')->with([
                'message' => $message,
                'alert-type' => 'error',
            ]);
        }
    }

    private function validateRequest(Request $request, ?int $adjustmentId): array
    {
        $validated = $request->validate([
            'adjustment_date' => 'required|date',
            'reference_no' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('stock_adjustments', 'reference_no')->ignore($adjustmentId)->whereNull('deleted_at'),
            ],
            'adjustment_type' => 'required_unless:physical_count_mode,1|nullable|in:increase,decrease,mixed',
            'reason' => 'nullable|string|max:150',
            'notes' => 'nullable|string|max:1000',
            'product_id' => 'required|array|min:1',
            'product_id.*' => 'required|exists:products,id',
            'qty' => 'required|array|min:1',
            'qty.*' => 'required|numeric|min:0.01',
            'unit_cost' => 'required|array|min:1',
            'unit_cost.*' => 'required|numeric|min:0',
            'amount' => 'required|array|min:1',
            'amount.*' => 'required|numeric|min:0',
            'description' => 'nullable|array',
            'description.*' => 'nullable|string|max:1000',
            'physical_count_mode' => 'nullable|in:0,1',
            'physical_count' => 'nullable|array',
            'physical_count.*' => 'nullable|numeric|min:0',
        ], [
            'adjustment_date.required' => 'Please select adjustment date',
            'adjustment_type.required' => 'Please select adjustment type',
            'product_id.required' => 'Please add at least one adjustment line',
            'qty.*.required' => 'Please enter quantity for each line',
            'qty.*.min' => 'Quantity must be greater than zero',
            'reference_no.unique' => 'Reference number already exists',
            'physical_count.*.min' => 'Physical count cannot be negative',
        ]);

        if (($validated['physical_count_mode'] ?? '0') === '1') {
            $directions = [];

            foreach ($validated['product_id'] as $index => $productId) {
                $product = Product::find($productId);
                if (! $product) {
                    continue;
                }

                $currentQty = (float) ($product->quantity ?? 0);
                $physicalCount = isset($validated['physical_count'][$index])
                    ? (float) $validated['physical_count'][$index]
                    : null;

                if ($physicalCount === null) {
                    throw new \RuntimeException('Please enter physical count for all lines in physical count mode.');
                }

                if ($physicalCount == $currentQty) {
                    throw new \RuntimeException('Physical count matches current stock for one or more lines. No adjustment needed.');
                }

                $lineDirection = $physicalCount > $currentQty ? 'increase' : 'decrease';
                $lineQty = abs($physicalCount - $currentQty);

                $directions[$lineDirection] = true;

                $validated['qty'][$index] = $lineQty;
            }

            $directionKeys = array_keys($directions);
            if (count($directionKeys) === 0) {
                throw new \RuntimeException('Unable to determine adjustment type from physical counts.');
            }

            $validated['adjustment_type'] = count($directionKeys) > 1
                ? 'mixed'
                : $directionKeys[0];
        } else {
            foreach ($validated['qty'] as $qty) {
                if ((float) $qty <= 0) {
                    throw new \RuntimeException('Adjustment quantity must be greater than zero for all lines.');
                }
            }
        }

        return $validated;
    }

    private function applyLines(StockAdjustment $adjustment, array $validatedData): void
    {
        foreach ($validatedData['product_id'] as $index => $productId) {
            $product = Product::where('id', $productId)->lockForUpdate()->firstOrFail();

            $beforeQty = (float) $product->quantity;

            $qty = (float) ($validatedData['qty'][$index] ?? 0);
            $unitCost = (float) $validatedData['unit_cost'][$index];
            $amount = round($qty * $unitCost, 2);

            $afterQty = $beforeQty;
            $lineDirection = $adjustment->adjustment_type;

            if (($validatedData['physical_count_mode'] ?? '0') === '1') {
                $physicalCount = isset($validatedData['physical_count'][$index])
                    ? (float) $validatedData['physical_count'][$index]
                    : $beforeQty;

                if ($physicalCount == $beforeQty) {
                    throw new \RuntimeException("Physical count matches system stock for '{$product->name}'.");
                }

                $lineDirection = $physicalCount > $beforeQty ? 'increase' : 'decrease';
                $qty = abs($physicalCount - $beforeQty);
                $amount = round($qty * $unitCost, 2);
            }

            if ($lineDirection === 'increase') {
                $afterQty = $beforeQty + $qty;
            } else {
                if ($beforeQty < $qty) {
                    throw new \RuntimeException("Cannot reduce '{$product->name}' below zero stock.");
                }
                $afterQty = $beforeQty - $qty;
            }

            $product->update([
                'quantity' => $afterQty,
                'updated_by' => Auth::id(),
            ]);

            $item = StockAdjustmentItem::create([
                'stock_adjustment_id' => $adjustment->id,
                'product_id' => $product->id,
                'qty' => $qty,
                'unit_cost' => $unitCost,
                'amount' => $amount,
                'before_qty' => $beforeQty,
                'after_qty' => $afterQty,
                'description' => $validatedData['description'][$index] ?? null,
                'created_by' => Auth::id(),
            ]);

            //StockLedgerService record to log stock movement
            app(StockLedgerService::class)->record([
                'product_id' => $product->id,
                'movement_type' => $lineDirection === 'increase' ? 'adjustment_increase' : 'adjustment_decrease',
                'reference_type' => 'stock_adjustment',
                'reference_id' => $adjustment->id,
                'reference_detail_id' => $item->id,
                'movement_at' => $adjustment->adjustment_date,
                'qty_in' => $lineDirection === 'increase' ? $qty : 0,
                'qty_out' => $lineDirection === 'decrease' ? $qty : 0,
                'balance_before' => $beforeQty,
                'balance_after' => $afterQty,
                'unit_cost' => $unitCost,
                'reason' => $adjustment->reason,
                'notes' => $validatedData['description'][$index] ?? $adjustment->notes,
                'created_by' => Auth::id(),
            ]);
        }
    }

    private function reverseLines(StockAdjustment $adjustment): void
    {
        foreach ($adjustment->items as $item) {
            $product = Product::where('id', $item->product_id)->lockForUpdate()->first();

            if (! $product) {
                continue;
            }

            $currentQty = (float) $product->quantity;
            $lineQty = (float) $item->qty;
            $lineWasIncrease = (float) $item->after_qty > (float) $item->before_qty;

            if ($lineWasIncrease) {
                if ($currentQty < $lineQty) {
                    throw new \RuntimeException("Cannot reverse adjustment because '{$product->name}' stock is already consumed.");
                }
                $newQty = $currentQty - $lineQty;
                $movementType = 'adjustment_decrease';
            } else {
                $newQty = $currentQty + $lineQty;
                $movementType = 'adjustment_increase';
            }

            $product->update([
                'quantity' => $newQty,
                'updated_by' => Auth::id(),
            ]);

            //StockLedgerService record to log stock movement
            app(StockLedgerService::class)->record([
                'product_id' => $product->id,
                'movement_type' => $movementType,
                'reference_type' => 'stock_adjustment',
                'reference_id' => $adjustment->id,
                'reference_detail_id' => $item->id,
                'movement_at' => now(),
                'qty_in' => $movementType === 'adjustment_increase' ? $lineQty : 0,
                'qty_out' => $movementType === 'adjustment_decrease' ? $lineQty : 0,
                'balance_before' => $currentQty,
                'balance_after' => $newQty,
                'unit_cost' => $item->unit_cost,
                'reason' => 'Stock adjustment reversal',
                'notes' => $item->description,
                'created_by' => Auth::id(),
            ]);
        }
    }
}
