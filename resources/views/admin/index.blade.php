@extends('layout.main')
@section('title') {{'Dashboard'}} @endsection

<style>
    .dashboard-metric-shell {
        margin-right: 0;
        margin-left: 0;
        padding: 18px 10px 4px;
        border: 1px solid #e5edf7;
        border-radius: 18px;
        background: linear-gradient(180deg, #f8fbff 0%, #f3f6fb 100%);
        box-shadow: 0 10px 24px rgba(24, 42, 76, 0.06);
    }

    .dashboard-metric-card {
        --metric-accent: #2563eb;
        --metric-bg: #eef3ff;
        min-height: 112px;
        border: 1px solid color-mix(in srgb, var(--metric-accent) 18%, #fff);
        border-radius: 14px;
        background: var(--metric-bg) !important;
        box-shadow: 0 5px 14px rgba(24, 42, 76, 0.06);
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .dashboard-metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 9px 20px rgba(24, 42, 76, 0.1);
    }

    .dashboard-metric-card .card-body {
        padding: 1rem;
    }

    .dashboard-metric-card p {
        color: var(--metric-accent) !important;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .dashboard-metric-card h4 {
        color: var(--metric-accent) !important;
        font-size: 1.18rem;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
    }

    .dashboard-metric-card .avatar-title {
        border: 1px solid rgba(255, 255, 255, 0.85);
        background: rgba(255, 255, 255, 0.72) !important;
        color: var(--metric-accent) !important;
    }

    .metric-trade {
        --metric-accent: #2a3f79;
        --metric-bg: #eef3ff;
    }

    .metric-cash {
        --metric-accent: #17643f;
        --metric-bg: #e8f8ef;
    }

    .metric-balance {
        --metric-accent: #8a4520;
        --metric-bg: #fff4ec;
    }

    .metric-stock {
        --metric-accent: #0f766e;
        --metric-bg: #e9f7f5;
    }

    .dashboard-surface-card {
        border: 1px solid #e5edf7;
        border-radius: 14px;
        background: linear-gradient(180deg, #f8fbff 0%, #f3f6fb 100%) !important;
        box-shadow: 0 10px 24px rgba(24, 42, 76, 0.06);
        overflow: hidden;
    }

    .dashboard-surface-card .card-body {
        padding: 1.25rem;
    }

    .dashboard-surface-header {
        border-bottom: 1px solid #e5edf7;
        background: #fff;
        color: #1c2a39;
    }

    .dashboard-email-card .btn {
        border: 0;
        background: #0f766e;
        color: #fff;
        font-weight: 600;
    }

    @media (max-width: 575.98px) {
        .dashboard-metric-shell {
            padding: 14px 4px 2px;
            border-radius: 14px;
        }

        .dashboard-metric-card {
            min-height: 104px;
        }
    }

    .instruction-card {
        border: 1px solid #f5c2c7;
        border-radius: 12px;
        overflow: hidden;
    }

    .instruction-header {
        background: linear-gradient(90deg, teal 0%, teal 100%);
        color: #fff;
        padding: 12px 18px;
        font-weight: 700;
        letter-spacing: 0.4px;
    }

    .instruction-welcome {
        font-size: 1.65rem;
        font-weight: 700;
        margin-bottom: 0.35rem;
    }

    .instruction-note {
        color: #6b7280;
        margin-bottom: 1rem;
    }

    .instruction-quick-actions .btn {
        margin: 0 10px 10px 0;
    }
</style>

@section('content')
<div class="page-content">
<div class="container-fluid">

<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Dashboard</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript: void(0);">{{ $company->name }} | POS</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- end page title -->
  
@can('dashboard-view')
    <div class="row mb-2 dashboard-metric-shell finance-shell">
        <div class="col-12">
            <h5 class="mb-3">Finance Snapshot</h5>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card dashboard-metric-card metric-trade">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-truncate font-size-14 mb-2" style="color:#fff;">Total Sales</p>
                            <h4 class="mb-2" style="color:#fff;">{{ number_format((float) $total_sales, 2) }}</h4>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-light text-primary rounded-3">
                                <i class="ri-shopping-cart-2-line font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card dashboard-metric-card metric-trade">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-truncate font-size-14 mb-2" style="color:#fff;">Total Purchases</p>
                            <h4 class="mb-2" style="color:#fff;">{{ number_format((float) $total_purchases, 2) }}</h4>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-light text-primary rounded-3">
                                <i class="ri-shopping-bag-3-line font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card dashboard-metric-card metric-cash">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-truncate font-size-14 mb-2" style="color:#fff;">Cash In Hand</p>
                            <h4 class="mb-2" style="color:#fff;">{{ number_format((float) $cash_in_hand, 2) }}</h4>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-light text-success rounded-3">
                                <i class="ri-wallet-3-line font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card dashboard-metric-card metric-cash">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-truncate font-size-14 mb-2" style="color:#fff;">Cash At Bank</p>
                            <h4 class="mb-2" style="color:#fff;">{{ number_format((float) $cash_at_bank, 2) }}</h4>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-light text-primary rounded-3">
                                <i class="ri-bank-line font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card dashboard-metric-card metric-balance">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-truncate font-size-14 mb-2" style="color:#fff;">Total Expenses</p>
                            <h4 class="mb-2" style="color:#fff;">{{ number_format((float) $total_expenses, 2) }}</h4>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-light text-danger rounded-3">
                                <i class="ri-file-list-3-line font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card dashboard-metric-card metric-balance">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-truncate font-size-14 mb-2" style="color:#fff;">Debtors Balance</p>
                            <h4 class="mb-2" style="color:#fff;">{{ number_format((float) $debtors_balance, 2) }}</h4>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-light text-warning rounded-3">
                                <i class="ri-user-follow-line font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card dashboard-metric-card metric-balance">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-truncate font-size-14 mb-2" style="color:#fff;">Creditors Balance</p>
                            <h4 class="mb-2" style="color:#fff;">{{ number_format((float) $creditors_balance, 2) }}</h4>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-light text-info rounded-3">
                                <i class="ri-user-unfollow-line font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3 mb-2 dashboard-metric-shell inventory-shell">
        <div class="col-12">
            <h5 class="mb-3">Inventory Snapshot</h5>
        </div>

        <div class="col-xl-6 col-md-6">
            <div class="card dashboard-metric-card metric-stock">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-truncate font-size-14 mb-2" style="color:#fff;">Total Stocks Quantity</p>
                            <h4 class="mb-2" style="color:#fff;">{{ number_format((float) $total_stocks_quantity, 0) }}</h4>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-light text-primary rounded-3">
                                <i class="ri-stack-line font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-md-6">
            <div class="card dashboard-metric-card metric-stock">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-truncate font-size-14 mb-2" style="color:#fff;">Stock Value</p>
                            <h4 class="mb-2" style="color:#fff;">{{ number_format((float) $total_stock_value, 2) }}</h4>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-light text-success rounded-3">
                                <i class="ri-coins-line font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Transaction Email Widget -->
    @if(config('pos.enable_daily_email_report', false))
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card dashboard-surface-card dashboard-email-card">
                <div class="card-header dashboard-surface-header">
                    <h5 class="mb-0">
                        <i class="ri-mail-send-line me-2"></i>Daily Transaction Report
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-12">
                            <p class="mb-2"><small><strong>Status:</strong></small></p>
                            <div id="emailStatus">
                                <span class="badge" style="background: rgba(255, 255, 255, 0.25); color: #fff;">Loading...</span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <p class="mb-2" style="color: #fff;"><small><strong>Quick Actions:</strong></small></p>
                            <button 
                                type="button" 
                                class="btn btn-sm"
                                id="sendEmailBtn"
                                onclick="sendDailyTransactionEmail()"
                            >
                                <i class="ri-mail-send-line me-1"></i>Send Email Now
                            </button>
                            <small class="text-muted d-block mt-2">
                                Scheduled time: {{ str_pad(config('pos.daily_email_report_time', 18), 2, '0', STR_PAD_LEFT) }}:00
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Re-Stock Alert -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card dashboard-surface-card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Re-Stock Alert</h4>
                    <table id="datatable" class="table table-bordered dt-responsive nowrap" style="width:100%;">
                        <thead>
                            <tr>
                                <th>Sl</th>
                                <th>Name</th>
                                <th>Remaining Stock</th>           
                                <th>Stock Level</th>
                                <th>Stock Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $key => $product)
                            <tr style="color:red">
                                <td>{{ $key+1 }}</td>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->quantity }}</td>
                                <td>{{ $product->stock_level }}</td>
                                <td>
                                    @if($product->quantity <= $product->stock_level)
                                        <div class="font-size-13">
                                            <a href="{{ route('purchases.create') }}" title="Reorder Product">
                                                <i class="ri-checkbox-blank-circle-fill font-size-10 text-danger align-middle me-2"></i>Reorder
                                            </a>
                                        </div>
                                    @else
                                        <div class="font-size-13">
                                            <i class="ri-checkbox-blank-circle-fill font-size-10 text-success align-middle me-2"></i>OK
                                        </div>
                                    @endif
                                </td> 
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div><!-- end card-body -->
            </div><!-- end card -->
        </div><!-- end col -->
    </div><!-- end row -->
    @else
    <div class="row">
        <div class="col-12">
            <div class="card instruction-card">
                <div class="instruction-header">
                    ATTENTION
                </div>
                <div class="card-body">
                    <h2 class="instruction-welcome">
                        Welcome, {{ auth()->user()->username ?? auth()->user()->name ?? 'User' }}
                    </h2>

                    <p class="instruction-note">Follow these quick instructions to continue your daily operations.</p>

                    <div class="instruction-quick-actions mb-3">
                        <a href="{{ route('change.password') }}" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-key"></i> Change Password
                        </a>

                        @can('invoice-create')
                        <a href="{{ route('invoices.create') }}" class="btn btn-danger btn-sm">
                            <i class="fas fa-cart-plus"></i> Create Sale
                        </a>
                        @endcan

                        @can('invoice-list')
                        <a href="{{ route('invoices.index') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-list"></i> All Sales
                        </a>
                        <a href="{{ route('print.daily_total_sales') }}" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-calendar-day"></i> Daily Sales
                        </a>
                        @endcan

                        @can('cash-reconciliation-create')
                        <a href="{{ route('cash-reconciliations.create') }}" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-balance-scale"></i> Create Reconciliation
                        </a>
                        @endcan

                        @can('cash-reconciliation-list')
                        <a href="{{ route('cash-reconciliations.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-file-invoice-dollar"></i> Reconciled List
                        </a>
                        @endcan
                    </div>

                    <ol class="mb-0" style="line-height:1.9;">
                        <li>Change your password if you have not by clicking on the profile image at the top right corner.</li>
                        <li>To start selling, Click Manage Transactions -&gt; Sales -&gt; Create or the Cart Icon on the topbar (1st Icon).</li>
                        <li>To view your Sales, Click Manage Transactions -&gt; Sales -&gt; All Sales or on the Sales Report Icon on the topbar (2nd Icon).</li>
                        <li>To view your daily Sales, Click Manage Transactions -&gt; Sales -&gt; Daily Total Sales.</li>
                        <li>To balance for the day, Click Manage Transactions -&gt; Cash Reconciliation -&gt; Create or the Cash Reconciliation Icon on the topbar (3rd Icon).</li>
                        <li>To view Cash Reconciliation, Click Manage Transactions -&gt; Cash Reconciliation -&gt; Reconciled or on the Cash Reconciliation Report Icon on the topbar (4th Icon).</li>
                        <li style="color:red;">Always Logout when leaving your desk by clicking on the profile image at the top right corner and selecting "Logout".</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endcan

</div><!-- end container-fluid -->
</div><!-- end page-content -->
@endsection

@section('scripts')
<script>
    // Daily Transaction Email Functions
    function checkEmailStatus() {
        fetch('{{ route("daily-transaction-email.status") }}')
            .then(response => response.json())
            .then(data => {
                const statusDiv = document.getElementById('emailStatus');
                const sendBtn = document.getElementById('sendEmailBtn');
                
                if (!data.enabled) {
                    statusDiv.innerHTML = '<span class="badge bg-secondary">Feature Disabled</span>';
                    sendBtn.disabled = true;
                } else if (data.already_sent_today) {
                    statusDiv.innerHTML = `<span class="badge bg-success">✓ Sent at ${data.last_sent}</span>`;
                    sendBtn.disabled = true;
                    sendBtn.classList.add('disabled');
                } else {
                    statusDiv.innerHTML = '<span class="badge bg-warning">Not sent today</span>';
                    sendBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error checking email status:', error);
                document.getElementById('emailStatus').innerHTML = '<span class="badge bg-danger">Error checking status</span>';
            });
    }

    function sendDailyTransactionEmail() {
        const btn = document.getElementById('sendEmailBtn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="ri-loader-4-line me-1 spinner"></i>Sending...';

        fetch('{{ route("daily-transaction-email.send") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 200) {
                Notiflix.Notify.success(data.message, {timeout: 4000});
                setTimeout(() => {
                    checkEmailStatus();
                }, 1000);
            } else {
                Notiflix.Notify.failure(data.message, {timeout: 4000});
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Notiflix.Notify.failure('Error sending email. Please try again.', {timeout: 4000});
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }

    // Check email status on page load if feature is enabled
    @if(config('pos.enable_daily_email_report', false))
        checkEmailStatus();
    @endif
</script>

@endsection

