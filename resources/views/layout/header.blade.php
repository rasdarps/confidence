<style>
    #page-topbar {
        border-bottom: 1px solid #1e293b;
        background: linear-gradient(105deg, #090e1a 0%, #0f172a 58%, #1e293b 100%);
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.35);
    }

    #page-topbar .navbar-header {
        background: transparent !important;
    }

    #page-topbar .navbar-header > .d-flex:first-child {
        align-items: center;
    }

    #page-topbar .navbar-brand-box {
        height: 70px;
        display: flex;
        align-items: center;
    }

    #page-topbar .navbar-brand-box .logo-light {
        display: flex;
        height: 70px;
        align-items: center;
        line-height: normal;
    }

    #page-topbar .logo-light .logo-lg {
        display: inline-flex;
        width: 58px;
        height: 58px;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(255, 255, 255, 0.55);
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.94);
        box-shadow: 0 3px 10px rgba(4, 45, 42, 0.16);
    }

    #page-topbar .logo-light .logo-lg img {
        width: 52px;
        height: 52px;
        margin-top: 0 !important;
        object-fit: contain;
    }

    #page-topbar #vertical-menu-btn {
        width: 42px;
        height: 42px;
        padding: 0 !important;
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
    }

    #page-topbar #vertical-menu-btn:hover,
    #page-topbar #vertical-menu-btn:focus {
        background: rgba(255, 255, 255, 0.2);
        color: #fff;
    }

    #page-topbar .header-quick-actions {
        flex-wrap: nowrap;
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, 0.45) transparent;
        gap: 6px;
        padding-bottom: 2px;
    }

    #page-topbar .header-quick-actions::-webkit-scrollbar {
        height: 5px;
    }

    #page-topbar .header-quick-actions::-webkit-scrollbar-track {
        background: transparent;
    }

    #page-topbar .header-quick-actions::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.45);
        border-radius: 12px;
    }

    #page-topbar .header-quick-actions .quick-action-btn {
        width: 40px;
        height: 40px;
        flex: 0 0 auto;
        padding: 0;
        border: 1px solid rgba(255, 255, 255, 0.22);
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.1);
        color: #f8ffff;
        font-size: 18px;
        box-shadow: none;
    }

    #page-topbar .header-quick-actions .quick-action-btn:hover,
    #page-topbar .header-quick-actions .quick-action-btn:focus-visible {
        border-color: rgba(16, 185, 129, 0.6);
        background: rgba(16, 185, 129, 0.22);
        color: #34d399;
        transform: translateY(-1px);
    }

    #page-topbar .navbar-header > .d-flex:last-child {
        min-height: 46px;
        margin-left: 8px;
        padding-left: 8px;
        border-left: 1px solid rgba(255, 255, 255, 0.2);
        align-items: center;
    }

    #page-topbar .noti-icon,
    #page-topbar #page-header-user-dropdown {
        color: #f8ffff;
    }

    #page-topbar .noti-icon {
        width: 42px;
        height: 42px;
        border-radius: 8px;
    }

    #page-topbar .noti-icon:hover,
    #page-topbar #page-header-user-dropdown:hover,
    #page-topbar #page-header-user-dropdown[aria-expanded="true"] {
        background: rgba(255, 255, 255, 0.14);
        color: #fff;
    }

    #page-topbar #page-header-user-dropdown {
        height: 46px;
        padding: 0 10px;
        border-radius: 8px;
    }

    #page-topbar .header-profile-user {
        width: 34px;
        height: 34px;
        border: 2px solid rgba(255, 255, 255, 0.8);
        background: #fff;
        object-fit: cover;
    }

    #page-topbar .dropdown-menu {
        margin-top: 8px;
        padding: 7px;
        border: 1px solid #dbe6ef;
        border-radius: 8px;
        box-shadow: 0 12px 28px rgba(24, 42, 76, 0.16);
    }

    #page-topbar .dropdown-item {
        padding: 9px 10px;
        border-radius: 6px;
        color: #425466;
    }

    #page-topbar .dropdown-item:hover,
    #page-topbar .dropdown-item:focus {
        background: #ecfdf5;
        color: #059669;
    }

    @media (max-width: 991.98px) {
        #page-topbar .navbar-header {
            height: auto;
            min-height: 70px;
            align-items: flex-start;
            padding-top: 8px;
            padding-bottom: 8px;
        }

        #page-topbar .navbar-header > .d-flex:first-child {
            flex: 1 1 auto;
            min-width: 0;
            flex-wrap: wrap;
            align-items: flex-start;
            row-gap: 6px;
        }

        #page-topbar #vertical-menu-btn {
            margin-left: 0 !important;
            margin-right: 8px !important;
        }

        #page-topbar .header-quick-actions {
            flex: 1 1 100%;
            display: flex;
            flex-wrap: nowrap;
            align-items: center !important;
            gap: 6px;
            margin-top: 4px;
            padding-right: 8px;
        }

        #page-topbar .header-quick-actions .quick-action-btn {
            width: 38px;
            height: 38px;
            min-width: 38px;
            padding: 0 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }
    }

    @media (max-width: 575.98px) {
        #page-topbar .navbar-header {
            min-height: 150px;
            padding-top: 12px;
            padding-bottom: 14px;
        }

        #page-topbar .header-quick-actions {
            display: grid;
            grid-template-rows: repeat(3, minmax(34px, auto));
            grid-auto-flow: column;
            grid-auto-columns: minmax(34px, auto);
            align-content: start;
            column-gap: 8px;
            row-gap: 8px;
            margin-top: 8px;
            margin-bottom: 4px;
            max-height: 126px;
            overflow-x: auto;
            overflow-y: hidden;
        }

        body.sidebar-enable .vertical-menu {
            top: 150px;
        }

        #page-topbar .header-quick-actions .quick-action-btn {
            width: 34px;
            height: 34px;
            min-width: 34px;
            padding: 0 8px;
        }
    }
