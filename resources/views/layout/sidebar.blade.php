<style>
    .vertical-menu {
        border-left: 1px solid #dbe6ef;
        background: linear-gradient(180deg, #f8fbff 0%, #f2f7f7 100%);
        box-shadow: -8px 0 24px rgba(24, 42, 76, 0.07);
    }

    .vertical-menu .simplebar-content-wrapper {
        scrollbar-width: thin;
        scrollbar-color: #a9c5c2 transparent;
    }

    #sidebar-menu {
        padding: 14px 8px 34px;
    }

    #sidebar-menu .menu-title {
        margin: 12px 8px 5px;
        padding: 8px 10px 5px !important;
        color: #78909c;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.08em;
        opacity: 1;
    }

    #sidebar-menu > ul > li > a,
    #sidebar-menu > ul > li > ul > li > a {
        min-height: 44px;
        margin: 3px 4px;
        padding: 10px 9px;
        border: 1px solid transparent;
        border-radius: 8px;
        color: #425466;
        font-size: 13px;
        font-weight: 600;
        white-space: nowrap;
        transition: color 0.16s ease, background-color 0.16s ease, border-color 0.16s ease;
    }

    #sidebar-menu ul li a i {
        width: 24px;
        min-width: 24px;
        margin-left: 4px;
        color: #668078;
        font-size: 16px;
        text-align: center;
        opacity: 1;
    }

    #sidebar-menu ul li a:hover {
        border-color: #d8e9e6;
        background: #edf7f5;
        color: #0f766e;
    }

    #sidebar-menu ul li a:hover i {
        color: #0f766e;
    }

    #sidebar-menu ul li > a.active,
    #sidebar-menu ul li.mm-active > a {
        border-color: #c6e2dd;
        background: #dff1ee;
        color: #0b625c !important;
        box-shadow: 0 3px 9px rgba(15, 118, 110, 0.1);
    }

    #sidebar-menu ul li > a.active i,
    #sidebar-menu ul li.mm-active > a i {
        color: #0f766e !important;
    }

    #sidebar-menu ul li ul.sub-menu {
        margin: 2px 9px 7px;
        padding: 4px 0 4px 8px;
        border-left: 1px solid #d7e4e7;
    }

    #sidebar-menu ul li ul.sub-menu li a,
    #sidebar-menu ul li ul.sub-menu li ul.sub-menu li a {
        min-height: 36px;
        margin: 2px 0;
        padding: 8px 12px;
        border-radius: 7px;
        color: #637484;
        font-size: 12.5px;
    }

    #sidebar-menu ul li ul.sub-menu li a:hover,
    #sidebar-menu ul li ul.sub-menu li a.active {
        background: #e8f4f2;
        color: #0f766e !important;
    }

    #sidebar-menu .has-arrow:after {
        color: #8aa09d;
        font-size: 14px;
    }

    .vertical-collpsed .vertical-menu #sidebar-menu > ul > li > a {
        min-height: 48px;
        margin: 4px 7px;
        padding: 12px 15px;
        border-radius: 8px;
    }

    .vertical-collpsed .vertical-menu #sidebar-menu > ul > li:hover > a,
    .vertical-collpsed .vertical-menu #sidebar-menu > ul > li:hover > ul {
        background: #f8fbff;
        box-shadow: 0 10px 22px rgba(24, 42, 76, 0.14);
    }

    @media (max-width: 991.98px) {
        body.sidebar-enable .vertical-menu {
            border-left: 0;
            box-shadow: -10px 12px 28px rgba(24, 42, 76, 0.18);
        }
    }
