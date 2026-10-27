<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\Request;

abstract class MobileController extends Controller
{
    protected function filters(Request $request): void
    {
        $rules = [
            'page' => ['sometimes', 'integer', 'min:1'], 'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'], 'barcode' => ['nullable', 'string', 'max:50'],
            'from_date' => ['nullable', 'date_format:Y-m-d'], 'to_date' => ['nullable', 'date_format:Y-m-d'],
            'customer_id' => ['nullable', 'integer', 'min:1'], 'supplier_id' => ['nullable', 'integer', 'min:1'],
            'category_id' => ['nullable', 'integer', 'min:1'], 'account_id' => ['nullable', 'integer', 'min:1'],
            'sale_id' => ['nullable', 'integer', 'min:1'], 'payment_status' => ['nullable', 'in:full_payment,partial_payment,full_credit'],
        ];
        if ($request->filled('from_date')) $rules['to_date'][] = 'after_or_equal:from_date';
        $request->validate($rules);
    }

    protected function range($query, string $column, Request $request): void
    {
        if ($request->filled('from_date')) $query->whereDate($column, '>=', $request->input('from_date'));
        if ($request->filled('to_date')) $query->whereDate($column, '<=', $request->input('to_date'));
    }

    protected function salesQuery(Request $request)
    {
        $query = Sale::query();
        if ($request->user()->hasRole('Cashier')) $query->where('created_by', $request->user()->id);
        $this->range($query, 'sales.date', $request);
        if ($request->filled('sale_id')) $query->whereKey($request->integer('sale_id'));
        if ($request->filled('customer_id')) $query->whereHas('payment', fn ($payment) => $payment->where('customer_id', $request->integer('customer_id')));
        if ($request->filled('payment_status')) $query->whereHas('payment', fn ($payment) => $payment->where('paid_status', $request->input('payment_status')));
        return $query;
    }

    protected function paginated($query, Request $request, callable $serialize, array $extra = [])
    {
        $page = $query->paginate($request->integer('per_page', 25));
        return response()->json([
            'data' => $page->getCollection()->map($serialize)->values()->all(),
            'meta' => array_merge([
                'current_page' => $page->currentPage(), 'per_page' => $page->perPage(), 'total' => $page->total(),
                'last_page' => $page->lastPage(), 'from_date' => $request->input('from_date'), 'to_date' => $request->input('to_date'),
            ], $extra),
        ]);
    }
}