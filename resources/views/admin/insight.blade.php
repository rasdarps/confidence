@extends('layout.main')
@section('title') {{ 'Insight' }} @endsection

@section('styles')
<style>
    .insight-card {
        border: 1px solid #cbd5e1 !important;
        border-radius: 12px;
        background: linear-gradient(135deg, #f8fafc, #eef2ff) !important;
        box-shadow: 0 8px 20px rgba(24, 42, 76, 0.07);
        overflow: hidden;
    }

    .insight-card .metric-chart-card {
        padding: 16px;
    }

    .insight-card .card-title {
        color: #0f172a;
        font-weight: 700;
        font-size: 15px;
    }

    .insight-card canvas {
        background: rgba(255, 255, 255, 0.62);
        border-radius: 8px;
    }

    .insight-kpi-card {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #ffffff;
        padding: 14px 16px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        height: 100%;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .insight-kpi-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
    }
</style>
@endsection

@section('content')
<div class="page-content">
<div class="container-fluid">

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">
                Insight &amp; Analytics
                @if(isset($selectedOutlet) && $selectedOutlet)
                    <span class="badge {{ $selectedOutlet->is_central ? 'bg-primary' : 'bg-info text-dark' }} ms-2 font-size-12">
                        <i class="ri-store-2-line me-1"></i>{{ $selectedOutlet->name }} ({{ $selectedOutlet->code }}){{ $selectedOutlet->is_central ? ' — Central HQ' : ' — Branch Station' }}
                    </span>
                @elseif(!empty($canFilterOutlets))
                    <span class="badge bg-primary ms-2 font-size-12">
                        <i class="ri-building-line me-1"></i>All Outlets &amp; Central Warehouse (Consolidated)
                    </span>
                @endif
            </h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ $company->name ?? 'POS' }}</a></li>
                    <li class="breadcrumb-item active">Insight</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@can('dashboard-view')
    @if(!empty($canFilterOutlets) && isset($outlets) && $outlets->count() > 0)
    <!-- Multi-Outlet Filter Bar (Requires dashboard-outlet-filter permission) -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card dashboard-surface-card mb-0 shadow-sm" style="border: 1px solid #cbd5e1; border-radius: 10px;">
                <div class="card-body py-2 px-3">
                    <form method="GET" action="{{ route('insight') }}" class="row align-items-center g-2 mb-0">
                        <div class="col-md-auto">
                            <span class="fw-bold text-dark font-size-13">
                                <i class="ri-store-2-line text-primary me-1"></i>Filter Insight by Outlet / Location:
                            </span>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <select name="outlet_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">All Outlets &amp; Central Warehouse (Consolidated)</option>
                                @foreach($outlets as $outlet)
                                    <option value="{{ $outlet->id }}" {{ (int) ($selectedOutletId ?? 0) === (int) $outlet->id ? 'selected' : '' }}>
                                        {{ $outlet->name }} ({{ $outlet->code }}){{ $outlet->is_central ? ' — Central HQ' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @if(!empty($selectedOutletId))
                        <div class="col-md-auto">
                            <a href="{{ route('insight') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="ri-refresh-line me-1"></i>Reset to Consolidated
                            </a>
                        </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
    @elseif(!empty($selectedOutlet))
    <!-- Locked Scope Banner for Branch Staff -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="alert alert-info border-0 shadow-sm mb-0 py-2 d-flex align-items-center justify-content-between" style="background: #f0fdfa; border-left: 4px solid #0F766E !important; color: #134e4a;">
                <div>
                    <i class="ri-store-2-line me-1 font-size-15" style="color: #0F766E;"></i>
                    <strong>Branch Scoped Analytics:</strong> Analytics and trends are scoped strictly to <strong>{{ $selectedOutlet->name }} ({{ $selectedOutlet->code }})</strong>.
                </div>
                <span class="badge" style="background: #0F766E; color: #fff;">Branch Station</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Multi-Outlet Scope KPI Summary Strip -->
    <div class="row mb-4 g-2">
        <div class="col-xl-3 col-md-6">
            <div class="insight-kpi-card" style="border-left: 4px solid #0F766E;">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted font-size-11 font-weight-bold text-uppercase">Sales (Invoices)</div>
                        <div class="font-size-19 font-weight-bold text-dark mt-1">Gh¢ {{ number_format((float) ($total_sales ?? 0), 2) }}</div>
                        <small class="text-muted font-size-11">{{ number_format($total_invoice ?? 0) }} invoices posted</small>
                    </div>
                    <span class="badge bg-light text-dark border font-size-10">
                        {{ isset($selectedOutlet) && $selectedOutlet ? $selectedOutlet->code : 'All Outlets' }}
                    </span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="insight-kpi-card" style="border-left: 4px solid #16a34a;">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted font-size-11 font-weight-bold text-uppercase">Cash Collected vs Due</div>
                        <div class="font-size-19 font-weight-bold text-success mt-1">Gh¢ {{ number_format((float) ($total_cash ?? 0), 2) }}</div>
                        <small class="text-danger font-size-11">Due Credit: Gh¢ {{ number_format((float) ($total_credit_sales ?? 0), 2) }}</small>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle font-size-10">Receipts</span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="insight-kpi-card" style="border-left: 4px solid #0284c7;">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted font-size-11 font-weight-bold text-uppercase">Active Stock Balance</div>
                        <div class="font-size-19 font-weight-bold text-dark mt-1">{{ number_format((float) ($total_stock_balance ?? 0), 2) }} <span class="font-size-12 text-muted fw-normal">units</span></div>
                        <small class="text-muted font-size-11">Cost Value: Gh¢ {{ number_format((float) ($total_stock_value ?? 0), 2) }}</small>
                    </div>
                    <span class="badge bg-info-subtle text-info border border-info-subtle font-size-10">Inventory</span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="insight-kpi-card" style="border-left: 4px solid #d97706;">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted font-size-11 font-weight-bold text-uppercase">Operating Expenses</div>
                        <div class="font-size-19 font-weight-bold text-warning mt-1">Gh¢ {{ number_format((float) ($total_expenses ?? 0), 2) }}</div>
                        <small class="text-muted font-size-11">
                            @if(isset($pendingTransfersCount) && $pendingTransfersCount > 0)
                                <span class="text-primary font-weight-bold"><i class="ri-truck-line me-1"></i>{{ $pendingTransfersCount }} In-Transit Transfers</span>
                            @else
                                Location vouchers
                            @endif
                        </small>
                    </div>
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle font-size-10">Expenses</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1: Sales Trend & Purchase Trend -->
    <div class="row mt-1">
        <div class="col-xl-6">
            <div class="card insight-card">
                <div class="card-body metric-chart-card">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <h4 class="card-title mb-0">Hybrid Sales Trend (Last 30 Days)</h4>
                        @if(isset($selectedOutlet) && $selectedOutlet)
                            <span class="badge bg-info text-dark font-size-11"><i class="ri-store-2-line me-1"></i>{{ $selectedOutlet->name }}</span>
                        @else
                            <span class="badge bg-secondary font-size-11"><i class="ri-building-line me-1"></i>All Outlets</span>
                        @endif
                    </div>
                    <div style="position: relative; height: 320px;">
                        <canvas id="salesTrendChart"></canvas>
                    </div>
                    <small class="text-muted d-block mt-2">Compares invoice value, cash collected, and credit due per day with a dashed 7-day moving average on invoice value.</small>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card insight-card">
                <div class="card-body metric-chart-card">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <h4 class="card-title mb-0">Hybrid Purchase Trend (Last 30 Days)</h4>
                        <span class="badge bg-primary text-white font-size-11"><i class="ri-shield-check-line me-1"></i>Central HQ Procurement</span>
                    </div>
                    <div style="position: relative; height: 320px;">
                        <canvas id="purchaseTrendChart"></canvas>
                    </div>
                    <small class="text-muted d-block mt-2">Central Procurement: Supplier purchases, supplier payments, and vendor credits are managed centrally at Central Warehouse (HQ) on behalf of retail branches.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2: Category Revenue Contribution & Stock Health Risk -->
    <div class="row mt-4">
        <div class="col-xl-6">
            <div class="card insight-card">
                <div class="card-body metric-chart-card">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <h4 class="card-title mb-0">Top Category Contribution</h4>
                        @if(isset($selectedOutlet) && $selectedOutlet)
                            <span class="badge bg-info text-dark font-size-11"><i class="ri-store-2-line me-1"></i>{{ $selectedOutlet->name }}</span>
                        @else
                            <span class="badge bg-secondary font-size-11"><i class="ri-building-line me-1"></i>Consolidated</span>
                        @endif
                    </div>
                    <div style="position: relative; height: 360px;">
                        <canvas id="topCategoriesChart"></canvas>
                    </div>
                    <small class="text-muted d-block mt-2">Ranks the highest-performing categories by total sales value for this location scope to show which lines drive revenue.</small>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card insight-card">
                <div class="card-body metric-chart-card">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <h4 class="card-title mb-0">Stock Health Risk (Shortfall)</h4>
                        @if(isset($selectedOutlet) && $selectedOutlet)
                            <span class="badge bg-info text-dark font-size-11"><i class="ri-store-2-line me-1"></i>{{ $selectedOutlet->name }}</span>
                        @else
                            <span class="badge bg-secondary font-size-11"><i class="ri-building-line me-1"></i>All Outlet Locations</span>
                        @endif
                    </div>
                    <div style="position: relative; height: 360px;">
                        <canvas id="stockRiskChart"></canvas>
                    </div>
                    <small class="text-muted d-block mt-2">Displays products below minimum re-order level and their quantity shortfall to prioritize stock transfers and replenishments.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 3: Annual Monthly Sales & Purchases -->
    <div class="row mt-4">
        <div class="col-xl-6">
            <div class="card insight-card">
                <div class="card-body metric-chart-card">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <h4 class="card-title mb-0">Monthly Sales Hybrid Overview ({{ $currentYear }})</h4>
                        @if(isset($selectedOutlet) && $selectedOutlet)
                            <span class="badge bg-info text-dark font-size-11"><i class="ri-store-2-line me-1"></i>{{ $selectedOutlet->name }}</span>
                        @else
                            <span class="badge bg-secondary font-size-11"><i class="ri-building-line me-1"></i>All Outlets</span>
                        @endif
                    </div>
                    <div style="position: relative; height: 340px;">
                        <canvas id="monthlySalesChart"></canvas>
                    </div>
                    <small class="text-muted d-block mt-2">Shows monthly invoice revenue, cash collected, and credit due for {{ $currentYear }} within this scope.</small>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card insight-card">
                <div class="card-body metric-chart-card">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <h4 class="card-title mb-0">Monthly Purchase Hybrid Overview ({{ $currentYear }})</h4>
                        <span class="badge bg-primary text-white font-size-11"><i class="ri-shield-check-line me-1"></i>Central HQ Procurement</span>
                    </div>
                    <div style="position: relative; height: 340px;">
                        <canvas id="monthlyPurchaseChart"></canvas>
                    </div>
                    <small class="text-muted d-block mt-2">Central Procurement: Purchase invoice totals, cash payments, and payables by month for {{ $currentYear }}.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 4: Expense Insight -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card insight-card">
                <div class="card-body metric-chart-card">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <h4 class="card-title mb-0">Expense Insight (Spent Amount by Category)</h4>
                        @if(isset($selectedOutlet) && $selectedOutlet)
                            <span class="badge bg-info text-dark font-size-11"><i class="ri-store-2-line me-1"></i>{{ $selectedOutlet->name }}</span>
                        @else
                            <span class="badge bg-secondary font-size-11"><i class="ri-building-line me-1"></i>All Locations</span>
                        @endif
                    </div>
                    <div style="position: relative; height: 340px;">
                        <canvas id="expenseInsightChart"></canvas>
                    </div>
                    <small class="text-muted d-block mt-2">
                        @if(empty($expenseInsightTotals))
                            No expense insight available yet. Categories appear here once spending is recorded.
                        @else
                            Shows operating spending per expense category for this location scope.
                        @endif
                    </small>
                </div>
            </div>
        </div>
    </div>
@endcan

</div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('vendor/chartjs/chart.umd-4.4.3.min.js') }}"></script>
<script src="{{ asset('vendor/chartjs/chartjs-plugin-datalabels-2.2.0.min.js') }}"></script>
<script>
    const isMobileChartView = window.matchMedia('(max-width: 767.98px)').matches;
    const isWideChartView = window.matchMedia('(min-width: 1600px)').matches;

    function money(value) {
        return 'Gh¢ ' + Number(value || 0).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    const salesTrendEl = document.getElementById('salesTrendChart');
    if (salesTrendEl) {
        new Chart(salesTrendEl.getContext('2d'), {
            type: 'line',
            data: {
                labels: @json($salesTrendLabels),
                datasets: [
                    {
                        label: 'Invoice Value',
                        data: @json($salesTrendInvoiceTotals),
                        borderColor: '#0f766e',
                        backgroundColor: 'rgba(20, 184, 166, 0.16)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 2,
                    },
                    {
                        label: 'Cash Collected',
                        data: @json($salesTrendCashCollectedTotals),
                        borderColor: '#16a34a',
                        backgroundColor: 'rgba(22, 163, 74, 0.10)',
                        fill: false,
                        tension: 0.35,
                        pointRadius: 2,
                    },
                    {
                        label: 'Credit Due',
                        data: @json($salesTrendCreditDueTotals),
                        borderColor: '#dc2626',
                        backgroundColor: 'rgba(220, 38, 38, 0.10)',
                        fill: false,
                        tension: 0.35,
                        pointRadius: 2,
                    },
                    {
                        label: '7-Day Invoice Average',
                        data: @json($salesTrendMovingAverage),
                        borderColor: '#0d9488',
                        backgroundColor: 'rgba(13, 148, 136, 0.08)',
                        fill: false,
                        tension: 0.35,
                        pointRadius: 0,
                        borderDash: [6, 4],
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    datalabels: {
                        display: false,
                    },
                    legend: {
                        labels: {
                            boxWidth: isMobileChartView ? 8 : 12,
                            font: {
                                size: isMobileChartView ? 10 : 12,
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: isMobileChartView ? 6 : (isWideChartView ? 15 : 10),
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            maxTicksLimit: isMobileChartView ? 5 : 7,
                            callback: function(value) {
                                return money(value);
                            }
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    }

    const purchaseTrendEl = document.getElementById('purchaseTrendChart');
    if (purchaseTrendEl) {
        new Chart(purchaseTrendEl.getContext('2d'), {
            type: 'line',
            data: {
                labels: @json($purchaseTrendLabels),
                datasets: [
                    {
                        label: 'Purchase Invoice Value',
                        data: @json($purchaseTrendInvoiceTotals),
                        borderColor: '#1d4ed8',
                        backgroundColor: 'rgba(29, 78, 216, 0.16)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 2,
                    },
                    {
                        label: 'Cash Paid',
                        data: @json($purchaseTrendCashPaidTotals),
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.10)',
                        fill: false,
                        tension: 0.35,
                        pointRadius: 2,
                    },
                    {
                        label: 'Purchase Credit Due',
                        data: @json($purchaseTrendCreditDueTotals),
                        borderColor: '#b91c1c',
                        backgroundColor: 'rgba(185, 28, 28, 0.10)',
                        fill: false,
                        tension: 0.35,
                        pointRadius: 2,
                    },
                    {
                        label: '7-Day Purchase Invoice Average',
                        data: @json($purchaseTrendMovingAverage),
                        borderColor: '#1e40af',
                        backgroundColor: 'rgba(30, 64, 175, 0.08)',
                        fill: false,
                        tension: 0.35,
                        pointRadius: 0,
                        borderDash: [6, 4],
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    datalabels: {
                        display: false,
                    },
                    legend: {
                        labels: {
                            boxWidth: isMobileChartView ? 8 : 12,
                            font: {
                                size: isMobileChartView ? 10 : 12,
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: isMobileChartView ? 6 : (isWideChartView ? 15 : 10),
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            maxTicksLimit: isMobileChartView ? 5 : 7,
                            callback: function(value) {
                                return money(value);
                            }
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    }

    const topCategoriesEl = document.getElementById('topCategoriesChart');
    if (topCategoriesEl) {
        new Chart(topCategoriesEl.getContext('2d'), {
            type: 'bar',
            data: {
                labels: @json($topCategoryLabels),
                datasets: [{
                    label: 'Sales (Gh¢)',
                    data: @json($topCategoryTotals),
                    backgroundColor: '#14b8a6',
                    borderRadius: 6,
                    maxBarThickness: 40,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    datalabels: {
                        display: !isMobileChartView,
                        anchor: 'end',
                        align: 'right',
                        color: '#0f172a',
                        formatter: function(value) {
                            return money(value);
                        }
                    },
                    legend: {
                        display: false,
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            callback: function(value) {
                                var label = this.getLabelForValue(value);
                                if (typeof label !== 'string') {
                                    return label;
                                }
                                return label.length > 20 ? label.slice(0, 20) + '...' : label;
                            }
                        }
                    },
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return money(value);
                            }
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    }

    const stockRiskEl = document.getElementById('stockRiskChart');
    if (stockRiskEl) {
        new Chart(stockRiskEl.getContext('2d'), {
            type: 'bar',
            data: {
                labels: @json($stockRiskLabels),
                datasets: [{
                    label: 'Stock Shortfall Qty',
                    data: @json($stockRiskShortfall),
                    backgroundColor: '#dc2626',
                    borderRadius: 6,
                    maxBarThickness: 40,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    datalabels: {
                        display: !isMobileChartView,
                        anchor: 'end',
                        align: 'right',
                        color: '#0f172a',
                        formatter: function(value) {
                            return Number(value || 0).toLocaleString(undefined, { maximumFractionDigits: 2 });
                        }
                    },
                    legend: {
                        display: false,
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            callback: function(value) {
                                var label = this.getLabelForValue(value);
                                if (typeof label !== 'string') {
                                    return label;
                                }
                                return label.length > 20 ? label.slice(0, 20) + '...' : label;
                            }
                        }
                    },
                    x: {
                        beginAtZero: true,
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    }

    const monthlySalesEl = document.getElementById('monthlySalesChart');
    if (monthlySalesEl) {
        new Chart(monthlySalesEl.getContext('2d'), {
            type: 'bar',
            data: {
                labels: @json($monthlySalesLabels),
                datasets: [
                    {
                        label: 'Invoice Value (Gh¢)',
                        data: @json($monthlyInvoiceTotals),
                        backgroundColor: '#0f766e',
                        borderColor: '#0f766e',
                        borderRadius: 6,
                        maxBarThickness: 28,
                    },
                    {
                        label: 'Cash Collected (Gh¢)',
                        data: @json($monthlyCashCollectedTotals),
                        backgroundColor: '#16a34a',
                        borderColor: '#16a34a',
                        borderRadius: 6,
                        maxBarThickness: 28,
                    },
                    {
                        label: 'Credit Due (Gh¢)',
                        data: @json($monthlyCreditDueTotals),
                        backgroundColor: '#dc2626',
                        borderColor: '#dc2626',
                        borderRadius: 6,
                        maxBarThickness: 28,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    datalabels: {
                        display: false,
                        anchor: 'end',
                        align: 'end',
                        color: '#0f172a',
                        formatter: function(value) {
                            return money(value);
                        }
                    },
                    legend: {
                        display: true,
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 0,
                            autoSkip: false,
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return money(value);
                            }
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    }

    const monthlyPurchaseEl = document.getElementById('monthlyPurchaseChart');
    if (monthlyPurchaseEl) {
        new Chart(monthlyPurchaseEl.getContext('2d'), {
            type: 'bar',
            data: {
                labels: @json($monthlySalesLabels),
                datasets: [
                    {
                        label: 'Purchase Invoice Value (Gh¢)',
                        data: @json($monthlyPurchaseInvoiceTotals),
                        backgroundColor: '#1d4ed8',
                        borderColor: '#1d4ed8',
                        borderRadius: 6,
                        maxBarThickness: 28,
                    },
                    {
                        label: 'Cash Paid (Gh¢)',
                        data: @json($monthlyPurchaseCashPaidTotals),
                        backgroundColor: '#2563eb',
                        borderColor: '#2563eb',
                        borderRadius: 6,
                        maxBarThickness: 28,
                    },
                    {
                        label: 'Purchase Credit Due (Gh¢)',
                        data: @json($monthlyPurchaseCreditDueTotals),
                        backgroundColor: '#b91c1c',
                        borderColor: '#b91c1c',
                        borderRadius: 6,
                        maxBarThickness: 28,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    datalabels: {
                        display: false,
                    },
                    legend: {
                        display: true,
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 0,
                            autoSkip: false,
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return money(value);
                            }
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    }

    const expenseInsightEl = document.getElementById('expenseInsightChart');
    if (expenseInsightEl) {
        new Chart(expenseInsightEl.getContext('2d'), {
            type: 'bar',
            data: {
                labels: @json($expenseInsightLabels),
                datasets: [{
                    label: 'Spent Amount (Gh¢)',
                    data: @json($expenseInsightTotals),
                    backgroundColor: '#f59e0b',
                    borderRadius: 6,
                    maxBarThickness: 42,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    datalabels: {
                        display: !isMobileChartView,
                        anchor: 'end',
                        align: 'right',
                        color: '#0f172a',
                        formatter: function(value) {
                            return money(value);
                        }
                    },
                    legend: {
                        display: false,
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            callback: function(value) {
                                var label = this.getLabelForValue(value);
                                if (typeof label !== 'string') {
                                    return label;
                                }
                                return label.length > 24 ? label.slice(0, 24) + '...' : label;
                            }
                        }
                    },
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return money(value);
                            }
                        }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    }
</script>
@endsection