</style>

 <div class="vertical-menu">

                <div data-simplebar class="h-100">

                    <!-- User details -->
                

                    <!--- Sidemenu -->
                    <div id="sidebar-menu">
                        <!-- Left Menu Start -->
                        <ul class="metismenu list-unstyled" id="side-menu">
                            <li class="menu-title">Menu</li>
                        @can('dashboard-view')
                            <li>
                                <a href="{{ route('dashboard') }}" class="waves-effect">
                                    <i class="ri-dashboard-line"></i>
                                    <span>Dashboard</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('insight') }}" class="waves-effect">
                                    <i class="ri-line-chart-line"></i>
                                    <span>Insight</span>
                                </a>
                            </li>
                        @endcan
                        @can('chat-use')
                            <li>
                                <a href="{{ route('chat.index') }}" class="waves-effect">
                                    <i class="ri-chat-3-line"></i>
                                    <span>Business Chat</span>
                                </a>
                            </li>
                        @endcan
                            {{-- transaction menu starts --}}
                            @canany(['purchase-list', 'purchase-create', 'sale-list', 'sale-create', 'sale-delivery-verify', 'expense-list', 'expense-create', 'stock-adjustment-list', 'stock-adjustment-create', 'cash-reconciliation-list', 'cash-reconciliation-create', 'cash-reconciliation-show', 'bank-transaction-list', 'payment-sales-cash-list', 'payment-sales-credit-list', 'payment-purchases-cash-list', 'payment-purchases-credit-list'])
                            <li class="menu-title">Transactions</li>
                                @canany(['purchase-list', 'purchase-create', 'sale-list', 'sale-create', 'sale-delivery-verify', 'expense-list', 'expense-create', 'stock-adjustment-list', 'stock-adjustment-create', 'cash-reconciliation-list', 'cash-reconciliation-create', 'cash-reconciliation-show', 'bank-transaction-list'])
                                <li><!--Main drop-->
                                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                                        <i class="fa fa-id-card"></i>
                                        <span>Manage Transactions</span>
                                    </a>

                                    <ul><!--drop ul-->
                                     @canany(['purchase-list', 'purchase-create'])
                                        <li>
                                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                                <i class="ri-oil-fill"></i>
                                                <span>Purchase</span>
                                            </a>
                                            <ul class="sub-menu" aria-expanded="false">
                                                @can('purchase-create')
                                                    <li><a href="{{ route('purchases.create') }}"><i class="fa fa-plus-circle me-1"></i>Create</a></li>
                                                @endcan
                                                @can('purchase-list')
                                                    <li><a href="{{ route('purchases.index') }}"><i class="fa fa-shopping-basket me-1"></i>All Purchase</a></li>
                                                @endcan
                                            
                                            </ul>
                                        </li>
                                     @endcanany
                                     @canany(['sale-list', 'sale-create', 'sale-delivery-verify'])
                                        <li>
                                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                                <i class="ri-compass-2-fill"></i>
                                                <span>Sales</span>
                                            </a>
                                            <ul class="sub-menu" aria-expanded="false">
                                                @can('sale-create')
                                                    <li><a href="{{ route('sales.create') }}"><i class="fa fa-plus-circle me-1"></i>Create</a></li>
                                                @endcan
                                                @can('sale-list')
                                                    <li><a href="{{ route('sales.index') }}"><i class="fa fa-cash-register me-1"></i>All Sales</a></li>
                                                    <li><a href="{{ route('sales.daily-total-sales') }}"><i class="fa fa-calendar-day me-1"></i>Daily Total Sales</a></li>
                                                @endcan
                                                @can('sale-delivery-verify')
                                                    <li><a href="{{ route('sales.delivery.verify.page') }}"><i class="fa fa-barcode me-1"></i>Verify Delivery</a></li>
                                                @endcan
                                                
                                            </ul>
                                        </li>
                                     @endcanany
                                     @canany(['expense-list', 'expense-create'])
                                        <li>
                                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                                <i class="ri-file-list-3-fill"></i>
                                                <span>Expenses</span>
                                            </a>
                                            <ul class="sub-menu" aria-expanded="false">
                                                @can('expense-create')
                                                    <li><a href="{{ route('expense-vouchers.create') }}"><i class="fa fa-plus-circle me-1"></i>Create</a></li>
                                                @endcan
                                                @can('expense-list')
                                                    <li><a href="{{ route('expense-vouchers.index') }}"><i class="fa fa-receipt me-1"></i>All Expense </a></li>
                                                @endcan
                                            </ul>
                                        </li>
                                    @endcanany

                                    @canany(['stock-adjustment-list', 'stock-adjustment-create'])
                                        <li>
                                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                                <i class="fa fa-cubes"></i>
                                                <span>Stock </span>
                                            </a>
                                            <ul class="sub-menu" aria-expanded="false">
                                                @can('stock-adjustment-create')
                                                    <li><a href="{{ route('stock-adjustments.create') }}"><i class="fa fa-plus-circle me-1"></i>Create</a></li>
                                                @endcan
                                                @can('stock-adjustment-list')
                                                    <li><a href="{{ route('stock-adjustments.index') }}"><i class="fa fa-boxes me-1"></i>All Stock Adjustments</a></li>
                                                @endcan
                                            </ul>
                                        </li>
                                    @endcanany
                                    @canany(['stock-transfer-list', 'stock-transfer-create', 'stock-transfer-receive'])
                                        <li>
                                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                                <i class="fa fa-truck"></i>
                                                <span>Stock Transfers</span>
                                            </a>
                                            <ul class="sub-menu" aria-expanded="false">
                                                @can('stock-transfer-create')
                                                    <li><a href="{{ route('stock-transfers.create') }}"><i class="fa fa-plus-circle me-1"></i>New Transfer</a></li>
                                                @endcan
                                                @can('stock-transfer-list')
                                                    <li><a href="{{ route('stock-transfers.index') }}"><i class="fa fa-truck me-1"></i>All Transfers</a></li>
                                                @endcan
                                            </ul>
                                        </li>
                                    @endcanany

                                    @canany(['cash-reconciliation-list', 'cash-reconciliation-create', 'cash-reconciliation-show'])
                                        <li>
                                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                                <i class="fa fa-money-bill-wave"></i>
                                                <span>Cash Reconcile</span>
                                            </a>
                                            <ul class="sub-menu" aria-expanded="false">
                                                @can('cash-reconciliation-create')
                                                    <li><a href="{{ route('cash-reconciliations.create') }}"><i class="fa fa-plus-circle me-1"></i>Create</a></li>
                                                @endcan
                                                @canany(['cash-reconciliation-list', 'cash-reconciliation-show'])
                                                    <li><a href="{{ route('cash-reconciliations.index') }}"><i class="fa fa-balance-scale me-1"></i>All Reconciliations</a></li>
                                                @endcanany
                                            </ul>
                                        </li>
                                    @endcanany

                                    @can('bank-transaction-list')
                                        <li><a href="{{ route('accounting.setup.bank-transactions.index') }}"><i class="fa fa-university me-1"></i>Bank Transactions</a></li>
                                    @endcan

                                </ul><!--drop ul ends-->    
                            </li><!--cover ends--> 
                            @endcanany

                            @canany(['payment-sales-cash-list', 'payment-sales-credit-list', 'payment-purchases-cash-list', 'payment-purchases-credit-list'])
                                <li>
                                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                                        <i class="fa fa-wallet"></i>
                                        <span>Manage Payments</span>
                                    </a>
                                    <ul class="sub-menu" aria-expanded="false">
                                        @can('payment-sales-cash-list')
                                            <li><a href="{{ route('paid.customer') }}"><i class="fa fa-money-bill-wave me-1"></i>Cash Sales</a></li>
                                        @endcan
                                        @can('payment-sales-credit-list')
                                            <li><a href="{{ route('credit.customer') }}"><i class="fa fa-credit-card me-1"></i>Credit Sales List</a></li>
                                        @endcan
                                        @can('payment-purchases-cash-list')
                                            <li><a href="{{ route('paid.supplier') }}"><i class="fa fa-money-check-alt me-1"></i>Cash Purchases</a></li>
                                        @endcan
                                        @can('payment-purchases-credit-list')
                                            <li><a href="{{ route('credit.supplier') }}"><i class="fa fa-file-invoice-dollar me-1"></i>Credit Purchases List</a></li>
                                        @endcan
                                    </ul>
                                </li>
                            @endcanany

                            @endcanany
                         {{-- transaction menu ends --}}

                                 @canany(['report-summary-ledger', 'report-profit', 'report-sales-lines', 'report-purchase-lines', 'report-purchase-edit-trail', 'report-sales-cash-movement', 'report-purchase-cash-movement', 'report-sales-credit-movement', 'report-purchase-credit-movement', 'report-expense', 'report-stock-adjustment', 'stock-product-trail-report', 'stock-report', 'stock-value-report', 'stock-ledger-report', 'product-price-history-list', 'sale-receipt-audit-view'])
                         <li class="menu-title">Reports</li>

                                     @canany(['report-summary-ledger', 'report-profit', 'report-sales-lines', 'report-purchase-lines', 'report-purchase-edit-trail', 'report-sales-cash-movement', 'report-purchase-cash-movement', 'report-sales-credit-movement', 'report-purchase-credit-movement', 'report-expense'])
                            <li>
                                <a href="javascript: void(0);" class="has-arrow waves-effect">
                                    <i class="fa-solid fa-chart-simple"></i>
                                    <span>Transactions Report</span>
                                </a>
                                <ul class="sub-menu" aria-expanded="false">
                                    @can('report-sales-lines')
                                        <li><a href="{{ route('reports.sales-lines') }}"><i class="fa fa-chart-line me-1"></i>Sales Lines Report</a></li>
                                    @endcan
                                    @can('report-purchase-lines')
                                        <li><a href="{{ route('reports.purchase-lines') }}"><i class="fa fa-chart-bar me-1"></i>Purchase Lines Report</a></li>
                                    @endcan
                                    @can('report-sales-cash-movement')
                                        <li><a href="{{ route('reports.sales-cash-movement') }}"><i class="fa fa-exchange-alt me-1"></i>Sales Cash Movement Report</a></li>
                                    @endcan
                                    @can('report-purchase-cash-movement')
                                        <li><a href="{{ route('reports.purchase-cash-movement') }}"><i class="fa fa-exchange-alt me-1"></i>Purchase Cash Movement Report</a></li>
                                    @endcan
                                    @can('report-sales-credit-movement')
                                        <li><a href="{{ route('reports.sales-credit-movement') }}"><i class="fa fa-credit-card me-1"></i>Sales Credit Movement Report</a></li>
                                    @endcan
                                    @can('report-purchase-credit-movement')
                                        <li><a href="{{ route('reports.purchase-credit-movement') }}"><i class="fa fa-credit-card me-1"></i>Purchase Credit Movement Report</a></li>
                                    @endcan
                                    @can('report-profit')
                                        <li><a href="{{ route('reports.profit') }}"><i class="fa fa-chart-pie me-1"></i>Profit Report</a></li>
                                    @endcan
                                    @can('report-summary-ledger')
                                        <li><a href="{{ route('reports.summary-ledger') }}"><i class="fa fa-book me-1"></i>Summary Ledger</a></li>
                                    @endcan
                                    @can('report-expense')
                                        <li><a href="{{ route('reports.expense') }}"><i class="fa fa-file-invoice me-1"></i>Expense Report</a></li>
                                    @endcan
                                </ul>
                            </li>
                            @endcanany

                            @canany(['report-stock-adjustment', 'stock-product-trail-report', 'stock-report', 'stock-value-report', 'stock-ledger-report', 'product-price-history-list'])
                            <li>
                                <a href="javascript: void(0);" class="has-arrow waves-effect">
                                    <i class="fa-solid fa-boxes-stacked"></i>
                                    <span>Stock Report</span>
                                </a>
                                <ul class="sub-menu" aria-expanded="false">
                                    @can('stock-product-trail-report')
                                        <li><a href="{{ route('stock.product_trail_report') }}"><i class="fa fa-route me-1"></i>Product-wise Stock Trail</a></li>
                                    @endcan
                                    @can('stock-report')
                                        <li><a href="{{ route('stock.report') }}"><i class="fa fa-clipboard-list me-1"></i>Summary Stock Report</a></li>
                                    @endcan
                                    @can('stock-value-report')
                                        <li><a href="{{ route('stock.stock_value') }}"><i class="fa fa-coins me-1"></i>Stock Value Report</a></li>
                                    @endcan
                                    @can('report-stock-adjustment')
                                        <li><a href="{{ route('reports.stock-adjustment') }}"><i class="fa fa-tools me-1"></i>Stock Adjustment Report</a></li>
                                    @endcan
                                    @can('product-price-history-list')
                                        <li><a href="{{ route('product-prices.product_price_history') }}"><i class="fa fa-history me-1"></i>Product Price History</a></li>
                                    @endcan
                                </ul>
                            </li>
                            @endcanany

                            @canany(['report-purchase-edit-trail', 'stock-ledger-report', 'sale-receipt-audit-view'])
                            <li>
                                <a href="javascript: void(0);" class="has-arrow waves-effect">
                                    <i class="fa-solid fa-chart-simple"></i>
                                    <span>Audit Report</span>
                                </a>
                                <ul class="sub-menu" aria-expanded="false">
                                    @can('report-purchase-edit-trail')
                                        <li><a href="{{ route('reports.purchase-edit-trail') }}"><i class="fa fa-history me-1"></i>Purchase Edit Trail</a></li>
                                    @endcan
                                    @can('stock-ledger-report')
                                        <li><a href="{{ route('stock.ledger_report') }}"><i class="fa fa-book me-1"></i>Stock Movement Trail</a></li>
                                    @endcan
                                    @can('sale-receipt-audit-view')
                                        <li><a href="{{ route('reports.sales-receipt-audit') }}"><i class="fa fa-clipboard-list me-1"></i>Receipt Audit</a></li>
                                    @endcan
                                </ul>
                            </li>
                            @endcanany
                        @endcanany

                        @canany(['report-trial-balance', 'report-general-ledger', 'report-income-statement', 'report-balance-sheet', 'report-cash-flow', 'report-ar-aging', 'report-ap-aging'])
                        <li>
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="fa-solid fa-calculator"></i>
                                <span>Accounting Reports</span>
                            </a>
                            <ul class="sub-menu" aria-expanded="false">
                                @can('report-trial-balance')
                                    <li><a href="{{ route('accounting.trial-balance') }}"><i class="fa fa-balance-scale me-1"></i>Trial Balance</a></li>
                                @endcan
                                @can('report-general-ledger')
                                    <li><a href="{{ route('accounting.general-ledger') }}"><i class="fa fa-book-open me-1"></i>General Ledger</a></li>
                                @endcan
                                @can('report-income-statement')
                                    <li><a href="{{ route('accounting.income-statement') }}"><i class="fa fa-file-invoice-dollar me-1"></i>Income Statement</a></li>
                                @endcan
                                @can('report-balance-sheet')
                                    <li><a href="{{ route('accounting.balance-sheet') }}"><i class="fa fa-file-invoice me-1"></i>Balance Sheet</a></li>
                                @endcan
                                @can('report-cash-flow')
                                    <li><a href="{{ route('accounting.cash-flow') }}"><i class="fa fa-money-bill-wave me-1"></i>Cash Flow</a></li>
                                @endcan
                                @can('report-ar-aging')
                                    <li><a href="{{ route('accounting.ar-aging') }}"><i class="fa fa-hourglass-half me-1"></i>A/R Aging</a></li>
                                @endcan
                                @can('report-ap-aging')
                                    <li><a href="{{ route('accounting.ap-aging') }}"><i class="fa fa-hourglass-end me-1"></i>A/P Aging</a></li>
                                @endcan
                            </ul>
                        </li>
                        @endcanany

                        @canany(['accounting-coa-list', 'accounting-fiscal-period-list', 'bank-account-list', 'capital-loan-list'])
                        <li>
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="fa fa-book"></i>
                                <span>Accounting Setup</span>
                            </a>
                            <ul class="sub-menu" aria-expanded="false">
                                @can('accounting-coa-list')
                                    <li><a href="{{ route('accounting.setup.coa.index') }}"><i class="fa fa-sitemap me-1"></i>Chart of Accounts</a></li>
                                @endcan
                                @can('accounting-fiscal-period-list')
                                    <li><a href="{{ route('accounting.setup.fiscal-periods.index') }}"><i class="fa fa-calendar-alt me-1"></i>Fiscal Periods</a></li>
                                @endcan
                                @can('bank-account-list')
                                    <li><a href="{{ route('accounting.setup.bank-accounts.index') }}"><i class="fa fa-piggy-bank me-1"></i>Bank Accounts</a></li>
                                @endcan
                                @can('capital-loan-list')
                                    <li><a href="{{ route('accounting.setup.capital-loans.index') }}"><i class="fa fa-hand-holding-usd me-1"></i>Capital & Loans</a></li>
                                @endcan
                            </ul>
                        </li>
                        @endcanany

                        {{-- setup menu starts --}}
                         @canany(['outlet-list', 'outlet-create', 'company-list', 'company-create', 'supplier-list', 'supplier-create', 'customer-list', 'customer-create', 'category-list', 'category-create', 'unit-list', 'unit-create', 'expense-category-list', 'expense-category-create', 'product-list', 'product-create', 'product-price-list', 'settings-manage'])
                         <li class="menu-title">Setups</li>
                          <li><!--Main drop-->
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="fa fa-cog"></i>
                                <span>Manage Setups</span>
                            </a>
                            <ul><!--drop ul-->
                                @canany(['company-list', 'company-create'])
                                 <li>
                                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                                        <i class="fa fa-building"></i>
                                        <span>Company</span>
                                    </a>
                                    <ul class="sub-menu" aria-expanded="false">
                                        @can('company-create')
                                            <li><a href="{{ route('companies.create') }}"><i class="fa fa-plus-circle me-1"></i>Create</a></li>
                                        @endcan
                                        @can('company-list')
                                            <li><a href="{{ route('companies.index') }}"><i class="fa fa-building me-1"></i>All Company</a></li>
                                        @endcan
                                        
                                    </ul>
                                </li>
                                @endcanany
                                @canany(['outlet-list', 'outlet-create'])
                                 <li>
                                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                                        <i class="fa fa-store"></i>
                                        <span>Outlets / Branches</span>
                                    </a>
                                    <ul class="sub-menu" aria-expanded="false">
                                        @can('outlet-create')
                                            <li><a href="{{ route('outlets.create') }}"><i class="fa fa-plus-circle me-1"></i>Create Outlet</a></li>
                                        @endcan
                                        @can('outlet-list')
                                            <li><a href="{{ route('outlets.index') }}"><i class="fa fa-store me-1"></i>All Outlets</a></li>
                                        @endcan
                                    </ul>
                                </li>
                                @endcanany


                                @canany(['supplier-list', 'supplier-create'])
                                <li>
                                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                                        <i class="fa fa-truck"></i>
                                        <span>Suppliers</span>
                                    </a>
                                    <ul class="sub-menu" aria-expanded="false">
                                        @can('supplier-create')
                                            <li><a href="{{ route('suppliers.create') }}"><i class="fa fa-plus-circle me-1"></i>Create</a></li>
                                        @endcan
                                        @can('supplier-list')
                                            <li><a href="{{ route('suppliers.index') }}"><i class="fa fa-truck me-1"></i>All Supplier</a></li>
                                        @endcan   
                                        
                                    </ul>
                                </li>
                                @endcanany

                                @canany(['customer-list', 'customer-create'])
                                <li>
                                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                                        <i class="fa fa-users"></i>
                                        <span>Customers</span>
                                    </a>
                                    <ul class="sub-menu" aria-expanded="false">
                                        @can('customer-create')
                                            <li><a href="{{ route('customers.create') }}"><i class="fa fa-plus-circle me-1"></i>Create</a></li>
                                        @endcan
                                        @can('customer-list')
                                            <li><a href="{{ route('customers.index') }}"><i class="fa fa-users me-1"></i>All Customers</a></li>
                                        @endcan
                                    
                                    </ul>
                                </li>
                                @endcanany

                                @canany(['category-list', 'category-create'])
                                <li>
                                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                                        <i class="fa fa-bars"></i>
                                        <span>Category</span>
                                    </a>
                                    <ul class="sub-menu" aria-expanded="false">
                                        @can('category-create')
                                            <li><a href="{{ route('categories.create') }}"><i class="fa fa-plus-circle me-1"></i>Create</a></li>
                                        @endcan
                                        @can('category-list')
                                            <li><a href="{{ route('categories.index') }}"><i class="fa fa-tags me-1"></i>All Category</a></li>
                                        @endcan
                                        
                                    </ul>
                                </li>
                                @endcanany

                                @canany(['unit-list', 'unit-create'])
                                <li>
                                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                                        <i class="fa fa-book"></i>
                                        <span>Unit</span>
                                    </a>
                                    <ul class="sub-menu" aria-expanded="false">
                                        @can('unit-create')
                                            <li><a href="{{ route('units.create') }}"><i class="fa fa-plus-circle me-1"></i>Create</a></li>
                                        @endcan
                                        @can('unit-list')
                                            <li><a href="{{ route('units.index') }}"><i class="fa fa-ruler-combined me-1"></i>All Unit</a></li>
                                        @endcan
                                        
                                    </ul>
                                </li>
                                @endcanany

                                @canany(['expense-category-list', 'expense-category-create'])
                                <li>
                                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                                        <i class="fa fa-list"></i>
                                        <span>Expense Category</span>
                                    </a>
                                    <ul class="sub-menu" aria-expanded="false">
                                        @can('expense-category-create')
                                            <li><a href="{{ route('expense-categories.create') }}"><i class="fa fa-plus-circle me-1"></i>Create</a></li>
                                        @endcan
                                        @can('expense-category-list')
                                            <li><a href="{{ route('expense-categories.index') }}"><i class="fa fa-list me-1"></i>All Expense Categories</a></li>
                                        @endcan
                                    </ul>
                                </li>
                                @endcanany

                                @canany(['product-list', 'product-create'])
                                <li>
                                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                                        <i class="fa fa-truck"></i>
                                        <span>Product</span>
                                    </a>
                                    <ul class="sub-menu" aria-expanded="false">
                                        @can('product-create')
                                            <li><a href="{{ route('products.create') }}"><i class="fa fa-plus-circle me-1"></i>Create</a></li>
                                        @endcan
                                        @can('product-list')
                                            <li><a href="{{ route('products.index') }}"><i class="fa fa-box-open me-1"></i>All Product</a></li>
                                        @endcan
                                    
                                    </ul>
                                </li>
                                @endcanany

                                @can('product-price-list')
                                 <li>
                                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                                        <i class="fa fa-truck"></i>
                                        <span>Update Price</span>
                                    </a>
                                    <ul class="sub-menu" aria-expanded="false">
                                        <li><a href="{{ route('product-prices.index') }}"><i class="fa fa-tags me-1"></i>All Product Prices</a></li>
                                    
                                    </ul>
                                </li>
                                @endcan

                                @can('settings-manage')
                                @if(auth()->user()->isCentral())
                                <li>
                                    <a href="{{ route('settings.index') }}" class="waves-effect">
                                        <i class="fa fa-sliders-h"></i>
                                        <span>Settings</span>
                                    </a>
                                </li>
                                @endif
                                @endcan
                            </ul><!--drop ul ends-->    
                        </li><!--cover ends--> 
                        @endcanany
                        {{-- setup menu ends --}}

                         @canany(['settings-list', 'settings-edit', 'daily-transaction-email-send'])
                        <li class="menu-title">Emails and Notifications</li>
                        <li>
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="fa fa-envelope"></i>
                                <span>Email Automation</span>
                            </a>
                            <ul class="sub-menu" aria-expanded="false">
                                <li><a href="{{ route('daily-transaction-email.index') }}"><i class="fa fa-paper-plane me-1"></i>Send Daily Email</a></li>
                                <li><a href="{{ route('daily-transaction-email.logs') }}"><i class="fa fa-envelope-open-text me-1"></i>Daily Email Report</a></li>
                            </ul>
                        </li>
                        @endcanany


                        {{-- setup user starts --}}
                        @canany(['user-list', 'user-create', 'role-list', 'role-create'])
                        <li class="menu-title">User Setup</li>
                        <li><!--Main drop-->
                          <a href="javascript: void(0);" class="has-arrow waves-effect">
                              <i class="fa fa-users"></i>
                              <span>Manage Users</span>
                          </a>
                          <ul><!--drop ul-->

                                @canany(['user-list', 'user-create'])
                                <li>
                                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                                        <i class="fa fa-id-card"></i>
                                        <span>Users</span>
                                    </a>
                                    <ul class="sub-menu" aria-expanded="false">
                                        @can('user-create')
                                            <li><a href="{{ route('users.create') }}"><i class="fa fa-plus-circle me-1"></i>Create</a></li>
                                        @endcan
                                        @can('user-list')
                                            <li><a href="{{ route('users.index') }}"><i class="fa fa-users-cog me-1"></i>All Users</a></li>
                                        @endcan
                                        
                                    </ul>
                                </li>
                                @endcanany

                                @canany(['role-list', 'role-create'])
                                <li>
                                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                                        <i class="fa fa-id-card"></i>
                                        <span>Roles</span>
                                    </a>
                                    <ul class="sub-menu" aria-expanded="false">
                                        @can('role-create')
                                            <li><a href="{{ route('roles.create') }}"><i class="fa fa-plus-circle me-1"></i>Create</a></li>
                                        @endcan
                                        @can('role-list')
                                            <li><a href="{{ route('roles.index') }}"><i class="fa fa-user-shield me-1"></i>All Roles</a></li>
                                        @endcan
                                        
                                    </ul>
                                </li>
                                @endcanany
                            

                            </ul><!--drop ul ends-->    
                        </li><!--user li ends--> 
                        @endcanany
                        {{-- setup user ends    --}}
                        </ul>
                    </div>
                    <!-- Sidebar -->
                </div>
            </div>
