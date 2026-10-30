@extends('layout.main')
@section('title') {{ 'Daily Transaction Report' }} @endsection

@push('styles')
<style>
    .kpi-card {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
    }
    .kpi-icon-circle {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
    .breakdown-card, .control-card {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        background: #ffffff;
    }
    .status-badge-lg {
        font-size: 13px;
        padding: 6px 14px;
        border-radius: 6px;
    }
    .btn-teal {
        background-color: #0F766E !important;
        border-color: #0F766E !important;
        color: #ffffff !important;
    }
    .btn-teal:hover {
        background-color: #0B5F59 !important;
        border-color: #0B5F59 !important;
    }
    .breakdown-table th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 600;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .breakdown-table tr.total-row td {
        background-color: #F0FDFA;
        font-weight: 700;
        color: #0F766E;
        border-top: 2px solid #0F766E;
        border-bottom: 2px solid #0F766E;
    }
</style>
@endpush

@section('content')
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between mb-3">
                    <h4 class="mb-sm-0">Daily Transaction Report (Executive Summary)</h4>
                    <div class="page-title-right d-flex gap-2">
                        <a href="{{ route('daily-transaction-email.logs') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-list me-1"></i> Delivery Audit Logs
                        </a>
                        <a href="{{ route('settings.index') }}" class="btn btn-sm btn-outline-primary" style="color: #0F766E; border-color: #0F766E;">
                            <i class="fas fa-sliders-h me-1"></i> Report Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- end page title -->

        {{-- Master Governance Alert --}}
        <div class="alert alert-info d-flex align-items-center mb-4">
            <i class="fas fa-envelope-open-text fa-2x mr-3 me-3 text-info"></i>
            <div>
                <strong class="font-size-15 d-block">Enterprise Multi-Outlet Reporting:</strong>
                <span class="text-muted">
                    This daily executive report displays the breakdown across individual retail outlets and stations first, followed by consolidated enterprise totals for customer payments, operating expenses, and cashier drawer reconciliations.
                </span>
            </div>
        </div>

        {{-- 1. Station & Branch Performance Breakdown Table --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card breakdown-card">
                    <div class="card-header bg-transparent border-bottom py-3 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-store-alt text-primary font-size-18 me-2"></i>
                            <h5 class="card-title mb-0 font-size-16">Station & Branch Performance Breakdown</h5>
                        </div>
                        <span class="badge bg-light text-muted border font-size-12">Live Today &bull; {{ today()->format('M d, Y') }}</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 breakdown-table">
                                <thead>
                                    <tr>
                                        <th style="padding-left: 20px;">Station / Outlet</th>
                                        <th class="text-center">Type</th>
                                        <th class="text-end">Sales Today (Gh¢)</th>
                                        <th class="text-end">Expenses (Gh¢)</th>
                                        <th class="text-end">Cash Shortage (Gh¢)</th>
                                        <th class="text-end">Net Cash Flow (Gh¢)</th>
                                        <th class="text-center" style="padding-right: 20px;">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="breakdown-table-body">
                                    @php
                                        $breakdownList = $totals['breakdown'] ?? [];
                                        $totalSales = $totals['sales'] ?? 0;
                                        $totalExpenses = $totals['expenses'] ?? 0;
                                        $totalShortage = $totals['shortage'] ?? 0;
                                        $totalNet = $totalSales - $totalExpenses - $totalShortage;
                                    @endphp

                                    @if(count($breakdownList) > 0)
                                        @foreach($breakdownList as $row)
                                            <tr id="outlet-row-{{ $row['outlet_id'] ?? 'unassigned' }}">
                                                <td style="padding-left: 20px;">
                                                    <strong class="font-size-14 text-dark">{{ $row['name'] }}</strong>
                                                    <small class="text-muted d-block font-size-11">{{ $row['code'] }}</small>
                                                </td>
                                                <td class="text-center">
                                                    @if($row['is_central'])
                                                        <span class="badge badge-soft-info" style="color: #0F766E; background-color: #E6FFFA; border: 1px solid #B2F5EA;">
                                                            <i class="fas fa-building me-1"></i> Central HQ
                                                        </span>
                                                    @else
                                                        <span class="badge bg-light text-dark border">
                                                            <i class="fas fa-store me-1"></i> Retail Outlet
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="text-end font-size-14 fw-semibold text-dark row-sales">
                                                    {{ number_format($row['sales'], 2) }}
                                                </td>
                                                <td class="text-end font-size-14 row-expenses" style="color: {{ $row['expenses'] > 0 ? '#D97706' : '#64748B' }};">
                                                    {{ number_format($row['expenses'], 2) }}
                                                </td>
                                                <td class="text-end font-size-14 row-shortage" style="color: {{ $row['shortage'] > 0 ? '#DC2626' : '#64748B' }};">
                                                    {{ number_format($row['shortage'], 2) }}
                                                </td>
                                                <td class="text-end font-size-14 fw-bold row-net" style="color: {{ $row['net'] >= 0 ? '#0F766E' : '#DC2626' }};">
                                                    {{ ($row['net'] >= 0 ? '+' : '') . number_format($row['net'], 2) }}
                                                </td>
                                                <td class="text-center row-status" style="padding-right: 20px;">
                                                    @if($row['shortage'] > 0)
                                                        <span class="badge bg-danger text-white">Shortage Alert</span>
                                                    @elseif($row['has_activity'])
                                                        <span class="badge bg-success text-white">Active Today</span>
                                                    @else
                                                        <span class="badge bg-light text-muted border">No Activity</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">No outlets registered in system.</td>
                                        </tr>
                                    @endif

                                    {{-- Consolidated Footer Row --}}
                                    <tr class="total-row">
                                        <td style="padding-left: 20px;">
                                            <strong class="font-size-15">Consolidated Enterprise Total</strong>
                                            <small class="text-muted d-block font-size-11">All Active Stations Aggregated</small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-dark text-white font-size-11">All Outlets</span>
                                        </td>
                                        <td class="text-end font-size-15 fw-bold" id="total-table-sales">
                                            {{ number_format($totalSales, 2) }}
                                        </td>
                                        <td class="text-end font-size-15 fw-bold text-warning" id="total-table-expenses">
                                            {{ number_format($totalExpenses, 2) }}
                                        </td>
                                        <td class="text-end font-size-15 fw-bold text-danger" id="total-table-shortage">
                                            {{ number_format($totalShortage, 2) }}
                                        </td>
                                        <td class="text-end font-size-15 fw-bold" id="total-table-net" style="color: {{ $totalNet >= 0 ? '#0F766E' : '#DC2626' }};">
                                            {{ ($totalNet >= 0 ? '+' : '') . number_format($totalNet, 2) }}
                                        </td>
                                        <td class="text-center" style="padding-right: 20px;">
                                            <span class="badge bg-success text-white">Consolidated</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. Consolidated KPI Metric Cards --}}
        <div class="row g-3 mb-4">
            <!-- Enterprise Sales -->
            <div class="col-xl-3 col-md-6">
                <div class="card kpi-card h-100 mb-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-uppercase font-size-12 fw-semibold">Enterprise Sales</span>
                                <h4 class="mt-2 mb-0 fw-bold" id="kpi-sales" style="color: #0F766E;">
                                    Gh¢ {{ number_format($totalSales, 2) }}
                                </h4>
                                <small class="text-muted font-size-11">All branch customer payments</small>
                            </div>
                            <div class="kpi-icon-circle" style="background-color: #E6FFFA; color: #0F766E;">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Operating Expenses -->
            <div class="col-xl-3 col-md-6">
                <div class="card kpi-card h-100 mb-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-uppercase font-size-12 fw-semibold">Operating Expenses</span>
                                <h4 class="mt-2 mb-0 fw-bold text-warning" id="kpi-expenses">
                                    Gh¢ {{ number_format($totalExpenses, 2) }}
                                </h4>
                                <small class="text-muted font-size-11">Recorded expense vouchers</small>
                            </div>
                            <div class="kpi-icon-circle" style="background-color: #FEF3C7; color: #D97706;">
                                <i class="fas fa-receipt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cash Drawer Shortage -->
            <div class="col-xl-3 col-md-6">
                <div class="card kpi-card h-100 mb-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-uppercase font-size-12 fw-semibold">Reconciled Shortages</span>
                                <h4 class="mt-2 mb-0 fw-bold text-danger" id="kpi-shortage">
                                    Gh¢ {{ number_format($totalShortage, 2) }}
                                </h4>
                                <small class="text-muted font-size-11">Cashier reconciliation gaps</small>
                            </div>
                            <div class="kpi-icon-circle" style="background-color: #FEE2E2; color: #DC2626;">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Net Cash Flow -->
            <div class="col-xl-3 col-md-6">
                <div class="card kpi-card h-100 mb-0">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-uppercase font-size-12 fw-semibold">Est. Net Cash Flow</span>
                                <h4 class="mt-2 mb-0 fw-bold {{ $totalNet >= 0 ? 'text-success' : 'text-danger' }}" id="kpi-net">
                                    Gh¢ {{ number_format($totalNet, 2) }}
                                </h4>
                                <small class="text-muted font-size-11">Sales - Expenses - Shortages</small>
                            </div>
                            <div class="kpi-icon-circle" style="background-color: #EFF6FF; color: #2563EB;">
                                <i class="fas fa-wallet"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. Dispatch Controls & Scheduler Health Status --}}
        <div class="row">
            <div class="col-lg-7 mb-4">
                <div class="card control-card h-100">
                    <div class="card-header bg-transparent border-bottom py-3 d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0 font-size-16">
                            <i class="ri-mail-send-line me-2 text-primary"></i> Dispatch Status & Controls
                        </h5>
                        <span id="daily-email-status-badge" class="badge status-badge-lg bg-secondary text-white">Checking...</span>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                            <div class="row g-3 mb-4">
                                <div class="col-sm-6">
                                    <div class="p-3 border rounded bg-light">
                                        <small class="text-muted d-block mb-1">Executive Recipient</small>
                                        <strong class="font-size-14 text-dark" id="recipientEmailText">
                                            {{ $company->email ?? 'Company email not set' }}
                                        </strong>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-3 border rounded bg-light">
                                        <small class="text-muted d-block mb-1">Automatic Schedule</small>
                                        <strong class="font-size-14 text-dark" id="autoScheduleText">
                                            Daily at {{ str_pad($autoSendHour, 2, '0', STR_PAD_LEFT) }}:00
                                            ({{ $autoSendHour >= 12 ? ($autoSendHour == 12 ? '12 PM' : ($autoSendHour - 12) . ' PM') : $autoSendHour . ' AM' }})
                                        </strong>
                                    </div>
                                </div>
                            </div>

                            <p class="text-muted font-size-13">
                                <i class="fas fa-info-circle me-1 text-info"></i>
                                Manual dispatch is enabled when the feature is active and today's report has not yet been sent. The report includes both the outlet breakdown table and consolidated enterprise aggregates.
                            </p>
                        </div>

                        <div class="mt-3 pt-3 border-top d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-muted d-block" id="daily-email-meta">Checking status...</small>
                            </div>
                            @can('daily-transaction-email-send')
                                <button
                                    id="send-daily-email-btn"
                                    type="button"
                                    class="btn btn-teal px-4 py-2 font-size-14"
                                    onclick="sendDailyTransactionEmailNow()"
                                    disabled
                                >
                                    <i class="ri-mail-send-line me-1"></i> Send Report Now
                                </button>
                            @else
                                <span class="badge bg-secondary p-2">Manual Send Restricted</span>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>

            {{-- Scheduler Engine Health --}}
            <div class="col-lg-5 mb-4">
                <div class="card control-card h-100">
                    <div class="card-header bg-transparent border-bottom py-3 d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0 font-size-16">
                            <i class="fas fa-heartbeat me-2 text-danger"></i> Background Scheduler Health
                        </h5>
                        <span id="scheduler-health-badge" class="badge status-badge-lg bg-secondary text-white">Checking...</span>
                    </div>
                    <div class="card-body">
                        <div class="p-3 border rounded mb-3 bg-light">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted font-size-13">Cron Job Status:</span>
                                <strong id="schedulerStatusLabel" class="text-dark">Active</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted font-size-13">Last Heartbeat:</span>
                                <span id="schedulerLastRunText" class="text-muted font-size-13">
                                    {{ $schedulerHealth['last_run_at'] ?? 'Never' }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted font-size-13">Heartbeat Interval:</span>
                                <span class="badge bg-info text-dark font-size-11">Every minute</span>
                            </div>
                        </div>

                        <p class="text-muted font-size-12 mb-3">
                            The scheduler runs automatically in the background. When healthy, it checks at the designated time ({{ str_pad($autoSendHour, 2, '0', STR_PAD_LEFT) }}:00) to dispatch the executive email.
                        </p>

                        <div class="border rounded p-3">
                            <span class="text-muted d-block font-size-11 text-uppercase fw-semibold mb-1">Company Entity</span>
                            <div class="fw-bold font-size-14 text-dark">{{ $company->name ?? 'Default Company' }}</div>
                            <small class="text-muted">{{ $company->address ?? 'Ghana' }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
    const dailyStatusUrl = "{{ route('daily-transaction-email.status') }}";
    const dailySendUrl = "{{ route('daily-transaction-email.send') }}";

    function updateDailyEmailStatusUi(payload) {
        const badge = document.getElementById('daily-email-status-badge');
        const schedulerBadge = document.getElementById('scheduler-health-badge');
        const meta = document.getElementById('daily-email-meta');
        const button = document.getElementById('send-daily-email-btn');

        if (!badge || !meta) {
            return;
        }

        if (!payload.success) {
            badge.className = 'badge status-badge-lg bg-danger text-white';
            badge.textContent = 'Service Unavailable';
            if (schedulerBadge) {
                schedulerBadge.className = 'badge status-badge-lg bg-danger text-white';
                schedulerBadge.textContent = 'Unavailable';
            }
            meta.textContent = payload.message || 'Status endpoint failed.';
            if (button) {
                button.disabled = true;
            }
            return;
        }

        // Live update KPI summary cards and breakdown table if fresh totals provided
        if (payload.totals) {
            const s = parseFloat(payload.totals.sales) || 0;
            const e = parseFloat(payload.totals.expenses) || 0;
            const sh = parseFloat(payload.totals.shortage) || 0;
            const net = s - e - sh;

            const formatCurrency = (val) => val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            $('#kpi-sales').text('Gh¢ ' + formatCurrency(s));
            $('#kpi-expenses').text('Gh¢ ' + formatCurrency(e));
            $('#kpi-shortage').text('Gh¢ ' + formatCurrency(sh));
            
            const netEl = $('#kpi-net');
            netEl.text('Gh¢ ' + formatCurrency(net));
            if (net >= 0) {
                netEl.removeClass('text-danger').addClass('text-success');
            } else {
                netEl.removeClass('text-success').addClass('text-danger');
            }

            // Update footer totals in table
            $('#total-table-sales').text(formatCurrency(s));
            $('#total-table-expenses').text(formatCurrency(e));
            $('#total-table-shortage').text(formatCurrency(sh));
            $('#total-table-net').text((net >= 0 ? '+' : '') + formatCurrency(net)).css('color', net >= 0 ? '#0F766E' : '#DC2626');

            // Update individual breakdown rows if provided
            if (payload.totals.breakdown && Array.isArray(payload.totals.breakdown)) {
                payload.totals.breakdown.forEach(function(row) {
                    const rowEl = $('#outlet-row-' + (row.outlet_id || 'unassigned'));
                    if (rowEl.length) {
                        rowEl.find('.row-sales').text(formatCurrency(row.sales));
                        rowEl.find('.row-expenses').text(formatCurrency(row.expenses)).css('color', row.expenses > 0 ? '#D97706' : '#64748B');
                        rowEl.find('.row-shortage').text(formatCurrency(row.shortage)).css('color', row.shortage > 0 ? '#DC2626' : '#64748B');
                        rowEl.find('.row-net').text((row.net >= 0 ? '+' : '') + formatCurrency(row.net)).css('color', row.net >= 0 ? '#0F766E' : '#DC2626');
                        
                        const statusEl = rowEl.find('.row-status');
                        if (row.shortage > 0) {
                            statusEl.html('<span class="badge bg-danger text-white">Shortage Alert</span>');
                        } else if (row.has_activity) {
                            statusEl.html('<span class="badge bg-success text-white">Active Today</span>');
                        } else {
                            statusEl.html('<span class="badge bg-light text-muted border">No Activity</span>');
                        }
                    }
                });
            }
        }

        if (!payload.feature_enabled) {
            badge.className = 'badge status-badge-lg bg-warning text-dark';
            badge.textContent = 'Feature Disabled';
            meta.innerHTML = 'Enable Daily Email in <a href="{{ route('settings.index') }}" class="text-teal text-decoration-underline fw-bold">Settings</a>.';
            if (button) {
                button.disabled = true;
            }
        } else {
            if (payload.already_sent_today) {
                const sentTime = payload.last_sent_at ? payload.last_sent_at : 'Earlier';
                badge.className = 'badge status-badge-lg bg-success text-white';
                badge.textContent = 'Dispatched Today (' + sentTime + ')';
            } else {
                badge.className = 'badge status-badge-lg bg-info text-white';
                badge.textContent = 'Pending Scheduled Dispatch';
            }
        }

        const schedulerHealth = payload.scheduler_health || {};
        if (schedulerBadge) {
            if (schedulerHealth.status === 'ok') {
                schedulerBadge.className = 'badge status-badge-lg bg-success text-white';
                schedulerBadge.textContent = 'Healthy';
                $('#schedulerStatusLabel').text('Running Normally').removeClass('text-danger text-warning').addClass('text-success');
            } else if (schedulerHealth.status === 'stale') {
                schedulerBadge.className = 'badge status-badge-lg bg-warning text-dark';
                schedulerBadge.textContent = 'Stale Heartbeat';
                $('#schedulerStatusLabel').text('Stale (' + (schedulerHealth.age_minutes || '?') + 'm ago)').removeClass('text-success text-danger').addClass('text-warning');
            } else if (schedulerHealth.status === 'missing') {
                schedulerBadge.className = 'badge status-badge-lg bg-secondary text-white';
                schedulerBadge.textContent = 'No Heartbeat Yet';
                $('#schedulerStatusLabel').text('Awaiting first run').removeClass('text-success text-danger').addClass('text-muted');
            } else {
                schedulerBadge.className = 'badge status-badge-lg bg-danger text-white';
                schedulerBadge.textContent = 'Inactive';
                $('#schedulerStatusLabel').text('Error').removeClass('text-success text-warning').addClass('text-danger');
            }

            if (schedulerHealth.last_run_at) {
                $('#schedulerLastRunText').text(schedulerHealth.last_run_at);
            }
        }

        const lastSentAt = payload.last_sent_at ? payload.last_sent_at : 'Not sent yet';
        const sendType = payload.last_send_type ? String(payload.last_send_type).toUpperCase() : 'N/A';
        const recipientEmail = payload.recipient_email ? payload.recipient_email : 'N/A';
        $('#recipientEmailText').text(recipientEmail);

        meta.innerHTML = '<strong>Business Date:</strong> ' + payload.business_date +
            ' &bull; <strong>Last Sent:</strong> ' + lastSentAt +
            ' (' + sendType + ')';

        if (button) {
            button.disabled = !payload.can_send;
        }
    }

    function refreshDailyTransactionEmailStatus() {
        $.get(dailyStatusUrl)
            .done(function(response) {
                updateDailyEmailStatusUi(response);
            })
            .fail(function(xhr) {
                updateDailyEmailStatusUi({
                    success: false,
                    message: xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Unable to fetch status.',
                });
            });
    }

    function sendDailyTransactionEmailNow() {
        if (!confirm('Are you sure you want to dispatch today\'s executive transaction report with the outlet breakdown to the company email?')) {
            return;
        }

        const button = document.getElementById('send-daily-email-btn');

        if (button) {
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Dispatching...';
        }

        $.ajax({
            url: dailySendUrl,
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
            },
        })
        .done(function(response) {
            if (window.Notiflix && Notiflix.Notify) {
                Notiflix.Notify.success(response.message || 'Daily email sent successfully.');
            } else {
                alert(response.message || 'Daily email sent successfully.');
            }
            refreshDailyTransactionEmailStatus();
        })
        .fail(function(xhr) {
            var errorMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to send daily email.';
            if (window.Notiflix && Notiflix.Notify) {
                Notiflix.Notify.failure(errorMsg);
            } else {
                alert(errorMsg);
            }

            if (button) {
                button.disabled = false;
            }
        })
        .always(function() {
            if (button) {
                button.innerHTML = '<i class="ri-mail-send-line me-1"></i> Send Report Now';
            }
        });
    }

    refreshDailyTransactionEmailStatus();
    setInterval(refreshDailyTransactionEmailStatus, 60000);
</script>
@endsection
