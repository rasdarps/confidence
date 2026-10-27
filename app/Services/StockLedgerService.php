<?php

namespace App\Services;

use App\Models\StockLedger;

class StockLedgerService
{
    public function record(array $payload): StockLedger
    {
        $qtyIn = (float) ($payload['qty_in'] ?? 0);
        $qtyOut = (float) ($payload['qty_out'] ?? 0);
        $deltaQty = $qtyIn - $qtyOut;

        $movementType = $payload['movement_type'] ?? null;
        if (! in_array($movementType, [
            'purchase',
            'sale',
            'adjustment_increase',
            'adjustment_decrease',
            'purchase_cancel',
            'sale_cancel',
        ], true)) {
            throw new \InvalidArgumentException('Invalid stock ledger movement type.');
        }

        return StockLedger::create([
            'product_id' => $payload['product_id'],
            'movement_type' => $movementType,
            'reference_type' => $payload['reference_type'] ?? 'unknown',
            'reference_id' => $payload['reference_id'] ?? null,
            'reference_detail_id' => $payload['reference_detail_id'] ?? null,
            'movement_at' => $payload['movement_at'] ?? now(),
            'qty_in' => $qtyIn,
            'qty_out' => $qtyOut,
            'delta_qty' => $deltaQty,
            'balance_before' => (float) ($payload['balance_before'] ?? 0),
            'balance_after' => (float) ($payload['balance_after'] ?? 0),
            'unit_cost' => $payload['unit_cost'] ?? null,
            'unit_price' => $payload['unit_price'] ?? null,
            'reason' => $payload['reason'] ?? null,
            'notes' => $payload['notes'] ?? null,
            'created_by' => $payload['created_by'] ?? null,
        ]);
    }
}
