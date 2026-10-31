<!doctype html>
<html lang="en">

    <head>
        
        <meta charset="utf-8" />
        <!--<title>Dashboard | Admin & Dashboard </title>-->
        <title>DarpPOS Multi-outlet Sales and Inventory System | @yield('title')</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta content="Best Sales and Inventory System" name="description" />
        <meta content="Themesdesign" name="author" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="{{ asset('images/favicon.ico') }}">

        <!-- jquery.vectormap css -->
        <link href="{{ asset('backend/assets/libs/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.css') }}" rel="stylesheet" type="text/css" />

        <!--select 2-->
        <link href="{{ asset('backend/assets/libs/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
        
        <!-- Yajra DataTables CSS (local vendor baseline) -->
        <link href="{{ asset('vendor/datatables/css/buttons.dataTables-2.2.3.min.css') }}" rel="stylesheet">
        <link href="{{ asset('vendor/datatables/css/jquery.dataTables-1.13.6.min.css') }}" rel="stylesheet">

        {{-- notiflix --}}
        <link rel="stylesheet" href=  "{{ asset('notiflix/notiflix-3.2.8.min.css') }}"/>

        <!--fontawesome-->
        <link href="{{asset('fontawesome/css/all.css')}}" rel="stylesheet">


        <!-- Bootstrap Css -->
        <link href="{{ asset('backend/assets/css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
        <link href="{{ asset('backend/assets/css/bootstrap4.5.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />

        <!-- Icons Css -->
        <link href="{{ asset('backend/assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
        <!-- App Css-->
        <link href="{{ asset('backend/assets/css/app.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />

        <link href="{{ asset('uppy/dist/uppy.min.css') }}" rel="stylesheet">

        @yield('styles')
        @stack('styles')
        <style>
            :root {
                --pos-bg: #f8fafc;
                --pos-surface: #ffffff;
                --pos-text: #0f172a;
                --pos-muted: #64748b;
                --pos-border: #e2e8f0;
                --pos-primary: #059669;
                --pos-primary-hover: #047857;
                --pos-dark-btn: #0f172a;
                --pos-card-border: #e2e8f0;
                --pos-card-bg-start: #ffffff;
                --pos-card-bg-end: #f8fafc;
            }

            body {
                background-color: var(--pos-bg);
                color: var(--pos-text);
            }

            .main-content,
            .page-content {
                background-color: var(--pos-bg);
            }

            .main-content .card:not(.dashboard-metric-card):not(.payment-shell):not(.daily-email-card):not(.instruction-card) {
                border: 1px solid var(--pos-card-border) !important;
                border-radius: 12px !important;
                background: linear-gradient(135deg, var(--pos-card-bg-start), var(--pos-card-bg-end)) !important;
                box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05) !important;
                transform: none !important;
                transition: border-color 0.18s ease, box-shadow 0.18s ease !important;
            }

            .main-content .card:not(.dashboard-metric-card):not(.payment-shell):not(.daily-email-card):not(.instruction-card):hover {
                border-color: #cbd5e1 !important;
                background: linear-gradient(135deg, var(--pos-card-bg-start), var(--pos-card-bg-end)) !important;
                box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08) !important;
                transform: none !important;
            }

            .main-content .card:not(.dashboard-metric-card):not(.payment-shell):not(.daily-email-card):not(.instruction-card)::before {
                content: none !important;
            }

            .main-content .card:not(.dashboard-metric-card):not(.payment-shell):not(.daily-email-card):not(.instruction-card) > .card-header {
                border-bottom: 1px solid rgba(203, 213, 225, 0.85);
                background: rgba(255, 255, 255, 0.58);
            }

            .card-title,
            .page-title-box h4,
            .page-title-box .mb-sm-0 {
                color: var(--pos-text);
            }

            .filter-summary {
                background: #f8f9fa;
                padding: 12px 15px;
                border-radius: 4px;
                margin-bottom: 20px;
                border-left: 4px solid #007bff;
                display: flex;
                align-items: center;
                flex-wrap: wrap;
                gap: 8px;
            }

            .filter-summary i {
                color: #007bff;
                font-size: 16px;
            }

            .filter-summary .badge {
                padding: 5px 10px;
                font-size: 12px;
                font-weight: 500;
                border-radius: 3px;
            }

            .filter-summary .btn-link {
                padding: 0 5px;
                font-size: 12px;
                text-decoration: none;
                color: #dc3545;
            }

            .filter-summary .btn-link:hover {
                text-decoration: underline;
            }
            /* Uppy Dashboard Style */
            [data-uppy-drag-drop-supported=true] .uppy-Dashboard-AddFiles {
                border: 1px dashed #0f9cf3 !important;
                border-radius: 3px;
                height: calc(100% - 14px);
                margin: 7px;
            }

            .uppy-Dashboard-inner {
                background-color: #e2ebf1 !important;
                /* border: 1px solid #0f9cf3; */
                border-radius: 5px;
                max-height: 100%;
                max-width: 100%;
                outline: none;
                position: relative;
            }

            /* Yajra DataTables Global Styles */
            table.dataTable thead th {
                border-bottom: 2px solid #cbd5e1 !important;
                border-top: none !important;
                border-left: none !important;
                border-right: none !important;
                background-color: #f1f5f9 !important;
                color: #0f172a !important;
                font-weight: 700 !important;
                font-size: 13px !important;
                padding: 12px 10px !important;
                letter-spacing: 0.2px !important;
            }
             table.dataTable tbody tr {
                border-bottom: 1px solid #e2e8f0 !important;
                border-top: none !important;
                border-left: none !important;
                border-right: none !important;
            }

            table.dataTable tbody td {
                padding: 11px 10px !important;
                border: none !important;
                border-bottom: 1px solid #e2e8f0 !important;
                color: #1e293b !important;
                font-size: 13.5px !important;
            }
            
            table.dataTable tbody tr:nth-child(odd) {
                background-color: #ffffff !important;
            }
            
            table.dataTable tbody tr:nth-child(even) {
                background-color: #f8fafc !important;
            }
            
            table.dataTable tbody tr:hover {
                background-color: #f1f5f9 !important;
            }
            
            /* Remove all outer table borders */
            table.dataTable {
                border: none !important;
                border-collapse: collapse !important;
            }

            .form-control,
            .select2-container .select2-selection--single,
            .select2-container .select2-selection--multiple {
                border-color: var(--pos-border) !important;
                color: var(--pos-text) !important;
            }

            .form-control:focus,
            .select2-container--default.select2-container--focus .select2-selection--multiple,
            .select2-container--default.select2-container--open .select2-selection--single {
                border-color: #059669 !important;
                box-shadow: 0 0 0 0.2rem rgba(5, 150, 105, 0.15) !important;
            }

            .btn-dark,
            .btn-dark:focus,
            .btn-dark:active {
                background-color: #0f172a !important;
                border-color: #0f172a !important;
                color: #fff !important;
            }

            .btn-dark:hover {
                background-color: #1e293b !important;
                border-color: #1e293b !important;
            }

            /* Modern DataTables Action Buttons (View, Edit, Delete) */
            .action-buttons {
                display: inline-flex !important;
                align-items: center !important;
                flex-wrap: nowrap !important;
                gap: 6px !important;
            }

            .btn-action-view,
            .btn-action-edit,
            .btn-action-delete,
            .btn-action-amber,
            .table-edit-btn,
            .delete-confirm-btn {
                width: 32px !important;
                min-width: 32px !important;
                height: 32px !important;
                padding: 0 !important;
                border-radius: 8px !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                font-size: 13px !important;
                cursor: pointer !important;
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
                text-decoration: none !important;
                line-height: 1 !important;
                border: 1px solid transparent !important;
                box-sizing: border-box !important;
            }

            /* View: Modern Sky Tint */
            .btn-action-view {
                background: #f0f9ff !important;
                color: #0284c7 !important;
                border-color: #bae6fd !important;
                box-shadow: 0 1px 2px rgba(2, 132, 199, 0.08) !important;
            }

            .btn-action-view:hover {
                background: #0284c7 !important;
                color: #ffffff !important;
                border-color: #0284c7 !important;
                transform: translateY(-2px) !important;
                box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3) !important;
            }

            .btn-action-view i,
            .btn-action-view svg {
                color: #0284c7 !important;
                font-size: 13px !important;
                transition: color 0.15s ease !important;
            }

            .btn-action-view:hover i,
            .btn-action-view:hover svg {
                color: #ffffff !important;
            }

            /* Edit: Modern Signature Emerald Tint */
            .btn-action-edit,
            .table-edit-btn {
                background: #ecfdf5 !important;
                color: #059669 !important;
                border-color: #a7f3d0 !important;
                box-shadow: 0 1px 2px rgba(5, 150, 105, 0.08) !important;
            }

            .btn-action-edit:hover,
            .table-edit-btn:hover {
                background: #059669 !important;
                color: #ffffff !important;
                border-color: #059669 !important;
                transform: translateY(-2px) !important;
                box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3) !important;
            }

            .btn-action-edit i,
            .table-edit-btn i,
            .btn-action-edit svg,
            .table-edit-btn svg {
                color: #059669 !important;
                font-size: 13px !important;
                transition: color 0.15s ease !important;
            }

            .btn-action-edit:hover i,
            .table-edit-btn:hover i,
            .btn-action-edit:hover svg,
            .table-edit-btn:hover svg {
                color: #ffffff !important;
            }

            /* Delete: Modern Rose Tint */
            .btn-action-delete,
            .delete-confirm-btn {
                background: #fff1f2 !important;
                color: #e11d48 !important;
                border-color: #fecdd3 !important;
                box-shadow: 0 1px 2px rgba(225, 29, 72, 0.08) !important;
            }

            .btn-action-delete:hover,
            .delete-confirm-btn:hover {
                background: #e11d48 !important;
                color: #ffffff !important;
                border-color: #e11d48 !important;
                transform: translateY(-2px) !important;
                box-shadow: 0 4px 12px rgba(225, 29, 72, 0.3) !important;
            }

            .btn-action-delete i,
            .delete-confirm-btn i,
            .btn-action-delete svg,
            .delete-confirm-btn svg {
                color: #e11d48 !important;
                font-size: 13px !important;
                transition: color 0.15s ease !important;
            }

            .btn-action-delete:hover i,
            .delete-confirm-btn:hover i,
            .btn-action-delete:hover svg,
            .delete-confirm-btn:hover svg {
                color: #ffffff !important;
            }

            /* Amber / Warning Action Button (e.g. Reopen, Restore) */
            .btn-action-amber {
                background: #fffbeb !important;
                color: #d97706 !important;
                border-color: #fde68a !important;
                box-shadow: 0 1px 2px rgba(217, 119, 6, 0.08) !important;
            }

            .btn-action-amber:hover {
                background: #d97706 !important;
                color: #ffffff !important;
                border-color: #d97706 !important;
                transform: translateY(-2px) !important;
                box-shadow: 0 4px 12px rgba(217, 119, 6, 0.3) !important;
            }

            .btn-action-amber i,
            .btn-action-amber svg {
                color: #d97706 !important;
                font-size: 13px !important;
                transition: color 0.15s ease !important;
            }

            .btn-action-amber:hover i,
            .btn-action-amber:hover svg {
                color: #ffffff !important;
            }

            /* Modern UI Page Buttons */
            .btn-modern-primary {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                background: linear-gradient(135deg, #059669 0%, #10b981 100%) !important;
                color: #ffffff !important;
                border: none !important;
                font-weight: 600 !important;
                font-size: 14px !important;
                border-radius: 10px !important;
                padding: 9px 24px !important;
                letter-spacing: 0.2px !important;
                box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35) !important;
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
                text-decoration: none !important;
            }

            .btn-modern-primary:hover {
                background: linear-gradient(135deg, #047857 0%, #059669 100%) !important;
                color: #ffffff !important;
                transform: translateY(-2px) !important;
                box-shadow: 0 6px 20px rgba(16, 185, 129, 0.45) !important;
            }

            .btn-modern-cancel {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                background: #ffffff !important;
                color: #475569 !important;
                border: 1.5px solid #cbd5e1 !important;
                font-weight: 600 !important;
                font-size: 14px !important;
                border-radius: 10px !important;
                padding: 9px 24px !important;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
                text-decoration: none !important;
            }

            .btn-modern-cancel:hover {
                background: #f8fafc !important;
                color: #0f172a !important;
                border-color: #94a3b8 !important;
                transform: translateY(-1px) !important;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06) !important;
            }

            .btn-modern-header {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                background: #ffffff !important;
                color: #059669 !important;
                border: 1.5px solid #cbd5e1 !important;
                font-weight: 600 !important;
                font-size: 13.5px !important;
                border-radius: 8px !important;
                padding: 8px 18px !important;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
                text-decoration: none !important;
            }

            .btn-modern-header:hover {
                background: #ecfdf5 !important;
                color: #047857 !important;
                border-color: #059669 !important;
                transform: translateY(-1px) !important;
                box-shadow: 0 4px 12px rgba(5, 150, 105, 0.15) !important;
            }

            /* Global Primary Button & Badge Override for Fintech Polish */
            .btn-primary:not(.btn-modern-cancel):not(.btn-modern-header):not(.btn-action-view) {
                background-color: #059669 !important;
                border-color: #059669 !important;
                color: #ffffff !important;
            }

            .btn-primary:not(.btn-modern-cancel):not(.btn-modern-header):not(.btn-action-view):hover,
            .btn-primary:not(.btn-modern-cancel):not(.btn-modern-header):not(.btn-action-view):focus {
                background-color: #047857 !important;
                border-color: #047857 !important;
                color: #ffffff !important;
            }

            .badge.bg-primary,
            .badge-primary {
                background-color: #059669 !important;
                color: #ffffff !important;
            }

            footer.footer {
                background: #ffffff !important;
                border-top: 1px solid #e2e8f0 !important;
                color: #64748b !important;
            }

            footer.footer a {
                color: #059669 !important;
                font-weight: 600 !important;
            }

            .header-quick-actions {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                gap: 10px;
                z-index: 2;
                flex-wrap: nowrap;
            }

            .header-quick-actions .quick-action-btn {
                width: 44px;
                height: 44px;
                border-radius: 50%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 22px;
                color: #ffffff;
                background: rgba(255, 255, 255, 0.14);
                border: 1px solid rgba(255, 255, 255, 0.28);
                transition: all 0.2s ease;
                padding: 0;
            }

            .header-quick-actions .quick-action-btn:hover {
                color: #ffffff;
                background: rgba(255, 255, 255, 0.26);
                transform: translateY(-1px);
            }

            @media (max-width: 1199.98px) {
                .header-quick-actions {
                    left: 54%;
                    gap: 6px;
                }

                .header-quick-actions .quick-action-btn {
                    width: 36px;
                    height: 36px;
                    font-size: 18px;
                }
            }

            @media (max-width: 767.98px) {
                .header-quick-actions {
                    left: auto;
                    right: 58px;
                    transform: translateY(-50%);
                    gap: 4px;
                    max-width: 58vw;
                    overflow-x: auto;
                    scrollbar-width: none;
                }

                .header-quick-actions::-webkit-scrollbar {
                    display: none;
                }

                .header-quick-actions .quick-action-btn {
                    width: 32px;
                    height: 32px;
                    font-size: 16px;
                    flex: 0 0 auto;
                }
            }

            @media (max-width: 991.98px) {
                .main-content {
                    margin-top: 96px !important;
                }

                .page-content {
                    padding-top: 16px;
                }
            }

            @media (max-width: 575.98px) {
                .main-content {
                    margin-top: 160px !important;
                }

                .page-content {
                    padding-top: 18px;
                }
            }
        </style>
        
    </head>

    {{-- <body data-topbar="dark" oncontextmenu="return false;"> --}}
    <body data-topbar="dark">

    <!-- <body data-layout="horizontal" data-topbar="dark"> -->

        <!-- Begin page -->
        <div id="layout-wrapper">

            
          @include('layout.header')

            <!-- ========== Left Sidebar Start ========== -->
           @include('layout.sidebar')
            <!-- Left Sidebar End -->

            
            

            <!-- ============================================================== -->
            <!-- Start right Content here -->
            <!-- ============================================================== -->
            <div class="main-content">

                
                @yield('content')
                    <!-- End Page-content -->
                

                @include('layout.footer')
                
                
            </div>
            <!-- end main content-->

        </div>
        <!-- END layout-wrapper -->

        @include('layout.business-chat-widget')

        <!-- Right Sidebar -->
        
        <!-- /Right-bar -->

        <!-- Right bar overlay-->
        <div class="rightbar-overlay"></div>
        
    @include('layout.scripts')

