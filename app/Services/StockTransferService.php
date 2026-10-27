<?php

namespace App\Services;

use App\Models\OutletStock;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockTransferService
{
    public function __construct(private StockLedgerService $stockLedgerService)
    {
    }

    public function createAndDispatch(array $data, int $userId): StockTransfer
    {
        return DB::transaction(function () use ($data, $userId) {
            $fromOutletId = (int) $data['from_outlet_id'];
            $toOutletId = (int) $data['to_outlet_id'];

            if ($fromOutletId === $toOutletId) {
                throw ValidationException::withMessages([
                    'to_outlet_id' => 'Destination outlet cannot be the same as the source outlet.',
                ]);
            }

            $transfer = StockTransfer::create([
                'transfer_no' => StockTransfer::generateTransferNo(),
                'from_outlet_id' => $fromOutletId,
                'to_outlet_id' => $toOutletId,
                'status' => 'dispatched',
                'transfer_date' => $data['transfer_date'] ?? date('Y-m-d'),
                'dispatched_at' => now(),
                'dispatched_by' => $userId,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($data['items'] as $item) {
                $productId = (int) $item['product_id'];
                $qty = (float) $item['quantity'];

                if ($qty <= 0) {
                    continue;
                }

                $sourceStock = OutletStock::where('outlet_id', $fromOutletId)
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->first();

                $currentQty = $sourceStock ? (float) $sourceStock->quantity : 0;
                if ($currentQty < $qty) {
                    $productName = Product::where('id', $productId)->value('name') ?? "Item #$productId";
                    throw ValidationException::withMessages([
                        'items' => "Insufficient stock at source warehouse for '{$productName}'. Available: {$currentQty}, requested: {$qty}.",
                    ]);
                }

                $beforeQty = $currentQty;
                $afterQty = $beforeQty - $qty;

                $sourceStock->quantity = $afterQty;
                $sourceStock->save();

                $product = Product::find($productId);

                $transferItem = StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $productId,
                    'qty_dispatched' => $qty,
                    'qty_received' => 0,
                    'unit_cost' => $product?->cost_price ?? 0,
                ]);

                $this->stockLedgerService->record([
                    'outlet_id' => $fromOutletId,
                    'product_id' => $productId,
                    'movement_type' => 'transfer_out',
                    'reference_type' => 'StockTransfer',
                    'reference_id' => $transfer->id,
                    'reference_detail_id' => $transferItem->id,
                    'qty_out' => $qty,
                    'balance_before' => $beforeQty,
                    'balance_after' => $afterQty,
                    'unit_cost' => $product?->cost_price,
                    'notes' => "Transferred to outlet #$toOutletId via {$transfer->transfer_no}",
                    'created_by' => $userId,
                ]);
            }

            return $transfer->load(['items.product', 'fromOutlet', 'toOutlet']);
        });
    }

    public function receive(StockTransfer $transfer, array $receivedQuantities, int $userId): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $receivedQuantities, $userId) {
            if ($transfer->status === 'received') {
                throw new \InvalidArgumentException('This transfer has already been marked as received.');
            }

            if ($transfer->status === 'cancelled') {
                throw new \InvalidArgumentException('Cannot receive a cancelled transfer.');
            }

            $transfer->update([
                'status' => 'received',
                'received_at' => now(),
                'received_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach ($transfer->items as $item) {
                $qtyReceived = isset($receivedQuantities[$item->id])
                    ? (float) $receivedQuantities[$item->id]
                    : (float) $item->qty_dispatched;

                $item->update(['qty_received' => $qtyReceived]);

                $destStock = OutletStock::firstOrCreate(
                    [
                        'outlet_id' => $transfer->to_outlet_id,
                        'product_id' => $item->product_id,
                    ],
                    [
                        'quantity' => 0,
                        'stock_level' => 0,
                    ]
                );

                $destStock = OutletStock::where('id', $destStock->id)->lockForUpdate()->first();

                $beforeQty = (float) $destStock->quantity;
                $afterQty = $beforeQty + $qtyReceived;

                $destStock->quantity = $afterQty;
                $destStock->save();

                $product = $item->product;

                $this->stockLedgerService->record([
                    'outlet_id' => $transfer->to_outlet_id,
                    'product_id' => $item->product_id,
                    'movement_type' => 'transfer_in',
                    'reference_type' => 'StockTransfer',
                    'reference_id' => $transfer->id,
                    'reference_detail_id' => $item->id,
                    'qty_in' => $qtyReceived,
                    'balance_before' => $beforeQty,
                    'balance_after' => $afterQty,
                    'unit_cost' => $product?->cost_price,
                    'notes' => "Received from outlet #{$transfer->from_outlet_id} via {$transfer->transfer_no}",
                    'created_by' => $userId,
                ]);
            }

            return $transfer->fresh(['items.product', 'fromOutlet', 'toOutlet']);
        });
    }

    public function cancel(StockTransfer $transfer, int $userId, string $reason = ''): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $userId, $reason) {
            if ($transfer->status !== 'dispatched') {
                throw new \InvalidArgumentException('Only dispatched transfers can be cancelled.');
            }

            $transfer->update([
                'status' => 'cancelled',
                'notes' => trim(($transfer->notes ? $transfer->notes . ' | ' : '') . "Cancelled by user #$userId: $reason"),
                'updated_by' => $userId,
            ]);

            foreach ($transfer->items as $item) {
                $sourceStock = OutletStock::firstOrCreate(
                    ['outlet_id' => $transfer->from_outlet_id, 'product_id' => $item->product_id],
                    ['quantity' => 0, 'stock_level' => 0]
                );

                $sourceStock = OutletStock::where('id', $sourceStock->id)->lockForUpdate()->first();
                $beforeQty = (float) $sourceStock->quantity;
                $afterQty = $beforeQty + (float) $item->qty_dispatched;
                $sourceStock->quantity = $afterQty;
                $sourceStock->save();

                $product = $item->product;

                $this->stockLedgerService->record([
                    'outlet_id' => $transfer->from_outlet_id,
                    'product_id' => $item->product_id,
                    'movement_type' => 'transfer_in',
                    'reference_type' => 'StockTransfer',
                    'reference_id' => $transfer->id,
                    'reference_detail_id' => $item->id,
                    'qty_in' => (float) $item->qty_dispatched,
                    'balance_before' => $beforeQty,
                    'balance_after' => $afterQty,
                    'unit_cost' => $product?->cost_price,
                    'notes' => "Reversed transfer cancellation #{$transfer->transfer_no}",
                    'created_by' => $userId,
                ]);
            }

            return $transfer->fresh(['items.product', 'fromOutlet', 'toOutlet']);
        });
    }
}
