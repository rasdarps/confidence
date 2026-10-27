<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\ExpenseVoucher;
use App\Models\Product;
use App\Models\User;

class MobileApiData
{
    public static function product(Product $product, User $user): array
    {
        $product->loadMissing(['unit', 'category']);
        $data = [
            'id' => $product->id, 'name' => $product->name, 'barcode' => $product->barcode,
            'category' => ['id' => $product->category_id, 'name' => $product->category?->name],
            'unit' => ['id' => $product->unit_id, 'name' => $product->unit?->name],
            'quantity' => (float) $product->quantity, 'stock_level' => (float) $product->stock_level,
            'selling_price' => self::money($product->selling_price), 'currency' => 'GHS',
        ];
        if ($user->can('stock-value-report')) {
            $data['cost_price'] = self::money($product->cost_price);
            $data['stock_value_at_cost'] = self::money($product->quantity * $product->cost_price);
            $data['stock_value_at_selling_price'] = self::money($product->quantity * $product->selling_price);
        }
        return $data;
    }

    public static function money($value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    public static function purchase(Purchase $purchase): array
    {
        $purchase->loadMissing(['purchase_details.product.unit']);
        $payment = PurchasePayment::with('supplier')->where('purchase_id', $purchase->id)->first();
        return [
            'id' => $purchase->id, 'purchase_no' => $purchase->purchase_no, 'date' => $purchase->date->toDateString(),
            'description' => $purchase->description, 'created_by' => $purchase->created_by, 'currency' => 'GHS',
            'cancelled_at' => $purchase->cancelled_at?->toISOString(),
            'supplier' => $payment?->supplier ? ['id' => $payment->supplier->id, 'name' => $payment->supplier->name] : null,
            'payment' => $payment ? [
                'paid_status' => $payment->paid_status, 'total_amount' => self::money($payment->total_amount),
                'paid_amount' => self::money($payment->paid_amount), 'due_amount' => self::money($payment->due_amount),
                'discount_amount' => self::money($payment->discount_amount),
            ] : null,
            'items' => $purchase->purchase_details->map(fn ($line) => [
                'product_id' => $line->product_id, 'product_name' => $line->product?->name, 'quantity' => (int) $line->buying_qty,
                'unit' => $line->product?->unit?->name, 'unit_cost_price' => self::money($line->unit_cost_price),
                'line_total' => self::money($line->buying_price),
            ])->all(),
        ];
    }

    public static function expense(ExpenseVoucher $voucher): array
    {
        $voucher->loadMissing('items.category');
        return [
            'id' => $voucher->id, 'voucher_date' => $voucher->voucher_date->toDateString(),
            'reference_no' => $voucher->reference_no, 'notes' => $voucher->notes, 'currency' => 'GHS',
            'total_amount' => self::money($voucher->total_amount), 'created_by' => $voucher->created_by,
            'accounting_status' => $voucher->accounting_status,
            'items' => $voucher->items->map(fn ($line) => [
                'id' => $line->id, 'expense_category_id' => $line->expense_category_id,
                'category_name' => $line->category?->name, 'amount' => self::money($line->amount), 'description' => $line->description,
            ])->all(),
        ];
    }

    public static function sale(Sale $sale): array
    {
        $sale->loadMissing(['payment.customer', 'sale_details.product.unit']);
        $payment = $sale->payment;
        return [
            'id' => $sale->id, 'sale_no' => $sale->sale_no, 'date' => $sale->date->toDateString(),
            'description' => $sale->description, 'created_by' => $sale->created_by,
            'cancelled_at' => $sale->cancelled_at?->toISOString(), 'currency' => 'GHS',
            'customer' => $payment?->customer ? ['id' => $payment->customer->id, 'name' => $payment->customer->name] : null,
            'payment' => $payment ? [
                'paid_status' => $payment->paid_status, 'total_amount' => number_format((float) $payment->total_amount, 2, '.', ''),
                'paid_amount' => number_format((float) $payment->paid_amount, 2, '.', ''),
                'due_amount' => number_format((float) $payment->due_amount, 2, '.', ''),
                'discount_amount' => number_format((float) $payment->discount_amount, 2, '.', ''),
                'taxable_amount' => number_format((float) $payment->taxable_amount, 2, '.', ''),
                'nhil_amount' => number_format((float) $payment->nhil_amount, 2, '.', ''),
                'getfund_amount' => number_format((float) $payment->getfund_amount, 2, '.', ''),
                'vat_amount' => number_format((float) $payment->vat_amount, 2, '.', ''),
                'cash_tendered' => number_format((float) $payment->cash_tendered, 2, '.', ''),
                'change_due' => number_format((float) $payment->change_due, 2, '.', ''),
            ] : null,
            'items' => $sale->sale_details->map(fn ($line) => [
                'product_id' => $line->product_id, 'product_name' => $line->product?->name,
                'quantity' => (float) $line->selling_qty, 'unit' => $line->product?->unit?->name,
                'unit_selling_price' => number_format((float) $line->unit_selling_price, 2, '.', ''),
                'line_total' => number_format((float) $line->selling_price, 2, '.', ''),
            ])->all(),
            'receipt' => ['printed_at' => $sale->printed_at?->toISOString(), 'delivered_at' => $sale->delivered_at?->toISOString()],
        ];
    }
}