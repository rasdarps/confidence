<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $guardName = config('permission.defaults.guard_name', config('auth.defaults.guard', 'web'));

        $permissions = [
            'role-list','role-create','role-edit','role-delete',
            'unit-list','unit-create','unit-edit','unit-delete',
            'category-list','category-create','category-edit','category-delete',
            'user-list','user-create', 'user-edit','user-delete',
        ];
      
        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => $guardName]);
        }

        // Outlets Management Permissions
        $outlet_management_permissions = [
            'outlet-list', 'outlet-create', 'outlet-edit', 'outlet-delete',
        ];
        foreach ($outlet_management_permissions as $outlet_permission) {
            Permission::query()->firstOrCreate(['name' => $outlet_permission, 'guard_name' => $guardName]);
        }

        // Stock Transfers Permissions
        $stock_transfer_permissions = [
            'stock-transfer-list', 'stock-transfer-create', 'stock-transfer-receive', 'stock-transfer-cancel',
        ];
        foreach ($stock_transfer_permissions as $st_permission) {
            Permission::query()->firstOrCreate(['name' => $st_permission, 'guard_name' => $guardName]);
        }

        // Products Management Permissions
        $products_management_permissions = [
            'product-list','product-create','product-edit','product-delete',
        ];
        foreach ($products_management_permissions as $product_permission) {
            Permission::query()->firstOrCreate(['name' => $product_permission, 'guard_name' => $guardName]);
        }

        $supplier_management_permissions = [
            'supplier-list','supplier-create','supplier-edit','supplier-delete',
        ];
        foreach ($supplier_management_permissions as $supplier_permission) {
            Permission::query()->firstOrCreate(['name' => $supplier_permission, 'guard_name' => $guardName]);
        }

        $customers_management_permissions = [ 
            'customer-list','customer-create','customer-edit','customer-delete',
        ];
        foreach ($customers_management_permissions as $customer_permission) {
            Permission::query()->firstOrCreate(['name' => $customer_permission, 'guard_name' => $guardName]);
        }

        $transactions_management_permissions = [ 
            'sale-list','sale-create','sale-cancel','sale-print',
            'sale-receipt-reprint','sale-receipt-audit-view','sale-delivery-verify',
            'purchase-list','purchase-create','purchase-edit','purchase-cancel','purchase-print',
        ];

        $expense_management_permissions = [
            'expense-list','expense-create','expense-edit','expense-delete',
        ];

        $expense_category_permissions = [
            'expense-category-list','expense-category-create','expense-category-edit','expense-category-delete',
        ];

        $stock_adjustment_permissions = [
            'stock-adjustment-list','stock-adjustment-create','stock-adjustment-edit','stock-adjustment-delete','stock_adjustment-print',
        ];

        $company_management_permissions = [
            'company-list','company-create','company-edit','company-delete',
        ];

        $product_price_permissions = [
            'product-price-list','product-price-edit','product-price-delete','product-price-history-list',
        ];

        $cash_reconciliation_permissions = [
            'cash-reconciliation-list','cash-reconciliation-create','cash-reconciliation-edit','cash-reconciliation-show',
            'cash-reconciliation-approve','cash-reconciliation-reverse-approval',
        ];

        $payment_management_permissions = [
            'payment-sales-cash-list',
            'payment-sales-credit-list',
            'payment-sales-credit-pay',
            'payment-purchases-cash-list',
            'payment-purchases-credit-list',
            'payment-purchases-credit-pay',
        ];

        $report_permissions = [
            'report-profit','report-expense','report-stock-adjustment','report-summary-ledger',
            'report-sales-lines','report-purchase-lines','report-purchase-edit-trail',
            'report-sales-cash-movement','report-purchase-cash-movement',
            'report-sales-credit-movement','report-purchase-credit-movement',
        ];

        $stock_report_permissions = [
            'stock-report','stock-ledger-report','stock-product-trail-report','stock-value-report',
        ];

        $dashboard_permissions = [
            'dashboard-view',
        ];

        $settings_permissions = [
            'settings-manage',
            'chat-use',
            'daily-transaction-email-send',
            'user-audit-view',
            'user-filter-view',
        ];

        $accounting_permissions = [
            'accounting-dashboard-view',
            'accounting-coa-list',
            'accounting-coa-create',
            'accounting-coa-edit',
            'accounting-journal-list',
            'accounting-journal-post',
            'accounting-journal-reverse',
            'accounting-fiscal-period-list',
            'accounting-fiscal-period-close',
            'bank-account-list',
            'bank-account-create',
            'bank-account-edit',
            'bank-account-delete',
            'bank-transaction-list',
            'bank-transaction-create',
            'bank-transaction-edit',
            'bank-transaction-delete',
            'capital-loan-list',
            'capital-loan-create',
            'capital-loan-edit',
            'capital-loan-delete',
            'bank-reconciliation-run',
            'report-trial-balance',
            'report-balance-sheet',
            'report-income-statement',
            'report-cash-flow',
            'report-general-ledger',
            'report-ar-aging',
            'report-ap-aging',
        ];

        foreach ($transactions_management_permissions as $transaction_permission) {
            Permission::query()->firstOrCreate(['name' => $transaction_permission, 'guard_name' => $guardName]);
        }

        foreach ($expense_management_permissions as $expense_permission) {
            Permission::query()->firstOrCreate(['name' => $expense_permission, 'guard_name' => $guardName]);
        }

        foreach ($expense_category_permissions as $expense_category_permission) {
            Permission::query()->firstOrCreate(['name' => $expense_category_permission, 'guard_name' => $guardName]);
        }

        foreach ($stock_adjustment_permissions as $stock_adjustment_permission) {
            Permission::query()->firstOrCreate(['name' => $stock_adjustment_permission, 'guard_name' => $guardName]);
        }

        foreach ($company_management_permissions as $company_permission) {
            Permission::query()->firstOrCreate(['name' => $company_permission, 'guard_name' => $guardName]);
        }

        foreach ($product_price_permissions as $product_price_permission) {
            Permission::query()->firstOrCreate(['name' => $product_price_permission, 'guard_name' => $guardName]);
        }

        foreach ($cash_reconciliation_permissions as $cash_reconciliation_permission) {
            Permission::query()->firstOrCreate(['name' => $cash_reconciliation_permission, 'guard_name' => $guardName]);
        }

        foreach ($payment_management_permissions as $payment_management_permission) {
            Permission::query()->firstOrCreate(['name' => $payment_management_permission, 'guard_name' => $guardName]);
        }

        foreach ($report_permissions as $report_permission) {
            Permission::query()->firstOrCreate(['name' => $report_permission, 'guard_name' => $guardName]);
        }

        foreach ($stock_report_permissions as $stock_report_permission) {
            Permission::query()->firstOrCreate(['name' => $stock_report_permission, 'guard_name' => $guardName]);
        }

        foreach ($dashboard_permissions as $dashboard_permission) {
            Permission::query()->firstOrCreate(['name' => $dashboard_permission, 'guard_name' => $guardName]);
        }

        foreach ($settings_permissions as $settings_permission) {
            Permission::query()->firstOrCreate(['name' => $settings_permission, 'guard_name' => $guardName]);
        }

        foreach ($accounting_permissions as $accounting_permission) {
            Permission::query()->firstOrCreate(['name' => $accounting_permission, 'guard_name' => $guardName]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Roles
        $role = Role::findOrCreate('Super Admin', $guardName);
        $manager_role = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => $guardName]);
        $cashier_role = Role::firstOrCreate(['name' => 'Cashier', 'guard_name' => $guardName]);
        $supervisor_role = Role::firstOrCreate(['name' => 'Supervisor', 'guard_name' => $guardName]);

        $seedManagerDefaults = $manager_role->wasRecentlyCreated;
        $seedCashierDefaults = $cashier_role->wasRecentlyCreated;
        $seedSupervisorDefaults = $supervisor_role->wasRecentlyCreated;

        foreach ($products_management_permissions as $product_permission) {
            $this->grantPermission($role, $product_permission, $guardName);
            if ($seedManagerDefaults) $this->grantPermission($manager_role, $product_permission, $guardName);
        }

        foreach ($supplier_management_permissions as $supplier_permission) {
            $this->grantPermission($role, $supplier_permission, $guardName);
            if ($seedManagerDefaults) $this->grantPermission($manager_role, $supplier_permission, $guardName);
        }

        foreach ($customers_management_permissions as $customer_permission) {
            $this->grantPermission($role, $customer_permission, $guardName);
            if ($seedManagerDefaults) $this->grantPermission($manager_role, $customer_permission, $guardName);
        }

        foreach ($transactions_management_permissions as $transaction_permission) {
            $this->grantPermission($role, $transaction_permission, $guardName);
            if ($seedManagerDefaults) $this->grantPermission($manager_role, $transaction_permission, $guardName);
        }

        foreach ($expense_management_permissions as $expense_permission) {
            $this->grantPermission($role, $expense_permission, $guardName);
            if ($seedManagerDefaults) $this->grantPermission($manager_role, $expense_permission, $guardName);
        }

        foreach ($expense_category_permissions as $expense_category_permission) {
            $this->grantPermission($role, $expense_category_permission, $guardName);
            if ($seedManagerDefaults) $this->grantPermission($manager_role, $expense_category_permission, $guardName);
        }

        foreach ($stock_adjustment_permissions as $stock_adjustment_permission) {
            $this->grantPermission($role, $stock_adjustment_permission, $guardName);
            if ($seedManagerDefaults) $this->grantPermission($manager_role, $stock_adjustment_permission, $guardName);
        }

        foreach ($permissions as $permission) {
            $this->grantPermission($role, $permission, $guardName);
        }

        // Additional permissions to Super Admin
        $additional_permissions = array_merge(
            $outlet_management_permissions,
            $stock_transfer_permissions,
            $company_management_permissions,
            $product_price_permissions,
            $cash_reconciliation_permissions,
            $payment_management_permissions,
            $report_permissions,
            $stock_report_permissions,
            $dashboard_permissions,
            $settings_permissions,
            $accounting_permissions
        );

        foreach ($additional_permissions as $additional_permission) {
            $this->grantPermission($role, $additional_permission, $guardName);
        }

        // Manager permissions
        $manager_additional_permissions = array_merge(
            $outlet_management_permissions,
            $stock_transfer_permissions,
            $report_permissions,
            $stock_report_permissions,
            $payment_management_permissions,
            $dashboard_permissions,
            $settings_permissions,
            $accounting_permissions,
            [
                'cash-reconciliation-show',
                'cash-reconciliation-approve',
                'cash-reconciliation-reverse-approval',
            ]
        );

        foreach ($manager_additional_permissions as $manager_permission) {
            if ($seedManagerDefaults) $this->grantPermission($manager_role, $manager_permission, $guardName);
        }

        // Cashier permissions
        $cashier_permissions = [
            'sale-list',
            'sale-create',
            'sale-print',
            'sale-receipt-reprint',
            'stock-transfer-list',
            'stock-transfer-receive',
            'cash-reconciliation-list',
            'cash-reconciliation-create',
            'cash-reconciliation-edit',
            'payment-sales-cash-list',
            'payment-sales-credit-list',
            'payment-sales-credit-pay',
        ];

        foreach ($cashier_permissions as $cashier_permission) {
            if ($seedCashierDefaults) $this->grantPermission($cashier_role, $cashier_permission, $guardName);
        }

        // Supervisor permissions
        $supervisor_permissions = [
            'stock-transfer-list',
            'stock-transfer-receive',
            'cash-reconciliation-list',
            'cash-reconciliation-show',
            'cash-reconciliation-approve',
            'cash-reconciliation-reverse-approval',
            'payment-sales-cash-list',
            'payment-sales-credit-list',
            'payment-purchases-cash-list',
            'payment-purchases-credit-list',
            'user-audit-view',
            'user-filter-view',
            'sale-receipt-audit-view',
            'sale-delivery-verify',
        ];

        foreach ($supervisor_permissions as $supervisor_permission) {
            if ($seedSupervisorDefaults) $this->grantPermission($supervisor_role, $supervisor_permission, $guardName);
        }
    }

    private function grantPermission(Role $role, string $permissionName, string $guardName): void
    {
        $permission = Permission::query()->firstOrCreate([
            'name' => $permissionName,
            'guard_name' => $guardName,
        ]);

        $role->givePermissionTo($permission);
    }
}
