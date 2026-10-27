@extends('layout.main')
@section('title') {{ 'Daily Transaction Report' }} @endsection

@push('styles')
<style>
    .daily-email-card {
        background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
        border: none;
        box-shadow: 0 8px 24px rgba(15, 118, 110, 0.18);
    }

    .daily-email-card .card-header {
        background: linear-gradient(135deg, #0b5f59 0%, #0f766e 100%);
        border-bottom: none;
    }

    .daily-email-card .card-header h5,
    .daily-email-card .card-body,
    .daily-email-card .card-body p,
    .daily-email-card .card-body small,
    .daily-email-card .card-body strong,
    .daily-email-card #daily-email-meta {
        color: #111 !important;
    }

    .daily-email-card .card-header *,
    .daily-email-card .card-body * {
        color: #111 !important;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.12);
    }

    .daily-email-card .status-label {
        background: rgba(255, 255, 255, 0.18);
        color: #fff !important;
        border: 1px solid rgba(0, 0, 0, 0.12);
    }

    .daily-email-card .badge {
        color: #fff !important;
    }

    .daily-email-card .card-body a {
        color: #0b5f59 !important;
        text-decoration: underline;
    }

    .daily-email-card .btn {
        background-color: #0b5f59 !important;
        color: #fff !important;
        border: 1px solid rgba(255, 255, 255, 0.18) !important;
        font-weight: 600;
        display: inline-block !important;
    }

    .daily-email-card .btn:hover,
    .daily-email-card .btn:focus {
        background-color: #09514b !important;
        color: #fff !important;
        border-color: rgba(255, 255, 255, 0.22) !important;
    }

    .daily-email-card .btn:disabled,
    .daily-email-card .btn.disabled {
        opacity: 0.75;
        color: #fff !important;
    }

    .page-title-box h4 {
        color: #111827;
    }

    .daily-email-card .card-header h5 {
        color: #fff !important;
    }

    .daily-email-card .card-header i {
        color: #fff !important;
    }

    @media (max-width: 767.98px) {
        .page-content {
            padding-left: 8px !important;
            padding-right: 8px !important;
            padding-top: 12px !important;
        }

        .page-content .container-fluid {
            padding-left: 4px !important;
            padding-right: 4px !important;
        }

        .page-title-box {
            color: #0f766e;
            margin-bottom: 0 !important;
        }

            color: #111 !important;
            margin-bottom: 4px !important;
        }

            color: #111 !important;
            margin-bottom: 12px;
        }

        .daily-email-card .card-header,
        .daily-email-card .card-body {
            padding-left: 12px;
            padding-right: 12px;
        }
    }
</style>
@endpush

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Daily Transaction Report</h4>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12">
                <div class="card daily-email-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="ri-mail-send-line me-2"></i>Daily Transaction Email
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><small><strong>Status:</strong></small></p>
                        <div id="emailStatus" class="mb-3">
                            <span id="daily-email-status-badge" class="badge status-label">Checking...</span>
                        </div>

                        <p class="mb-2"><small><strong>Scheduler Health:</strong></small></p>
                        <div id="schedulerHealthStatus" class="mb-3">
                            <span id="scheduler-health-badge" class="badge status-label">Checking...</span>
                        </div>

                        <p class="mb-2"><small><strong>Quick Actions:</strong></small></p>
                        @can('daily-transaction-email-send')
                            <button
                                id="send-daily-email-btn"
                                type="button"
                                class="btn btn-sm"
                                onclick="sendDailyTransactionEmailNow()"
                            >
                                <i class="ri-mail-send-line me-1"></i>Send Email Now
                            </button>
                        @else
                            <small class="text-muted">You do not have permission to send manual reports.</small>
                        @endcan

                        <small id="daily-email-meta" class="d-block mt-3">Preparing status...</small>
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
            badge.className = 'badge badge-danger';
            badge.textContent = 'Unavailable';
            if (schedulerBadge) {
                schedulerBadge.className = 'badge badge-danger';
                schedulerBadge.textContent = 'Unavailable';
            }
            meta.textContent = payload.message || 'Status endpoint failed.';
            if (button) {
                button.disabled = true;
            }
            return;
        }

        if (!payload.feature_enabled) {
            badge.className = 'badge badge-warning';
            badge.textContent = 'Disabled';
            meta.innerHTML = 'Enable Daily Transaction Email in <a href="{{ route('settings.index') }}">Settings</a> to use this feature.';
            if (button) {
                button.disabled = true;
            }
        }

        if (payload.feature_enabled) {
            if (payload.already_sent_today) {
                const sentTime = payload.last_sent_at ? payload.last_sent_at : 'N/A';
                badge.className = 'badge bg-success text-white';
                badge.textContent = 'Sent Today - ' + sentTime;
            } else {
                badge.className = 'badge badge-info';
                badge.textContent = 'Pending';
            }
        }

        const schedulerHealth = payload.scheduler_health || {};
        if (schedulerBadge) {
            if (schedulerHealth.status === 'ok') {
                schedulerBadge.className = 'badge bg-success text-white';
                schedulerBadge.textContent = 'Healthy';
            } else if (schedulerHealth.status === 'stale') {
                schedulerBadge.className = 'badge badge-warning';
                schedulerBadge.textContent = 'Stale';
            } else if (schedulerHealth.status === 'missing') {
                schedulerBadge.className = 'badge badge-warning';
                schedulerBadge.textContent = 'No Heartbeat Yet';
            } else {
                schedulerBadge.className = 'badge badge-danger';
                schedulerBadge.textContent = 'Invalid';
            }
        }

        const lastSentAt = payload.last_sent_at ? payload.last_sent_at : 'Not sent yet';
        const autoState = payload.auto_send_enabled ? 'ON' : 'OFF';
        const sendType = payload.last_send_type ? String(payload.last_send_type).toUpperCase() : 'N/A';
        const recipientEmail = payload.recipient_email ? payload.recipient_email : 'N/A';
        const schedulerLastRun = schedulerHealth.last_run_at ? schedulerHealth.last_run_at : 'Never';
        const schedulerAge = schedulerHealth.age_minutes !== null && schedulerHealth.age_minutes !== undefined
            ? schedulerHealth.age_minutes + 'm'
            : 'N/A';
        meta.textContent = 'Business Date: ' + payload.business_date + ' | Last Sent: ' + lastSentAt + ' | Type: ' + sendType + ' | Recipient: ' + recipientEmail + ' | Auto Send: ' + autoState + ' | Auto Time: ' + payload.configured_auto_send_time_label + ' | Scheduler Last Run: ' + schedulerLastRun + ' | Scheduler Age: ' + schedulerAge;

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
        const button = document.getElementById('send-daily-email-btn');

        if (button) {
            button.disabled = true;
            button.textContent = 'Sending...';
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
            }
            refreshDailyTransactionEmailStatus();
        })
        .fail(function(xhr) {
            if (window.Notiflix && Notiflix.Notify) {
                Notiflix.Notify.failure((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to send daily email.');
            }

            if (button) {
                button.disabled = false;
            }
        })
        .always(function() {
            if (button) {
                button.textContent = 'Send Email Now';
            }
        });
    }

    refreshDailyTransactionEmailStatus();
    setInterval(refreshDailyTransactionEmailStatus, 60000);
</script>
@endsection
