@extends('layout.main')
@section('title') {{ 'Create Role' }} @endsection

@section('content')

    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header with-border role-page-header">
                            <div>
                                <span class="card-title font-size-20 font-weight-bold">Create | Role </span>
                                <p class="text-muted mb-0 mt-1">Define enterprise role access and configure permissions across Central HQ and branch stations.</p>
                            </div>

                            <a href="{{ route('roles.index') }}" class="btn btn-modern-header">
                                <i class="fas fa-arrow-left me-1 mr-1"></i> View All Roles
                            </a>
                        </div>

                        <div class="card-body px-4 py-4">

                            <!-- Multi-Outlet Role Governance Guidance -->
                            <div class="alert alert-info border-0 shadow-sm mb-4" style="background: #f0fdfa; border-left: 4px solid #0F766E !important; color: #134e4a;">
                                <div class="d-flex align-items-start">
                                    <div class="mr-3 me-3 pt-1">
                                        <i class="fas fa-shield-alt fa-2x" style="color: #0F766E;"></i>
                                    </div>
                                    <div>
                                        <h6 class="font-weight-bold mb-1" style="color: #0F766E;">Enterprise Multi-Outlet Role Governance</h6>
                                        <p class="mb-0 font-size-13" style="color: #334155; line-height: 1.55;">
                                            <strong>How Roles Interact with Outlets:</strong> Role permissions define <em>what functional capabilities</em> a user has (such as sales, purchases, stock adjustments, or reconciliations). 
                                            A user's <strong>Assigned Outlet</strong> then determines <em>where</em> those permissions apply:
                                            staff assigned to a <strong>Branch Station</strong> operate strictly within their station inventory and daily shifts, while staff assigned to <strong>Central Branch (HQ)</strong> operate with enterprise-wide visibility across all branch stations.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <form action="{{ route('roles.store') }}" method="POST" id="roleForm" novalidate>
                                @csrf

                                <div class="row gx-3">
                                    <div class="col-lg-8 col-md-10 mb-4 mx-auto">
                                        <div class="form-group">
                                            <label for="name" class="font-weight-bold text-dark font-size-14">
                                                Role Name <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" id="name" name="name" class="form-control form-control-lg modern-input" placeholder="e.g. Branch Manager, Cashier, Inventory Auditor, Chief Accountant" value="{{ old('name') }}" autocomplete="off">
                                            <small class="text-muted mt-1 d-block">Enter a clear, descriptive role name.</small>
                                        </div>
                                    </div>
                                </div>

                                @php
                                    $permission = $permission->unique('name')->sortBy('name')->values();

                                    $permissionGroups = [
                                        'Multi-Outlet & Branch Management' => collect(),
                                        'Stock Transfer & Inter-Branch Movement' => collect(),
                                        'User and Role Management' => collect(),
                                        'Product Price Permissions' => collect(),
                                        'Products Management Permissions' => collect(),
                                        'Supplier Management Permissions' => collect(),
                                        'Customers Management Permissions' => collect(),
                                        'Sales Management Permissions' => collect(),
                                        'Purchases Management Permissions' => collect(),
                                        'Accounting' => collect(),
                                        'Expense Management Permissions' => collect(),
                                        'Stock Adjustment Permissions' => collect(),
                                        'Company Management Permissions' => collect(),
                                        'Cash Reconciliation Permissions' => collect(),
                                        'Payment Management Permissions' => collect(),
                                        'Report Permissions' => collect(),
                                        'Stock Report Permissions' => collect(),
                                        'Dashboard Permissions' => collect(),
                                        'Settings Permissions' => collect(),
                                        'Other Permissions' => collect(),
                                    ];

                                    $groupMatchers = [
                                        'Multi-Outlet & Branch Management' => function (string $name): bool {
                                            return str_starts_with($name, 'outlet-');
                                        },
                                        'Stock Transfer & Inter-Branch Movement' => function (string $name): bool {
                                            return str_starts_with($name, 'stock-transfer-');
                                        },
                                        'User and Role Management' => function (string $name): bool {
                                            return str_starts_with($name, 'user-') || str_starts_with($name, 'role-');
                                        },
                                        'Product Price Permissions' => function (string $name): bool {
                                            return str_starts_with($name, 'product-price-');
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
                                                    'accounting-fiscal-period-create',
                                                    'accounting-fiscal-period-edit',
                                                    'accounting-fiscal-period-reopen',
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
                                            return str_starts_with($name, 'dashboard-');
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
                                        'outlet-list' => 'View Outlets / Branches',
                                        'outlet-create' => 'Create Outlet / Branch',
                                        'outlet-edit' => 'Edit Outlet / Branch Details',
                                        'outlet-delete' => 'Delete / Deactivate Outlet',
                                        'stock-transfer-list' => 'View Stock Transfers',
                                        'stock-transfer-create' => 'Dispatch Stock Transfer',
                                        'stock-transfer-receive' => 'Confirm & Receive Stock Transfer',
                                        'stock-transfer-cancel' => 'Cancel In-Transit Stock Transfer',
                                        'dashboard-view' => 'View Dashboard Overview',
                                        'dashboard-outlet-filter' => 'Filter All Outlets on Dashboard',
                                        'sale-delivery-verify' => 'Verify Sale Delivery',
                                        'sale-receipt-audit-view' => 'View Receipt Audit Trail',
                                        'sale-receipt-reprint' => 'Reprint Sales Receipts',
                                        'chat-use' => 'Access Business Assistant Chat',
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
                                        'sale-list' => 'View Sales Records',
                                        'sale-create' => 'Create New Sales',
                                        'sale-cancel' => 'Cancel Completed Sale',
                                        'sale-print' => 'Print Sale Invoices',
                                        'purchase-list' => 'View Purchases Records',
                                        'purchase-create' => 'Create New Purchase',
                                        'purchase-edit' => 'Edit Existing Purchase',
                                        'purchase-cancel' => 'Cancel Purchase',
                                        'purchase-print' => 'Print Purchase Invoice',
                                        'accounting-dashboard-view' => 'View Accounting Dashboard',
                                        'accounting-coa-list' => 'View Chart of Accounts',
                                        'accounting-coa-create' => 'Create Account in Chart of Accounts',
                                        'accounting-coa-edit' => 'Edit Chart of Accounts',
                                        'accounting-journal-list' => 'View General Journal Entries',
                                        'accounting-journal-post' => 'Post Manual Journal Entry',
                                        'accounting-journal-reverse' => 'Reverse Posted Journal Entry',
                                        'accounting-fiscal-period-list' => 'View Fiscal Periods',
                                        'accounting-fiscal-period-create' => 'Create Fiscal Period',
                                        'accounting-fiscal-period-edit' => 'Edit Fiscal Period',
                                        'accounting-fiscal-period-close' => 'Close Fiscal Period',
                                        'accounting-fiscal-period-reopen' => 'Reopen Closed Fiscal Period',
                                        'bank-account-list' => 'View Bank Accounts',
                                        'bank-account-create' => 'Create Bank Account',
                                        'bank-account-edit' => 'Edit Bank Account',
                                        'bank-account-delete' => 'Delete Bank Account',
                                        'bank-transaction-list' => 'View Bank Transactions',
                                        'bank-transaction-create' => 'Record Bank Transaction',
                                        'bank-transaction-edit' => 'Edit Bank Transaction',
                                        'bank-transaction-delete' => 'Delete Bank Transaction',
                                        'capital-loan-list' => 'View Capital / Loan Entries',
                                        'capital-loan-create' => 'Record Capital / Loan Entry',
                                        'capital-loan-edit' => 'Edit Capital / Loan Entry',
                                        'capital-loan-delete' => 'Delete Capital / Loan Entry',
                                        'bank-reconciliation-run' => 'Perform Bank Reconciliation',
                                        'report-sales-lines' => 'View Sales Lines Report',
                                        'report-purchase-lines' => 'View Purchase Lines Report',
                                        'report-sales-cash-movement' => 'View Sales Cash Movement Report',
                                        'report-purchase-cash-movement' => 'View Purchase Cash Movement Report',
                                        'report-sales-credit-movement' => 'View Sales Credit Movement Report',
                                        'report-purchase-credit-movement' => 'View Purchase Credit Movement Report',
                                        'stock-product-trail-report' => 'View Product-wise Stock Trail Report',
                                    ];

                                    $permissionDescriptionMap = [
                                        'outlet-list' => 'View list of branch stations and central headquarters.',
                                        'outlet-create' => 'Create new branch stations or define central warehouse facility.',
                                        'outlet-edit' => 'Modify outlet codes, addresses, phone contacts, and active status.',
                                        'outlet-delete' => 'Deactivate or remove branch station records from system.',
                                        'stock-transfer-list' => 'View inter-branch stock dispatch notes and incoming transfer shipments.',
                                        'stock-transfer-create' => 'Initiate and dispatch inventory shipments to another outlet.',
                                        'stock-transfer-receive' => 'Accept and verify incoming stock transfer shipments into station inventory.',
                                        'stock-transfer-cancel' => 'Cancel pending transfer shipment and return inventory to dispatching origin.',
                                        'dashboard-view' => 'Access general dashboard overview and daily summary widgets.',
                                        'dashboard-outlet-filter' => 'Permits switching branch filter views and viewing aggregated multi-outlet analytics on the dashboard.',
                                        'sale-delivery-verify' => 'Inspect and verify delivery of goods against sales invoice receipts.',
                                        'sale-receipt-audit-view' => 'Review historical receipt generation and print audit logs.',
                                        'sale-receipt-reprint' => 'Reissue and reprint past sales receipts and customer invoices.',
                                        'chat-use' => 'Access the internal AI business assistant for operational queries.',
                                        'accounting-dashboard-view' => 'Open the accounting dashboard and financial overview panels.',
                                        'accounting-coa-list' => 'View the chart of accounts structure and ledger balances.',
                                        'accounting-coa-create' => 'Create new chart of account entries.',
                                        'accounting-coa-edit' => 'Edit existing chart of account codes and classifications.',
                                        'accounting-journal-list' => 'View manual and automated double-entry journal records.',
                                        'accounting-journal-post' => 'Post manual adjusting and opening journal entries.',
                                        'accounting-journal-reverse' => 'Reverse posted journal entries with audit trails.',
                                        'accounting-fiscal-period-list' => 'View accounting fiscal periods and locked status.',
                                        'accounting-fiscal-period-close' => 'Close an active accounting fiscal period.',
                                        'accounting-fiscal-period-reopen' => 'Reopen a previously closed fiscal period for adjustments.',
                                        'bank-account-list' => 'View corporate bank accounts and verified balances.',
                                        'bank-account-create' => 'Create a new corporate bank account record.',
                                        'bank-account-edit' => 'Edit bank account numbers, branches, and currency settings.',
                                        'bank-account-delete' => 'Delete an unlinked bank account record.',
                                        'bank-transaction-list' => 'View bank transactions, deposits, and withdrawal history.',
                                        'bank-transaction-create' => 'Record deposits, withdrawals, and bank fund transfers.',
                                        'bank-transaction-edit' => 'Edit bank transaction details and payment references.',
                                        'bank-transaction-delete' => 'Delete a bank transaction entry.',
                                        'capital-loan-list' => 'View equity capital contributions and borrowings.',
                                        'capital-loan-create' => 'Record new capital injections or loan receipts.',
                                        'capital-loan-edit' => 'Edit capital and loan entry terms.',
                                        'capital-loan-delete' => 'Delete capital or loan entries.',
                                        'bank-reconciliation-run' => 'Reconcile bank ledger transactions with bank statements.',
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
                                            'post' => 'Post',
                                            'reverse' => 'Reverse',
                                            'close' => 'Close',
                                            'reopen' => 'Reopen',
                                            'cancel' => 'Cancel',
                                            'receive' => 'Receive',
                                            'verify' => 'Verify',
                                            'reprint' => 'Reprint',
                                            'run' => 'Run',
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
                                            'delete' => 'Delete or deactivate ' . strtolower($resource) . ' records.',
                                            'manage' => 'Manage ' . strtolower($resource) . ' settings.',
                                            'report' => 'View the ' . strtolower($resource) . ' report.',
                                            'pay' => 'Pay ' . strtolower($resource) . ' invoices.',
                                            'post' => 'Post ' . strtolower($resource) . ' entries.',
                                            'reverse' => 'Reverse ' . strtolower($resource) . ' entries.',
                                            'close' => 'Close ' . strtolower($resource) . ' records.',
                                            'reopen' => 'Reopen ' . strtolower($resource) . ' records.',
                                            'receive' => 'Receive and verify incoming ' . strtolower($resource) . ' items.',
                                            'cancel' => 'Cancel pending ' . strtolower($resource) . ' documents.',
                                            'verify' => 'Verify ' . strtolower($resource) . ' operations.',
                                            'reprint' => 'Reprint ' . strtolower($resource) . ' documents.',
                                            'run' => 'Run ' . strtolower($resource) . '.',
                                        ];

                                        return $descriptionMap[$action] ?? ('Allows access to ' . strtolower($resource) . '.');
                                    };
                                @endphp

                                <div class="row gx-3">
                                    <div class="col-lg-11 col-12 mb-3 mx-auto">
                                        
                                        <!-- Global Permissions Controls -->
                                        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom flex-wrap gap-2">
                                            <div>
                                                <label class="font-weight-bold text-dark font-size-16 mb-0 d-flex align-items-center gap-2">
                                                    <span>Module Permissions</span>
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <small class="text-muted d-block mt-1">Select the specific capabilities granted to this role. Staff at branch stations operate within their assigned outlet.</small>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <button type="button" class="btn btn-modern-pill-success" id="btnSelectAllGlobal">
                                                    <i class="fas fa-check-double mr-1 me-1"></i> Select All Permissions
                                                </button>
                                                <button type="button" class="btn btn-modern-pill-danger" id="btnDeselectAllGlobal">
                                                    <i class="fas fa-times-circle mr-1 me-1"></i> Deselect All
                                                </button>
                                            </div>
                                        </div>

                                        @foreach($permissionGroups as $groupName => $groupPermissions)
                                            @if($groupPermissions->count() > 0)
                                                @php
                                                    $groupSlug = 'group-' . \Illuminate\Support\Str::slug($groupName);
                                                @endphp
                                                <div class="mb-4 permission-group {{ $groupSlug }}">
                                                    <div class="permission-group-header mb-3">
                                                        <div class="d-flex align-items-center gap-2">
                                                            @if(str_contains($groupName, 'Multi-Outlet') || str_contains($groupName, 'Stock Transfer'))
                                                                <i class="fas fa-network-wired text-primary mr-2 me-2 font-size-16"></i>
                                                            @endif
                                                            <h6 class="mb-0 fw-bold text-dark font-size-15">{{ $groupName }}</h6>
                                                            <span class="badge bg-primary-subtle text-primary font-weight-semibold">{{ $groupPermissions->count() }}</span>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <button type="button" class="btn btn-modern-group-select group-select-all" data-target="{{ $groupSlug }}">
                                                                <i class="fas fa-check-square me-1 mr-1"></i> Select Group
                                                            </button>
                                                            <button type="button" class="btn btn-modern-group-deselect group-deselect-all" data-target="{{ $groupSlug }}">
                                                                <i class="far fa-square me-1 mr-1"></i> Clear
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="row g-2">
                                                        @foreach($groupPermissions as $value)
                                                            <div class="col-lg-4 col-md-6 col-12">
                                                                <div class="permission-item h-100">
                                                                    <div class="form-check m-0">
                                                                        <input class="form-check-input permission-checkbox" type="checkbox" name="permission[]" value="{{ $value->id }}" id="perm{{ $value->id }}" {{ in_array($value->id, old('permission', [])) ? 'checked' : '' }}>
                                                                        <label class="form-check-label font-weight-semibold text-dark" for="perm{{ $value->id }}">
                                                                            {{ $formatPermissionLabel($value->name) }}
                                                                        </label>
                                                                        <small class="text-muted d-block mt-1 line-height-sm">{{ $formatPermissionDescription($value->name) }}</small>
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

                                <div class="row mt-4 pt-3 border-top">
                                    <div class="col-12 text-center">
                                        <a href="{{ route('roles.index') }}" class="btn btn-modern-cancel mr-2 me-2">
                                            <i class="fas fa-arrow-left me-1 mr-1"></i> Cancel
                                        </a>
                                        <button type="submit" id="btnSubmitRole" class="btn btn-modern-primary">
                                            <i class="fas fa-check-circle me-1 mr-1"></i> Save Role
                                        </button>
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
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .permission-group {
        padding: 20px;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #ffffff;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
    }

    .permission-group-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 12px;
    }

    .permission-item {
        padding: 0.85rem 0.95rem;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #f8fafc;
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease, background 0.15s ease;
    }

    .permission-item:hover {
        transform: translateY(-1px);
        border-color: #0F766E;
        background: #ffffff;
        box-shadow: 0 6px 14px rgba(15, 118, 110, 0.08);
    }

    .permission-item .form-check-input {
        margin-top: 0.25rem;
        cursor: pointer;
    }

    .permission-item .form-check-label {
        cursor: pointer;
        font-size: 13.5px;
    }

    .line-height-sm {
        line-height: 1.35;
        font-size: 12px;
    }

    .modern-input {
        border-radius: 10px;
        border: 1.5px solid #cbd5e1;
        transition: all 0.2s ease;
    }

    .modern-input:focus {
        border-color: #0F766E;
        box-shadow: 0 0 0 0.2rem rgba(15, 118, 110, 0.15);
    }

    .is-invalid {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.2) !important;
    }

    /* Modern UI Button Styles */
    .btn-modern-header {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #ffffff;
        color: #0F766E;
        border: 1.5px solid #cbd5e1;
        font-weight: 600;
        font-size: 13.5px;
        border-radius: 8px;
        padding: 8px 18px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none !important;
    }

    .btn-modern-header:hover {
        background: #f0fdfa;
        color: #0F766E;
        border-color: #0F766E;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(15, 118, 110, 0.15);
    }

    .btn-modern-header:active {
        transform: translateY(0);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }

    .btn-modern-pill-success {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #ecfdf5;
        color: #047857;
        border: 1.5px solid #a7f3d0;
        font-weight: 600;
        font-size: 13px;
        border-radius: 8px;
        padding: 6px 14px;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 1px 2px rgba(16, 185, 129, 0.08);
    }

    .btn-modern-pill-success:hover {
        background: #10b981;
        color: #ffffff;
        border-color: #10b981;
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(16, 185, 129, 0.25);
    }

    .btn-modern-pill-success:active {
        transform: translateY(0);
        box-shadow: 0 1px 3px rgba(16, 185, 129, 0.15);
    }

    .btn-modern-pill-danger {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #fff1f2;
        color: #be123c;
        border: 1.5px solid #fecdd3;
        font-weight: 600;
        font-size: 13px;
        border-radius: 8px;
        padding: 6px 14px;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 1px 2px rgba(225, 29, 72, 0.08);
    }

    .btn-modern-pill-danger:hover {
        background: #e11d48;
        color: #ffffff;
        border-color: #e11d48;
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(225, 29, 72, 0.25);
    }

    .btn-modern-pill-danger:active {
        transform: translateY(0);
        box-shadow: 0 1px 3px rgba(225, 29, 72, 0.15);
    }

    .btn-modern-group-select {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #f0fdfa;
        color: #0F766E;
        border: 1px solid #99f6e4;
        font-weight: 600;
        font-size: 12px;
        border-radius: 6px;
        padding: 4px 11px;
        transition: all 0.15s ease-in-out;
    }

    .btn-modern-group-select:hover {
        background: #0F766E;
        color: #ffffff;
        border-color: #0F766E;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(15, 118, 110, 0.2);
    }

    .btn-modern-group-deselect {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #f8fafc;
        color: #64748b;
        border: 1px solid #cbd5e1;
        font-weight: 600;
        font-size: 12px;
        border-radius: 6px;
        padding: 4px 11px;
        transition: all 0.15s ease-in-out;
    }

    .btn-modern-group-deselect:hover {
        background: #e2e8f0;
        color: #1e293b;
        border-color: #94a3b8;
        transform: translateY(-1px);
    }

    .btn-modern-cancel {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #ffffff;
        color: #475569;
        border: 1.5px solid #cbd5e1;
        font-weight: 600;
        font-size: 14px;
        border-radius: 10px;
        padding: 9px 26px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none !important;
    }

    .btn-modern-cancel:hover {
        background: #f8fafc;
        color: #1e293b;
        border-color: #94a3b8;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    }

    .btn-modern-primary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(135deg, #0F766E 0%, #0d9488 100%);
        color: #ffffff;
        border: none;
        font-weight: 600;
        font-size: 14.5px;
        border-radius: 10px;
        padding: 10px 36px;
        letter-spacing: 0.2px;
        box-shadow: 0 4px 14px rgba(15, 118, 110, 0.35);
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .btn-modern-primary:hover {
        background: linear-gradient(135deg, #115e59 0%, #0F766E 100%);
        color: #ffffff;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(15, 118, 110, 0.45);
    }

    .btn-modern-primary:active {
        transform: translateY(0);
        box-shadow: 0 2px 8px rgba(15, 118, 110, 0.25);
    }

    .btn-modern-primary:disabled {
        opacity: 0.65;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
</style>
@endsection

@section('scripts')
<script type="text/javascript">
    $(document).ready(function() {
        // Global Select All / Deselect All
        $('#btnSelectAllGlobal').on('click', function() {
            $('input[name="permission[]"]').prop('checked', true);
            if (typeof Notiflix !== 'undefined') {
                Notiflix.Notify.info('All permissions selected');
            }
        });

        $('#btnDeselectAllGlobal').on('click', function() {
            $('input[name="permission[]"]').prop('checked', false);
            if (typeof Notiflix !== 'undefined') {
                Notiflix.Notify.warning('All permissions deselected');
            }
        });

        // Group Select All / Deselect All
        $('.group-select-all').on('click', function() {
            var target = $(this).data('target');
            $('.' + target).find('input[name="permission[]"]').prop('checked', true);
        });

        $('.group-deselect-all').on('click', function() {
            var target = $(this).data('target');
            $('.' + target).find('input[name="permission[]"]').prop('checked', false);
        });

        // Form Submit with Notiflix
        $('#roleForm').on('submit', function(e) {
            e.preventDefault();

            var nameInput = $('#name');
            var roleName = nameInput.val() ? nameInput.val().trim() : '';

            nameInput.removeClass('is-invalid');

            if (!roleName) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Please enter a role name');
                } else {
                    alert('Please enter a role name');
                }
                nameInput.addClass('is-invalid').focus();
                return;
            }

            if (roleName.toLowerCase() === 'super admin') {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('The "Super Admin" role is reserved for system administration');
                } else {
                    alert('The "Super Admin" role is reserved for system administration');
                }
                nameInput.addClass('is-invalid').focus();
                return;
            }

            var checkedCount = $('input[name="permission[]"]:checked').length;
            if (checkedCount === 0) {
                if (typeof Notiflix !== 'undefined') {
                    Notiflix.Notify.failure('Please select at least one permission for this role');
                } else {
                    alert('Please select at least one permission for this role');
                }
                return;
            }

            var submitBtn = $('#btnSubmitRole');
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1 mr-1"></i> Saving Role...');

            if (typeof Notiflix !== 'undefined') {
                Notiflix.Loading.standard('Saving role, please wait...');
            }

            var form = this;
            $.ajax({
                url: $(form).attr('action'),
                type: $(form).attr('method'),
                data: new FormData(form),
                processData: false,
                contentType: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function(response) {
                    if (typeof Notiflix !== 'undefined') {
                        Notiflix.Loading.remove();
                    }

                    var msg = (response && response.message) ? response.message : 'Role created successfully.';
                    if (typeof Notiflix !== 'undefined') {
                        Notiflix.Notify.success(msg);
                    } else {
                        alert(msg);
                    }

                    setTimeout(function() {
                        window.location.href = (response && response.redirect) ? response.redirect : "{{ route('roles.index') }}";
                    }, 1000);
                },
                error: function(xhr) {
                    if (typeof Notiflix !== 'undefined') {
                        Notiflix.Loading.remove();
                    }
                    submitBtn.prop('disabled', false).html('<i class="fas fa-check-circle me-1 mr-1"></i> Save Role');

                    var msg = 'Failed to create role.';
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var errors = xhr.responseJSON.errors;
                        var firstKey = Object.keys(errors)[0];
                        msg = errors[firstKey][0];
                        if (firstKey === 'name') {
                            nameInput.addClass('is-invalid').focus();
                        }
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }

                    if (typeof Notiflix !== 'undefined') {
                        Notiflix.Notify.failure(msg);
                    } else {
                        alert(msg);
                    }
                }
            });
        });
    });
</script>
@endsection
