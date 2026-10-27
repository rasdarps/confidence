<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class MobileUserResource extends JsonResource
{
    public function toArray($request): array
    {
        $permissions = [
            'sales_list' => ['sale-list'], 'sales_create' => ['sale-create'],
            'product_lookup' => ['product-list', 'sale-create', 'purchase-create'],
            'customer_lookup' => ['customer-list', 'sale-create'], 'customer_details' => ['customer-list'],
            'credit_list' => ['payment-sales-credit-list'], 'paid_list' => ['payment-sales-cash-list'],
            'customer_payment' => ['payment-sales-credit-pay'], 'supplier_lookup' => ['supplier-list', 'purchase-create'],
            'purchases_list' => ['purchase-list'], 'purchases_create' => ['purchase-create'],
            'expenses_list' => ['expense-list'], 'expenses_create' => ['expense-create'],
            'expense_categories' => ['expense-category-list', 'expense-create', 'expense-list'],
            'bank_accounts' => ['bank-account-list'], 'bank_transactions' => ['bank-transaction-list'],
            'dashboard' => ['dashboard-view'], 'sales_report' => ['sale-list', 'report-sales-lines'],
            'collections_report' => ['report-sales-cash-movement'], 'stock_report' => ['stock-report'],
            'stock_value' => ['stock-value-report'], 'expenses_report' => ['report-expense'],
        ];
        $capabilities = [];
        foreach ($permissions as $capability => $required) {
            $capabilities[$capability] = collect($required)->contains(fn ($permission) => $this->resource->can($permission));
        }
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'user_type' => $this->user_type,
            'must_change_password' => (bool) $this->must_change_password,
            'roles' => $this->getRoleNames()->values(),
            'permissions' => $this->getAllPermissions()->pluck('name')->unique()->values(),
            'capabilities' => $capabilities,
            'pos_settings' => [
                'allow_negative_stock' => (bool) config('pos.allow_negative_stock'),
                'prevent_backdated_invoices' => (bool) config('pos.prevent_backdated_invoices'),
                'allow_manual_discount_entry_by_staff' => (bool) config('pos.allow_manual_discount_entry_by_staff'),
                'enforce_previous_day_reconciliation' => (bool) config('pos.enforce_previous_day_reconciliation'),
            ],
        ];
    }
}