</style>  
  <header id="page-topbar">
                <div class="navbar-header" style="position: relative;">
                    <div class="d-flex">
                        <!-- LOGO -->
                        <div class="navbar-brand-box">
                            {{-- <a href="{{ route('dashboard') }}" class="logo logo-dark">
                                <span class="logo_cart.png">
                                    <img src="{{ asset('images/logo_cart.png') }}" alt="logo_cart.png" height="22">
                                </span>
                                <span class="logo-lg">
                                    <img src="{{ asset('images/logo_cart.png') }}" alt="logo-dark" height="20">
                                </span>
                            </a> --}}

                            <a href="{{ route('dashboard') }}" class="logo logo-light">
                                {{-- <span class="logo_cart.png">
                                    <img src="{{ asset('images/logo_cart.png') }}" alt="logo_cart.png-light" height="22">
                                </span> --}}
                                <span class="logo-lg">
                                    <img src="{{ asset('images/bg_logo.PNG') }}" alt="DarPOS logo">
                                </span>
                            </a>
                        </div>

                <button type="button" class="btn btn-sm px-3 font-size-24 header-item waves-effect" id="vertical-menu-btn" style="margin-left: -65px; margin-right: 18px;">
                    <i class="ri-menu-2-line align-middle"></i>
                </button>

    <div class="header-quick-actions d-flex align-items-center">
        @can('dashboard-view')
            <a href="{{ route('dashboard') }}" class="btn quick-action-btn header-item waves-effect" title="Dashboard">
                <i class="ri-home-4-line align-middle"></i>
            </a>
        @endcan
        <!-- Purchases -->
        @can('purchase-create')
            <a href="{{ route('purchases.create') }}" class="btn quick-action-btn header-item waves-effect" title="Create Purchase">
                <i class="ri-add-circle-line align-middle"></i>
            </a>
        @endcan
        @can('purchase-list')
            <a href="{{ route('purchases.index') }}" class="btn quick-action-btn header-item waves-effect" title="View Purchases">
                <i class="ri-file-list-3-line align-middle"></i>
            </a>
        @endcan
        <!-- Sales -->
        @can('sale-create')
            <a href="{{ route('sales.create') }}" class="btn quick-action-btn header-item waves-effect" title="Create Sale">
                <i class="ri-shopping-cart-2-line align-middle"></i>
            </a>
        @endcan
        @can('sale-list')
            <a href="{{ route('sales.index') }}" class="btn quick-action-btn header-item waves-effect" title="View Sales">
                <i class="ri-file-list-3-line align-middle"></i>
            </a>
        @endcan
        <!-- Cash Reconciliations -->
        @can('cash-reconciliation-create')
            <a href="{{ route('cash-reconciliations.create') }}" class="btn quick-action-btn header-item waves-effect" title="Create Cash Reconciliation">
                <i class="ri-wallet-3-line align-middle"></i>
            </a>
        @endcan
        @canany(['cash-reconciliation-list', 'cash-reconciliation-show'])
            <a href="{{ route('cash-reconciliations.index') }}" class="btn quick-action-btn header-item waves-effect" title="View Cash Reconciliations">
                <i class="ri-file-list-3-line align-middle"></i>
            </a>
        @endcanany
        <!-- Products -->
        @can('product-create')
            <a href="{{ route('products.create') }}" class="btn quick-action-btn header-item waves-effect" title="Create Product">
                <i class="fa fa-apple-whole align-middle"></i>
            </a>
        @endcan
        @can('product-list')
            <a href="{{ route('products.index') }}" class="btn quick-action-btn header-item waves-effect" title="View Products">
                <i class="ri-file-list-3-line align-middle"></i>
            </a>
        @endcan
        <!-- Categories -->
        {{-- @can('category-create')
            <a href="{{ route('categories.create') }}" class="btn quick-action-btn header-item waves-effect" title="Create Category">
                <i class="ri-layout-grid-line align-middle"></i>
            </a>
        @endcan
        @can('category-list')
            <a href="{{ route('categories.index') }}" class="btn quick-action-btn header-item waves-effect" title="View Categories">
                <i class="ri-file-list-3-line align-middle"></i>
            </a>
        @endcan --}}
        <!-- Units -->
        {{-- @can('unit-create')
            <a href="{{ route('units.create') }}" class="btn quick-action-btn header-item waves-effect" title="Create Unit">
                <i class="ri-ruler-line align-middle"></i>
            </a>
        @endcan
        @can('unit-list')
            <a href="{{ route('units.index') }}" class="btn quick-action-btn header-item waves-effect" title="View Units">
                <i class="ri-file-list-3-line align-middle"></i>
            </a>
        @endcan --}}
        <!-- Reports -->
        @can('report-summary-ledger')
            <a href="{{ route('reports.summary-ledger') }}" class="btn quick-action-btn header-item waves-effect" title="Summary Ledger">
                <i class="ri-money-dollar-circle-line align-middle"></i>
            </a>
        @endcan
        @can('report-profit')
            <a href="{{ route('reports.profit') }}" class="btn quick-action-btn header-item waves-effect" title="Profit Report">
                <i class="ri-money-dollar-circle-line align-middle"></i>
            </a>
        @endcan
        @can('report-expense')
            <a href="{{ route('reports.expense') }}" class="btn quick-action-btn header-item waves-effect" title="Expense Report">
                <i class="ri-money-dollar-circle-line align-middle"></i>
            </a>
        @endcan
        @can('stock-report')
            <a href="{{ route('stock.report') }}" class="btn quick-action-btn header-item waves-effect" title="Stock Report">
                <i class="ri-bar-chart-box-line align-middle"></i>
            </a>
        @endcan
        @can('stock-ledger-report')
            <a href="{{ route('stock.ledger_report') }}" class="btn quick-action-btn header-item waves-effect" title="Stock Ledger Report">
                <i class="ri-bar-chart-box-line align-middle"></i>
            </a>
        @endcan
        {{-- @can('report-stock-adjustment')
            <a href="{{ route('reports.stock-adjustment') }}" class="btn quick-action-btn header-item waves-effect" title="Stock Adjustment Report">
                <i class="ri-bar-chart-box-line align-middle"></i>
            </a>
        @endcan --}}
        {{-- @can('product-price-history-list')
            <a href="{{ route('product-prices.product_price_history') }}" class="btn quick-action-btn header-item waves-effect" title="Product Price History">
                <i class="ri-history-line align-middle"></i>
            </a>
        @endcan --}}
        {{-- @can('stock-value-report')
            <a href="{{ route('stock.stock_value') }}" class="btn quick-action-btn header-item waves-effect" title="Stock Value">
                <i class="ri-coin-line align-middle"></i>
            </a>
        @endcan --}}
        {{-- @can('stock-product-trail-report')
            <a href="{{ route('stock.product_trail_report') }}" class="btn quick-action-btn header-item waves-effect" title="Product Trail Report">
                <i class="ri-bar-chart-box-line align-middle"></i>
            </a>
        @endcan --}}
        <!-- Settings & Logout -->
        {{-- @can('settings-manage')
            <a href="{{ route('settings.index') }}" class="btn quick-action-btn header-item waves-effect" title="Settings">
                <i class="ri-settings-3-line align-middle"></i>
            </a>
        @endcan --}}
        {{-- <a href="{{ route('admin.logout') }}" class="btn quick-action-btn header-item waves-effect" title="Logout">
            <i class="ri-logout-box-r-line align-middle"></i>
        </a> --}}
    </div>

    <!-- App Search-->
    {{-- <form class="app-search d-none d-lg-block">
        <div class="position-relative">
            <input type="text" class="form-control" placeholder="Search...">
            <span class="ri-search-line"></span>
        </div>
    </form> --}}
    

    
