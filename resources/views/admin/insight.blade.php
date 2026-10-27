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
        padding: 14px;
    }

    .insight-card .card-title {
        color: #0f172a;
        font-weight: 700;
    }

    .insight-card canvas {
        background: rgba(255, 255, 255, 0.62);
        border-radius: 8px;
    }
</style>
@endsection

@section('content')
<div class="page-content">
<div class="container-fluid">

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Insight</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="javascript: void(0);">{{ $company->name }} | POS</a></li>
                    <li class="breadcrumb-item active">Insight</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@can('dashboard-view')
    <div class="row mt-1">
        <div class="col-xl-6">
            <div class="card insight-card">
                <div class="card-body metric-chart-card">
                    <h4 class="card-title">Hybrid Sales Trend (Last 30 Days)</h4>
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
                    <h4 class="card-title">Hybrid Purchase Trend (Last 30 Days)</h4>
                    <div style="position: relative; height: 320px;">
                        <canvas id="purchaseTrendChart"></canvas>
                    </div>
                    <small class="text-muted d-block mt-2">Compares purchase invoice value, cash paid, and purchase credit due per day with a dashed 7-day moving average on purchase invoice value.</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-xl-6">
            <div class="card insight-card">
                <div class="card-body metric-chart-card">
                    <h4 class="card-title">Top Category Contribution</h4>
                    <div style="position: relative; height: 360px;">
                        <canvas id="topCategoriesChart"></canvas>
                    </div>
                    <small class="text-muted d-block mt-2">Ranks the highest-performing categories by total sales value so you can see which categories contribute most to revenue.</small>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card insight-card">
                <div class="card-body metric-chart-card">
                    <h4 class="card-title">Stock Health Risk (Shortfall)</h4>
                    <div style="position: relative; height: 360px;">
                        <canvas id="stockRiskChart"></canvas>
                    </div>
                    <small class="text-muted d-block mt-2">Displays products below stock level and the quantity shortfall, helping you prioritize urgent restocking.</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-xl-6">
            <div class="card insight-card">
                <div class="card-body metric-chart-card">
                    <h4 class="card-title">Monthly Hybrid Overview ({{ $currentYear }})</h4>
                    <div style="position: relative; height: 340px;">
                        <canvas id="monthlySalesChart"></canvas>
                    </div>
                    <small class="text-muted d-block mt-2">Shows invoice value, cash collected, and credit due by month for the current year.</small>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card insight-card">
                <div class="card-body metric-chart-card">
                    <h4 class="card-title">Monthly Purchase Hybrid Overview ({{ $currentYear }})</h4>
                    <div style="position: relative; height: 340px;">
                        <canvas id="monthlyPurchaseChart"></canvas>
                    </div>
                    <small class="text-muted d-block mt-2">Shows purchase invoice value, cash paid, and purchase credit due by month for the current year.</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card insight-card">
                <div class="card-body metric-chart-card">
                    <h4 class="card-title">Expense Insight (Spent Amount by Category)</h4>
                    <div style="position: relative; height: 340px;">
                        <canvas id="expenseInsightChart"></canvas>
                    </div>
                    <small class="text-muted d-block mt-2">
                        @if(empty($expenseInsightTotals))
                            No expense insight available yet. Categories appear here once spending is above 0.
                        @else
                            Shows how much has been spent per expense category.
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