<script>
    (function () {
        function shouldStyleAsBlack($el) {
            // Keep DataTables buttons/actions unchanged.
            if ($el.closest('.dataTables_wrapper').length || $el.closest('.dt-buttons').length || $el.hasClass('dt-button')) {
                return false;
            }

            var text = '';
            if ($el.is('input[type="submit"], input[type="button"]')) {
                text = ($el.val() || '').trim().toLowerCase();
            } else {
                text = ($el.text() || '').trim().toLowerCase();
            }

            if (!text) {
                return false;
            }

            return /\b(add|submit|save|view)\b/.test(text);
        }

        function applyBlackButtons() {
            $('a.btn, button.btn, input.btn[type="submit"], input.btn[type="button"]').each(function () {
                var $el = $(this);

                if (!shouldStyleAsBlack($el)) {
                    return;
                }

                $el.removeClass('btn-primary btn-success btn-info btn-secondary btn-warning btn-danger btn-light btn-outline-primary btn-outline-success btn-outline-info btn-outline-secondary btn-outline-warning btn-outline-danger');
                $el.addClass('btn-dark');
                $el.css({
                    'background-color': '#000',
                    'border-color': '#000',
                    'color': '#fff'
                });
            });
        }

        $(document).ready(function () {
            applyBlackButtons();
        });

        $(document).ajaxComplete(function () {
            applyBlackButtons();
        });
    })();
</script>

<script>
    (function () {
        // Delegate click handling so delete works for dynamically rendered DataTables rows.
        $(document).on('click', '.btn-delete, .delete-confirm-btn', function (e) {
            e.preventDefault();

            var $button = $(this);
            var form = $button.closest('form')[0];

            if (!form) {
                return;
            }

            if (typeof Notiflix !== 'undefined' && Notiflix.Confirm && typeof Notiflix.Confirm.show === 'function') {
                Notiflix.Confirm.show(
                    'Confirm Delete',
                    'Are you sure you want to delete this record?',
                    'Yes, Delete',
                    'Cancel',
                    function () {
                        form.submit();
                    }
                );
                return;
            }

            if (window.confirm('Are you sure you want to delete this record?')) {
                form.submit();
            }
        });
    })();
</script>

@yield('scripts')
@stack('scripts')
</body>

</html>