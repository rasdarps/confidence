@extends('layout.main')
@section('title') {{ 'Edit Roles' }} @endsection

@section('content')

    <!--Validator link-->
    <script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>

    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header with-border role-page-header">
                            <div>
                                <span class="card-title" style="font-size:20px;">Edit | Role </span>
                                <p class="text-muted mb-0 mt-1">Update role access and keep permissions grouped by business area.</p>
                            </div>

                            <a href="{{ route('roles.index') }}" style="float:right">
                                <button type="button" class="btn btn-primary modal_btn">View Roles</button>
                            </a>
                        </div>

                        <div class="card-body px-4 py-4">
                            @if (count($errors) > 0)
                                <div class="alert alert-danger">
                                    <strong>Whoops!</strong> There were some problems with your input.<br><br>
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form action="{{ route('roles.update', Crypt::encrypt($role->id)) }}" method="POST" id="roleForm">
                                @csrf
                                @method('PATCH')

                                @php
                                    $selectedPermissions = old('permission', $rolePermissions);
                                    $permission = $permission->unique('name')->sortBy('name')->values();

                                    $permissionGroups = [
                                        'User and Role Management' => collect(),
                                        'Products Management Permissions' => collect(),
                                        'Supplier Management Permissions' => collect(),
                                        'Customers Management Permissions' => collect(),
                                        'Sales Management Permissions' => collect(),
                                        'Purchases Management Permissions' => collect(),
                                        'Accounting' => collect(),
                                        'Expense Management Permissions' => collect(),
                                        'Stock Adjustment Permissions' => collect(),
                                        'Company Management Permissions' => collect(),
                                        'Product Price Permissions' => collect(),
                                        'Cash Reconciliation Permissions' => collect(),
                                        'Payment Management Permissions' => collect(),
                                        'Report Permissions' => collect(),
                                        'Stock Report Permissions' => collect(),
                                        'Dashboard Permissions' => collect(),
                                        'Settings Permissions' => collect(),
                                        'Other Permissions' => collect(),
                                    ];

                                    $groupMatchers = [
                                        // Keep User and Role Management grouping intact.
                                        'User and Role Management' => function (string $name): bool {
                                            return str_starts_with($name, 'user-') || str_starts_with($name, 'role-');
                                        },
                                        'Products Management Permissions' => function (string $name): bool {
                                            return str_starts_with($name, 'product-')
                                                || str_starts_with($name, 'unit-')
                                                || str_starts_with($name, 'category-');
                                        },
                                        'Supplier Management Permissions' => function (string $name): bool {
                                            return str_starts_with($name, 'supplier-');
                                        },
                                        'Customers Management Permissions' => function (string $name): bool {
                                            return str_starts_with($name, 'customer-');
                                        },
                                        'Sales Management Permissions' => function (string $name): bool {
                                            return str_starts_with($name, 'sale-');
                                        },
                                        'Purchases Management Permissions' => function (string $name): bool {
                                            return str_starts_with($name, 'purchase-');
                                        },
                                        'Accounting' => function (string $name): bool {
                                            return str_starts_with($name, 'accounting-')
                                                || in_array($name, [
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
                                                    'accounting-fiscal-period-list',
                                                    'accounting-fiscal-period-close',
                                                    'accounting-journal-list',
                                                    'accounting-journal-post',
                                                    'accounting-journal-reverse',
                                                    'accounting-coa-list',
                                                    'accounting-coa-create',
                                                    'accounting-coa-edit',
                                                ], true);
                                        },
                                        'Expense Management Permissions' => function (string $name): bool {
                                            return str_starts_with($name, 'expense-')
                                                || str_starts_with($name, 'expense-category-');
                                        },
                                        'Stock Adjustment Permissions' => function (string $name): bool {
                                            return str_starts_with($name, 'stock-adjustment-')
                                                || in_array($name, ['stock_adjustment-print', 'stock_adjustment_print'], true);
                                        },
                                        'Company Management Permissions' => function (string $name): bool {
                                            return str_starts_with($name, 'company-');
                                        },
                                        'Product Price Permissions' => function (string $name): bool {
                                            return str_starts_with($name, 'product-price-');
                                        },
                                        'Cash Reconciliation Permissions' => function (string $name): bool {
                                            return str_starts_with($name, 'cash-reconciliation-');
                                        },
                                        'Payment Management Permissions' => function (string $name): bool {
                                            return str_starts_with($name, 'payment-');
                                        },
                                        'Report Permissions' => function (string $name): bool {
                                            return str_starts_with($name, 'report-') && $name !== 'report-stock-adjustment';
                                        },
                                        'Stock Report Permissions' => function (string $name): bool {
                                            return in_array($name, [
                                                'report-stock-adjustment',
                                                'stock-report',
                                                'stock-ledger-report',
                                                'stock-product-trail-report',
                                                'stock-value-report',
                                            ], true);
                                        },
                                        'Dashboard Permissions' => function (string $name): bool {
                                            return $name === 'dashboard-view';
                                        },
                                        'Settings Permissions' => function (string $name): bool {
                                            return in_array($name, [
                                                'settings-manage',
                                                'daily-transaction-email-send',
                                                'user-audit-view',
                                                'user-filter-view',
                                            ], true);
                                        },
                                    ];

                                    foreach ($permission as $perm) {
                                        $groupName = 'Other Permissions';

                                        foreach ($groupMatchers as $candidateGroup => $matcher) {
                                            if ($matcher($perm->name)) {
                                                $groupName = $candidateGroup;
                                                break;
                                            }
                                        }

                                        $permissionGroups[$groupName]->push($perm);
                                    }

                                    $permissionLabelMap = [
                                        'cash-reconciliation-list' => 'View Cash Reconciliation List',
                                        'cash-reconciliation-show' => 'View Cash Reconciliation Details',
                                        'cash-reconciliation-create' => 'Create Cash Reconciliation',
                                        'cash-reconciliation-edit' => 'Edit Cash Reconciliation',
                                        'cash-reconciliation-approve' => 'Approve Cash Reconciliation',
                                        'cash-reconciliation-reverse-approval' => 'Reverse Cash Reconciliation Approval',
                                        'payment-sales-cash-list' => 'View Cash Sales Payments',
                                        'payment-sales-credit-list' => 'View Credit Sales Payments',
                                        'payment-sales-credit-pay' => 'Pay Credit Sales Invoice',
                                        'payment-purchases-cash-list' => 'View Cash Purchase Payments',
                                        'payment-purchases-credit-list' => 'View Credit Purchase Payments',
                                        'payment-purchases-credit-pay' => 'Pay Credit Purchase Invoice',
                                        'sale-list' => 'View Sales',
                                        'sale-create' => 'Create Sales',
                                        'sale-cancel' => 'Cancel Sale',
                                        'sale-print' => 'Print Sale',
                                        'purchase-list' => 'View Purchases',
                                        'purchase-create' => 'Create Purchase',
                                        'purchase-edit' => 'Edit Purchase',
                                        'purchase-cancel' => 'Cancel Purchase',
                                        'purchase-print' => 'Print Purchase',
                                        'accounting-dashboard-view' => 'View Accounting Dashboard',
                                        'accounting-coa-list' => 'View Accounting Coa',
                                        'accounting-coa-create' => 'Create Accounting Coa',
                                        'accounting-coa-edit' => 'Edit Accounting Coa',
                                        'accounting-journal-list' => 'View Accounting Journal',
                                        'accounting-journal-post' => 'Post Accounting Journal',
                                        'accounting-journal-reverse' => 'Reverse Accounting Journal',
                                        'accounting-fiscal-period-list' => 'View Accounting Fiscal Period',
                                        'accounting-fiscal-period-close' => 'Close Accounting Fiscal Period',
                                        'bank-account-list' => 'View Bank Account',
                                        'bank-account-create' => 'Create Bank Account',
                                        'bank-account-edit' => 'Edit Bank Account',
                                        'bank-account-delete' => 'Delete Bank Account',
                                        'bank-transaction-list' => 'View Bank Transaction',
                                        'bank-transaction-create' => 'Create Bank Transaction',
                                        'bank-transaction-edit' => 'Edit Bank Transaction',
                                        'bank-transaction-delete' => 'Delete Bank Transaction',
                                        'capital-loan-list' => 'View Capital Loan',
                                        'capital-loan-create' => 'Create Capital Loan',
                                        'capital-loan-edit' => 'Edit Capital Loan',
                                        'capital-loan-delete' => 'Delete Capital Loan',
                                        'bank-reconciliation-run' => 'Run Bank Reconciliation',
                                        'report-sales-lines' => 'View Sales Lines Report',
                                        'report-purchase-lines' => 'View Purchase Lines Report',
                                        'report-sales-cash-movement' => 'View Sales Cash Movement Report',
                                        'report-purchase-cash-movement' => 'View Purchase Cash Movement Report',
                                        'report-sales-credit-movement' => 'View Sales Credit Movement Report',
                                        'report-purchase-credit-movement' => 'View Purchase Credit Movement Report',
                                        'stock-product-trail-report' => 'View Product-wise Stock Trail Report',
                                    ];

                                    $permissionDescriptionMap = [
                                        'accounting-dashboard-view' => 'Open the accounting dashboard and overview panels.',
                                        'accounting-coa-list' => 'View the chart of accounts list.',
                                        'accounting-coa-create' => 'Create new chart of account entries.',
                                        'accounting-coa-edit' => 'Edit existing chart of account entries.',
                                        'accounting-journal-list' => 'View journal entries and journal history.',
                                        'accounting-journal-post' => 'Post journal entries to the ledger.',
                                        'accounting-journal-reverse' => 'Reverse posted journal entries.',
                                        'accounting-fiscal-period-list' => 'View accounting fiscal periods.',
                                        'accounting-fiscal-period-close' => 'Close an accounting fiscal period.',
                                        'bank-account-list' => 'View bank accounts and their balances.',
                                        'bank-account-create' => 'Create a new bank account record.',
                                        'bank-account-edit' => 'Edit bank account details.',
                                        'bank-account-delete' => 'Delete a bank account record.',
                                        'bank-transaction-list' => 'View bank transactions and transfer history.',
                                        'bank-transaction-create' => 'Create a new bank transaction.',
                                        'bank-transaction-edit' => 'Edit bank transaction details.',
                                        'bank-transaction-delete' => 'Delete a bank transaction record.',
                                        'capital-loan-list' => 'View capital loan entries and records.',
                                        'capital-loan-create' => 'Create a new capital loan entry.',
                                        'capital-loan-edit' => 'Edit capital loan details.',
                                        'capital-loan-delete' => 'Delete a capital loan record.',
                                        'bank-reconciliation-run' => 'Run the bank reconciliation process.',
                                    ];

                                    $formatPermissionLabel = function ($permissionName) use ($permissionLabelMap) {
                                        if (isset($permissionLabelMap[$permissionName])) {
                                            return $permissionLabelMap[$permissionName];
                                        }

                                        if (str_starts_with($permissionName, 'report-')) {
                                            $reportName = str_replace('-', ' ', substr($permissionName, 7));
                                            return 'View ' . ucwords($reportName) . ' Report';
                                        }

                                        $parts = explode('-', $permissionName);
                                        $action = array_pop($parts);
                                        $resource = ucwords(str_replace('-', ' ', implode('-', $parts)));

                                        $actionMap = [
                                            'list' => 'View',
                                            'create' => 'Create',
                                            'edit' => 'Edit',
                                            'update' => 'Update',
                                            'delete' => 'Delete',
                                            'show' => 'View',
                                            'manage' => 'Manage',
                                            'report' => 'View',
                                            'pay' => 'Pay',
                                        ];

                                        $actionLabel = $actionMap[$action] ?? ucwords(str_replace('-', ' ', $action));

                                        if ($action === 'report') {
                                            return $resource . ' Report';
                                        }

                                        return trim($actionLabel . ' ' . $resource);
                                    };

                                    $formatPermissionDescription = function ($permissionName) use ($permissionDescriptionMap) {
                                        if (isset($permissionDescriptionMap[$permissionName])) {
                                            return $permissionDescriptionMap[$permissionName];
                                        }

                                        if (str_starts_with($permissionName, 'report-')) {
                                            $reportName = str_replace('-', ' ', substr($permissionName, 7));
                                            return 'View the ' . strtolower($reportName) . ' report.';
                                        }

                                        $parts = explode('-', $permissionName);
                                        $action = array_pop($parts);
                                        $resource = ucwords(str_replace('-', ' ', implode('-', $parts)));

                                        $descriptionMap = [
                                            'list' => 'View ' . strtolower($resource) . ' records.',
                                            'show' => 'View ' . strtolower($resource) . ' details.',
                                            'create' => 'Create new ' . strtolower($resource) . ' records.',
                                            'edit' => 'Edit existing ' . strtolower($resource) . ' records.',
                                            'update' => 'Update existing ' . strtolower($resource) . ' records.',
                                            'delete' => 'Delete ' . strtolower($resource) . ' records.',
                                            'manage' => 'Manage ' . strtolower($resource) . ' settings.',
                                            'report' => 'View the ' . strtolower($resource) . ' report.',
                                            'pay' => 'Pay ' . strtolower($resource) . ' invoices.',
                                            'post' => 'Post ' . strtolower($resource) . ' entries.',
                                            'reverse' => 'Reverse ' . strtolower($resource) . ' entries.',
                                            'close' => 'Close ' . strtolower($resource) . ' records.',
                                            'run' => 'Run ' . strtolower($resource) . '.',
                                        ];

                                        return $descriptionMap[$action] ?? ('Allows access to ' . strtolower($resource) . '.');
                                    };
                                @endphp

                                <div class="row gx-3">
                                    <div class="col-lg-8 col-md-10 mb-3 mx-auto">
                                        <div class="form-group">
                                            <label for="name"><strong>Role Name: <span class="text-danger">*</span></strong></label>
                                            <input type="text" id="name" name="name" class="form-control" placeholder="Enter role name" value="{{ old('name', $role->name) }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="row gx-3">
                                    <div class="col-lg-11 col-12 mb-3 mx-auto">
                                        <div class="form-group">
                                            <label><strong>Permissions: <span class="text-danger">*</span></strong></label>
                                            <small class="text-muted d-block mb-2">Update the permissions for this role</small>
                                            @foreach($permissionGroups as $groupName => $groupPermissions)
                                                @if($groupPermissions->count() > 0)
                                                    <div class="mb-4 permission-group">
                                                        <div class="permission-group-header mb-3">
                                                            <h6 class="mb-0 fw-bold text-dark">{{ $groupName }}</h6>
                                                            <span class="badge bg-primary-subtle text-primary">{{ $groupPermissions->count() }}</span>
                                                        </div>
                                                        <div class="row g-2">
                                                            @foreach($groupPermissions as $value)
                                                                <div class="col-lg-4 col-md-6 col-12">
                                                                    <div class="permission-item h-100">
                                                                        <div class="form-check m-0">
                                                                        <input class="form-check-input" type="checkbox" name="permission[]" value="{{ $value->id }}" id="perm{{ $value->id }}" {{ in_array($value->id, $selectedPermissions) ? 'checked' : '' }}>
                                                                        <label class="form-check-label" for="perm{{ $value->id }}">
                                                                            {{ $formatPermissionLabel($value->name) }}
                                                                        </label>
                                                                        <small class="text-muted d-block mt-1">{{ $formatPermissionDescription($value->name) }}</small>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12 text-center">
                                        <button type="submit" class="btn btn-success mt-3">Submit</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection

@section('styles')
<style>
    .role-page-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .permission-group {
        padding: 18px;
        border: 1px solid #e9ecef;
        border-radius: 16px;
        background: linear-gradient(180deg, #ffffff 0%, #fbfcfe 100%);
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
    }

    .permission-group-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .permission-item {
        padding: 0.85rem 0.95rem;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        background: #fff;
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
    }

    .permission-item:hover {
        transform: translateY(-1px);
        border-color: #d7dde6;
        box-shadow: 0 6px 14px rgba(15, 23, 42, 0.05);
    }

    .permission-item .form-check-input {
        margin-top: 0.2rem;
    }
</style>
@endsection