</div>

<div class="d-flex">



    <div class="dropdown d-none d-lg-inline-block ms-1">
        <button type="button" class="btn header-item noti-icon waves-effect" data-toggle="fullscreen">
            <i class="ri-fullscreen-line"></i>
        </button>
    </div>

    @php
    $id = Auth::user()->id;
    $adminData = App\Models\User::with('outlet')->find($id);
    @endphp

    <div class="dropdown d-inline-block user-dropdown">
        <button type="button" class="btn header-item waves-effect d-flex align-items-center" id="page-header-user-dropdown"
            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <img class="rounded-circle header-profile-user" src="{{ asset('images/bg_logo.PNG') }}"
                alt="Header Avatar">
            <span class="d-none d-xl-inline-block ms-2 text-start">
                <span class="d-block fw-semibold text-white" style="line-height: 1.2;">{{ $adminData->username }}</span>
                <span class="badge {{ ($adminData->outlet && !$adminData->outlet->is_central) ? 'bg-info' : 'bg-light text-dark' }}" style="font-size: 10px; font-weight: 500; padding: 2px 6px;">
                    <i class="fas {{ ($adminData->outlet && !$adminData->outlet->is_central) ? 'fa-store' : 'fa-warehouse' }} me-1"></i>{{ $adminData->outlet ? $adminData->outlet->name : 'Central HQ' }}
                </span>
            </span>
            <i class="mdi mdi-chevron-down d-none d-xl-inline-block ms-1"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-end">
            <!-- item-->
            <a class="dropdown-item" href="{{ route('admin.profile') }}"><i class="ri-user-line align-middle me-1"></i> Profile</a>
            <a class="dropdown-item" href="{{ route('change.password') }}"><i class="ri-wallet-2-line align-middle me-1"></i> Change Password</a>
            @if(Auth::user()->isCentral() && Auth::user()->can('settings-manage'))
            <a class="dropdown-item d-block" href="{{ route('settings.index') }}"><i class="ri-settings-2-line align-middle me-1"></i> Settings</a>
            @endif
            <a class="dropdown-item d-block" href="{{ asset('docs/DarpPOS_User_Manual.pdf') }}" target="_blank"><i class="ri-book-read-line align-middle me-1"></i> User Manual (PDF)</a>
            <a class="dropdown-item" href="#"><i class="ri-lock-unlock-line align-middle me-1"></i> Lock screen</a>
            <div class="dropdown-divider"></div>

            <form id="header-logout-form" action="{{ route('admin.logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
            <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="event.preventDefault(); document.getElementById('header-logout-form').submit();">
                <i class="ri-shut-down-line align-middle me-1 text-danger"></i> Logout
            </a>
        </div>
    </div>

                        
            
                    </div>
                </div>

            </header>
