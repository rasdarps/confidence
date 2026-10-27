<?php

return [
    // When enabled, a cashier with sales yesterday must reconcile yesterday
    // (submitted or approved) before creating sales today.
    'enforce_previous_day_reconciliation' => env('POS_ENFORCE_PREVIOUS_DAY_RECONCILIATION', false),

    // When enabled, sales can reduce stock below zero.
    'allow_negative_stock' => env('POS_ALLOW_NEGATIVE_STOCK', false),

    // When enabled, users cannot create invoices dated before today.
    'prevent_backdated_invoices' => env('POS_PREVENT_BACKDATED_INVOICES', false),

    // When enabled, staff can enter manual discount amounts on sales.
    'allow_manual_discount_entry_by_staff' => env('POS_ALLOW_MANUAL_DISCOUNT_ENTRY_BY_STAFF', true),

    // When enabled, users marked as requiring password change must update
    // their password before accessing the rest of the system.
    'require_password_change_on_first_login' => env('POS_REQUIRE_PASSWORD_CHANGE_ON_FIRST_LOGIN', false),

    // When enabled, daily transaction report email is sent automatically at configured time.
    'enable_daily_email_report' => env('POS_ENABLE_DAILY_EMAIL_REPORT', false),

    // Time to send daily email report (24-hour format, 0-23). Range: 18-23 (6pm to 12am)
    'daily_email_report_time' => env('POS_DAILY_EMAIL_REPORT_TIME', 18),

    // When enabled, posted purchases can be edited by permitted users within configured window.
    'purchase_edit_enabled' => env('POS_PURCHASE_EDIT_ENABLED', false),

    // Purchase edit window in hours. Range: 24 to 720 (30 days).
    'purchase_edit_window_hours' => env('POS_PURCHASE_EDIT_WINDOW_HOURS', 24),
];
