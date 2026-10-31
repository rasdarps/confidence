<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Barryvdh\DomPDF\Facade\Pdf;

$html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>DarpPOS Multi-outlet Management System - Complete User Manual</title>
<style>
    @page {
        margin: 18mm 14mm 18mm 14mm;
    }
    body {
        font-family: DejaVu Sans, "Helvetica Neue", Helvetica, Arial, sans-serif;
        font-size: 9.5px;
        line-height: 1.45;
        color: #1e293b;
        margin: 0;
        padding: 0;
    }

    /* Cover Page */
    .cover-page {
        text-align: center;
        page-break-after: always;
        padding-top: 35px;
    }
    .cover-badge {
        display: inline-block;
        background: #0F766E;
        color: #ffffff;
        font-size: 10.5px;
        font-weight: bold;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        padding: 5px 16px;
        border-radius: 20px;
        margin-bottom: 20px;
    }
    .cover-title {
        font-size: 24px;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 8px 0;
        line-height: 1.25;
        text-transform: uppercase;
    }
    .cover-subtitle {
        font-size: 12px;
        font-weight: 600;
        color: #0d9488;
        margin: 0 0 25px 0;
        letter-spacing: 0.5px;
    }
    .cover-divider {
        width: 100px;
        height: 4px;
        background: #0F766E;
        margin: 0 auto 25px auto;
        border-radius: 2px;
    }
    .cover-card {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 16px;
        text-align: left;
        margin: 0 auto 30px auto;
        width: 88%;
    }
    .cover-card table {
        width: 100%;
        border-collapse: collapse;
    }
    .cover-card td {
        padding: 5px 8px;
        font-size: 9.5px;
        border-bottom: 1px solid #e2e8f0;
    }
    .cover-card td.lbl {
        font-weight: bold;
        color: #475569;
        width: 34%;
    }
    .cover-card td.val {
        color: #0f172a;
        font-weight: 600;
    }
    .cover-footer {
        margin-top: 40px;
        font-size: 8.5px;
        color: #64748b;
    }

    /* Headings & Structure */
    .chapter-break {
        page-break-before: always;
    }
    h1 {
        font-size: 14.5px;
        font-weight: bold;
        color: #0F766E;
        border-bottom: 2px solid #0F766E;
        padding-bottom: 3px;
        margin-top: 18px;
        margin-bottom: 10px;
        text-transform: uppercase;
        page-break-after: avoid;
    }
    h2 {
        font-size: 11.5px;
        font-weight: bold;
        color: #0f172a;
        margin-top: 14px;
        margin-bottom: 6px;
        border-left: 3px solid #0d9488;
        padding-left: 6px;
        page-break-after: avoid;
    }
    h3 {
        font-size: 10px;
        font-weight: bold;
        color: #334155;
        margin-top: 10px;
        margin-bottom: 4px;
        page-break-after: avoid;
    }
    p {
        margin: 0 0 6px 0;
        text-align: justify;
    }
    ul, ol {
        margin: 0 0 8px 0;
        padding-left: 18px;
    }
    li {
        margin-bottom: 3px;
    }

    /* Callout & Alert Boxes */
    .callout {
        border-radius: 5px;
        padding: 8px 12px;
        margin: 8px 0;
        font-size: 9px;
        page-break-inside: avoid;
    }
    .callout-info {
        background: #f0fdfa;
        border-left: 4px solid #0F766E;
        color: #134e4a;
    }
    .callout-warning {
        background: #fffbeb;
        border-left: 4px solid #d97706;
        color: #78350f;
    }
    .callout-tip {
        background: #eff6ff;
        border-left: 4px solid #2563eb;
        color: #1e3a8a;
    }
    .callout-danger {
        background: #fef2f2;
        border-left: 4px solid #dc2626;
        color: #991b1b;
    }
    .callout-title {
        font-weight: bold;
        display: block;
        margin-bottom: 2px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Tables */
    table.data-table {
        width: 100%;
        border-collapse: collapse;
        margin: 8px 0 12px 0;
        font-size: 9px;
        page-break-inside: avoid;
    }
    table.data-table th, table.data-table td {
        border: 1px solid #cbd5e1;
        padding: 4px 6px;
        text-align: left;
        vertical-align: top;
    }
    table.data-table th {
        background: #0F766E;
        color: #ffffff;
        font-weight: bold;
        text-transform: uppercase;
        font-size: 8.5px;
        letter-spacing: 0.5px;
    }
    table.data-table tr:nth-child(even) {
        background: #f8fafc;
    }

    /* Step Boxes */
    .step-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-left: 3px solid #0d9488;
        border-radius: 4px;
        padding: 6px 10px;
        margin: 5px 0;
        page-break-inside: avoid;
    }
    .step-number {
        font-weight: bold;
        color: #0F766E;
        margin-right: 4px;
    }

    /* Badges */
    .badge {
        display: inline-block;
        padding: 1px 5px;
        border-radius: 3px;
        font-size: 8px;
        font-weight: bold;
        text-transform: uppercase;
    }
    .badge-primary { background: #0F766E; color: #fff; }
    .badge-info { background: #0284c7; color: #fff; }
    .badge-success { background: #16a34a; color: #fff; }
    .badge-danger { background: #dc2626; color: #fff; }
    .badge-warning { background: #d97706; color: #fff; }
    .badge-secondary { background: #64748b; color: #fff; }

    code {
        font-family: monospace;
        background: #e2e8f0;
        padding: 1px 4px;
        border-radius: 3px;
        font-size: 8.5px;
        color: #0f172a;
    }
</style>
</head>
<body>

<!-- ================= COVER PAGE ================= -->
<div class="cover-page">
    <div style="height: 25px;"></div>
    <div class="cover-badge">ENTERPRISE CLIENT USER MANUAL</div>
    <h1 class="cover-title">DarpPOS<br>MULTI-OUTLET MANAGEMENT SYSTEM</h1>
    <div class="cover-subtitle">Complete Operational &amp; Administrative Manual for Headquarters, Warehouses, Cashiers &amp; Executives</div>
    <div class="cover-divider"></div>

    <div class="cover-card">
        <table>
            <tr>
                <td class="lbl">System Edition:</td>
                <td class="val">DarpPOS Multi-Outlet Enterprise Platform v2.5</td>
            </tr>
            <tr>
                <td class="lbl">Architecture:</td>
                <td class="val">Central HQ Hub-and-Spoke Distribution with Retail Point of Sale</td>
            </tr>
            <tr>
                <td class="lbl">Packaging Mode:</td>
                <td class="val">Dual Wholesale (Bags/Boxes) &amp; Retail (Pieces) Inventory Hierarchy</td>
            </tr>
            <tr>
                <td class="lbl">Administration:</td>
                <td class="val">Role-Based Access Control (RBAC), Multi-User &amp; Scoped Branch Isolation</td>
            </tr>
            <tr>
                <td class="lbl">Executive Reporting:</td>
                <td class="val">Automated Daily EOD Transaction Emails, Audit Logs &amp; AI Business Chat</td>
            </tr>
            <tr>
                <td class="lbl">Financial Engine:</td>
                <td class="val">Double-Entry Accounting, Real-Time Cash Reconciliation &amp; Financial Statements</td>
            </tr>
            <tr>
                <td class="lbl">Target Audience:</td>
                <td class="val">Company Executives, IT Administrators, Warehouse Supervisors, Cashiers &amp; Accountants</td>
            </tr>
            <tr>
                <td class="lbl">Published Date:</td>
                <td class="val">November 2026 &bull; Official Client Release</td>
            </tr>
        </table>
    </div>

    <div class="cover-footer">
        <strong>Confidential &amp; Proprietary</strong> — Prepared exclusively for client operations, management oversight, and staff training.<br>
        &copy; 2026 DarpPOS Multi-outlet Management System. All rights reserved.
    </div>
</div>

<!-- ================= TABLE OF CONTENTS ================= -->
<div class="toc-container">
    <h1>Table of Contents</h1>
    <table class="data-table" style="font-size: 9px;">
        <thead>
            <tr>
                <th style="width: 14%;">Chapter</th>
                <th style="width: 50%;">Module / Operational Domain</th>
                <th style="width: 36%;">Core Functional Coverage</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Chapter 1</strong></td>
                <td><strong>System Overview &amp; Architecture</strong></td>
                <td>Hub-and-spoke multi-outlet topology, dual packaging, security scoping.</td>
            </tr>
            <tr>
                <td><strong>Chapter 2</strong></td>
                <td><strong>Enterprise User &amp; Role Administration</strong></td>
                <td>User creation, editing, outlet assignment, roles &amp; granular permissions matrix.</td>
            </tr>
            <tr>
                <td><strong>Chapter 3</strong></td>
                <td><strong>Company Setup, Outlets &amp; Global Policies</strong></td>
                <td>Company profile, legal TIN, branch stations, session policies, fraud restrictions.</td>
            </tr>
            <tr>
                <td><strong>Chapter 4</strong></td>
                <td><strong>Automated Daily Transaction Emails &amp; Reports</strong></td>
                <td>EOD email dispatch, executive summary, manual trigger, audit logs &amp; SMTP status.</td>
            </tr>
            <tr>
                <td><strong>Chapter 5</strong></td>
                <td><strong>Product Catalog &amp; Dual-Packaging Setup</strong></td>
                <td>Units &amp; sub-units, pieces-per-pack ratio, dual pricing, barcode printing.</td>
            </tr>
            <tr>
                <td><strong>Chapter 6</strong></td>
                <td><strong>Inbound Procurement &amp; Supplier Management</strong></td>
                <td>Supplier bills, bulk receiving in bags/boxes, landed cost &amp; edit trail.</td>
            </tr>
            <tr>
                <td><strong>Chapter 7</strong></td>
                <td><strong>Inter-Branch Stock Transfers (Logistics)</strong></td>
                <td>Two-phase handshake: Dispatch manifest, in-transit status &amp; branch receipt audit.</td>
            </tr>
            <tr>
                <td><strong>Chapter 8</strong></td>
                <td><strong>Point of Sale (POS) &amp; Retail Cashiering</strong></td>
                <td>Barcode scanning, dual-unit cart selling, multi-payment, receipt reprinting.</td>
            </tr>
            <tr>
                <td><strong>Chapter 9</strong></td>
                <td><strong>Customer &amp; Supplier Credit (AR / AP)</strong></td>
                <td>Aging buckets, debt repayments, customer ledgers, vendor payables.</td>
            </tr>
            <tr>
                <td><strong>Chapter 10</strong></td>
                <td><strong>Expense Management &amp; Voucher Control</strong></td>
                <td>Expense categories, voucher creation, receipt printing &amp; ledger postings.</td>
            </tr>
            <tr>
                <td><strong>Chapter 11</strong></td>
                <td><strong>Shift End &amp; Cash Drawer Reconciliation</strong></td>
                <td>Opening float, denomination counting, expected vs actual variance &amp; sign-off.</td>
            </tr>
            <tr>
                <td><strong>Chapter 12</strong></td>
                <td><strong>Stock Adjustments &amp; Inventory Audits</strong></td>
                <td>Periodic physical counts, damage/shrinkage reasons, automatic ledger adjustment.</td>
            </tr>
            <tr>
                <td><strong>Chapter 13</strong></td>
                <td><strong>Double-Entry Accounting &amp; Banking</strong></td>
                <td>Chart of Accounts, fiscal periods, bank transactions, balance sheet &amp; P&amp;L.</td>
            </tr>
            <tr>
                <td><strong>Chapter 14</strong></td>
                <td><strong>Business Intelligence &amp; Movement Reports</strong></td>
                <td>Product trails, stock valuation, cashier audit, sales lines &amp; PDF/Excel exports.</td>
            </tr>
            <tr>
                <td><strong>Chapter 15</strong></td>
                <td><strong>AI Business Chat Assistant (`/admin/chat`)</strong></td>
                <td>Natural language queries for sales, collections, bags/boxes &amp; pieces stock balances.</td>
            </tr>
            <tr>
                <td><strong>Chapter 16</strong></td>
                <td><strong>Daily Checklists, Troubleshooting &amp; Support</strong></td>
                <td>Morning/evening checklists, scanner/printer fixes, email delivery &amp; helpdesk contacts.</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- ================= CHAPTER 1 ================= -->
<div class="chapter-break">
    <h1>Chapter 1: System Overview &amp; Architecture</h1>

    <h2>1.1 Enterprise Hub-and-Spoke Topology</h2>
    <p>
        The <strong>DarpPOS Multi-outlet Management System</strong> provides centralized command and decentralized operational capability for wholesale distributors, retail chain stores, and multi-depot enterprises.
    </p>
    <ul>
        <li><strong>Central Warehouse (HQ Hub):</strong> Acts as the primary logistics and procurement depot. Receives direct manufacturer shipments, controls supplier liabilities, and dispatches wholesale stock to branch locations.</li>
        <li><strong>Retail Branches (Spokes / POS Outlets):</strong> Independent retail locations holding localized stock. Cashiers operate dedicated Point of Sale terminals, collect walk-in revenue, and reconcile daily cash drawers.</li>
        <li><strong>Strict Data Isolation:</strong> Cashiers and branch managers only access local inventory, POS sales, and shift records. Central executives maintain unified multi-outlet visibility.</li>
    </ul>

    <h2>1.2 Dual-Packaging Unit Architecture</h2>
    <p>
        DarpPOS solves unit conversion mismatches by supporting unified wholesale and retail packaging:
    </p>
    <div class="callout callout-info">
        <span class="callout-title">Packaging Hierarchy:</span>
        <strong>1 Wholesale Outer Pack (Bag / Box / Carton) = N Retail Loose Pieces (Pieces / Bottles / Sachets)</strong><br>
        Inventory is tracked in base loose units in the database, while procurement, transfers, audits, and sales dynamically handle whole packs, loose pieces, or any mixed combination.
    </div>

    <h2>1.3 Hardware &amp; Environmental Requirements</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25%;">Hardware Component</th>
                <th style="width: 35%;">Specification / Type</th>
                <th style="width: 40%;">Operational Function</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>POS Receipt Printer</strong></td>
                <td>80mm or 58mm Thermal ESC/POS Printer (USB / Ethernet)</td>
                <td>Instant thermal printing for sales receipts, transfer manifests, and shift summaries.</td>
            </tr>
            <tr>
                <td><strong>Barcode Scanner</strong></td>
                <td>1D / 2D Handheld Laser or Omnidirectional Scanner (USB)</td>
                <td>Rapid barcode lookups during cashier checkout, goods receiving, and audits.</td>
            </tr>
            <tr>
                <td><strong>Cash Drawer</strong></td>
                <td>Standard RJ11 / RJ12 24V Drawer Kick connected to printer</td>
                <td>Automatically pops open upon completing a cash sale or shift audit.</td>
            </tr>
            <tr>
                <td><strong>Workstation / Browser</strong></td>
                <td>Modern Browser (Chrome, Firefox, Edge) on Windows/macOS/Linux</td>
                <td>Responsive web interface accessible over local LAN or secure cloud network.</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- ================= CHAPTER 2 ================= -->
<div class="chapter-break">
    <h1>Chapter 2: Enterprise User &amp; Role Administration</h1>

    <h2>2.1 User Management Lifecycle</h2>
    <p>
        Staff access is controlled centrally at Headquarters. To manage users, navigate to <strong>User Setup &gt; Manage Users &gt; Users</strong>.
    </p>

    <div class="step-box">
        <span class="step-number">Step 1: Creating a New User:</span> Click the <strong>+ Create</strong> button on the Users index screen.
        <ul>
            <li><strong>Full Name (`name`):</strong> Employee's full legal name.</li>
            <li><strong>Username (`username`):</strong> Unique alphanumeric username used for system sign-in.</li>
            <li><strong>Corporate Email (`email`):</strong> Unique corporate email address for notifications and password recovery.</li>
            <li><strong>Phone Number (`phone`):</strong> 10-digit mobile number for communication and identity verification.</li>
            <li><strong>Password &amp; Confirmation:</strong> Strong password (min 6 characters, mixed case, numbers, and symbols).</li>
            <li><strong>User Type (`user_type`):</strong> Select <code>Admin</code> (for back-office managers) or <code>User</code> (for frontline cashiers).</li>
            <li><strong>Assigned Outlet (`outlet_id`):</strong> Assign the staff member to <em>Central HQ</em> or a specific <em>Retail Branch</em>. This locks their operational boundaries.</li>
            <li><strong>Assigned Roles:</strong> Multi-select one or more pre-configured roles (e.g. <em>Cashier</em>, <em>Branch Manager</em>).</li>
        </ul>
    </div>

    <div class="step-box">
        <span class="step-number">Step 2: Viewing &amp; Filtering Users:</span> The Users index table displays user avatar, full name, username, email, phone, branch badge, active roles, and creation date. Use the search input to filter users by name, role, or branch station.
    </div>

    <div class="step-box">
        <span class="step-number">Step 3: Editing User Accounts:</span> Click the <strong>Edit (Pencil)</strong> icon next to any user. Administrators can update contact information, reassign the user to a different branch station, grant or revoke roles, or reset the account password.
    </div>

    <div class="step-box">
        <span class="step-number">Step 4: Deleting &amp; Deactivating Users:</span> Click the <strong>Delete (Trash)</strong> icon to remove access. DarpPOS implements safe soft-deletes: the user cannot log in, but all historical transactions, shift audits, and sales associated with that employee remain permanently intact for audit compliance.
    </div>

    <h2>2.2 Roles &amp; Granular Permissions Framework</h2>
    <p>
        Access permissions are governed via Role-Based Access Control (RBAC). Navigate to <strong>User Setup &gt; Manage Users &gt; Roles</strong>.
    </p>

    <div class="step-box">
        <span class="step-number">Step 1: Creating a Custom Role:</span> Click <strong>+ Create Role</strong>.
        <ul>
            <li>Enter a descriptive <strong>Role Name</strong> (e.g., <code>Inventory Auditor</code>, <code>Senior Cashier</code>, <code>Logistics Officer</code>).</li>
            <li><strong>Permissions Matrix:</strong> Toggle checkboxes across functional modules:
                <ul>
                    <li><em>Catalog &amp; Products:</em> <code>product-list</code>, <code>product-create</code>, <code>product-edit</code>, <code>product-delete</code>, <code>product-price-edit</code>.</li>
                    <li><em>Point of Sale:</em> <code>sale-list</code>, <code>sale-create</code>, <code>sale-cancel</code>, <code>sale-receipt-reprint</code>.</li>
                    <li><em>Procurement:</em> <code>purchase-list</code>, <code>purchase-create</code>, <code>purchase-edit</code>, <code>purchase-cancel</code>.</li>
                    <li><em>Logistics:</em> <code>stock-transfer-list</code>, <code>stock-transfer-create</code>, <code>stock-transfer-receive</code>.</li>
                    <li><em>Cash &amp; Banking:</em> <code>cash-reconciliation-create</code>, <code>cash-reconciliation-approve</code>, <code>bank-account-list</code>.</li>
                    <li><em>Administration:</em> <code>settings-manage</code>, <code>daily-transaction-email-send</code>, <code>user-list</code>, <code>role-create</code>.</li>
                </ul>
            </li>
            <li>Click <strong>Save Role</strong> to register the permissions bundle.</li>
        </ul>
    </div>

    <div class="step-box">
        <span class="step-number">Step 2: Editing &amp; Auditing Roles:</span> Click <strong>Edit</strong> on any role to dynamically adjust its privileges. Changes take effect across all assigned employees upon their next request. Click <strong>Show</strong> to inspect all granted permissions grouped by domain.
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25%;">Standard Role</th>
                <th style="width: 35%;">Recommended Staff</th>
                <th style="width: 40%;">Core Granted Privileges</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><span class="badge badge-primary">Super Admin</span></td>
                <td>Executive Directors &amp; IT Leads</td>
                <td>Unrestricted enterprise-wide authority across all branches, settings, users, and financials.</td>
            </tr>
            <tr>
                <td><span class="badge badge-info">Warehouse Manager</span></td>
                <td>Central Store Inventory Leads</td>
                <td>Supplier receiving, bulk purchase entry, dispatching stock transfers, inventory auditing.</td>
            </tr>
            <tr>
                <td><span class="badge badge-success">Branch Manager</span></td>
                <td>Store / Branch Station Leads</td>
                <td>Confirming inbound transfers, approving shift reconciliations, localized adjustments.</td>
            </tr>
            <tr>
                <td><span class="badge badge-warning">POS Cashier</span></td>
                <td>Retail Till Operators</td>
                <td>Barcode scanning, processing walk-in cash/MoMo sales, receipt reprinting, shift counting.</td>
            </tr>
            <tr>
                <td><span class="badge badge-secondary">Accountant</span></td>
                <td>Finance &amp; Audit Officers</td>
                <td>General ledger, chart of accounts, banking, P&amp;L, balance sheet, customer/vendor debt ledgers.</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- ================= CHAPTER 3 ================= -->
<div class="chapter-break">
    <h1>Chapter 3: Company Setup, Outlets &amp; Global Policies</h1>

    <h2>3.1 Company Profile Configuration</h2>
    <p>
        Navigate to <strong>Company Setup</strong> to define your legal corporate profile.
    </p>
    <ul>
        <li><strong>Company Name:</strong> Registered business trading name displayed on thermal receipts and invoices.</li>
        <li><strong>Taxpayer Identification Number (TIN):</strong> Formatted legal tax number printed on official receipts for statutory tax compliance.</li>
        <li><strong>Address &amp; Contacts:</strong> Physical headquarters address, official phone numbers, and support email.</li>
        <li><strong>Corporate Logo:</strong> Upload PNG/JPEG brand mark for receipt headers and PDF financial statements.</li>
    </ul>

    <h2>3.2 Multi-Outlet Branch Management</h2>
    <p>
        Navigate to <strong>Outlets</strong> to administer retail branches and warehouse depots.
    </p>
    <ul>
        <li><strong>Creating an Outlet:</strong> Click <em>+ Create Outlet</em>. Enter Branch Name (e.g. <em>Kumasi Main Branch</em>), unique Branch Code (e.g. <code>KMS-01</code>), contact telephone, email, and location.</li>
        <li><strong>Central HQ Designation:</strong> Mark exactly one outlet as <code>is_central = True</code>. This marks the hub warehouse where bulk supplier deliveries arrive.</li>
        <li><strong>Outlet Status:</strong> Active outlets appear on cash registers and transfer dropdowns. Deactivating an outlet gracefully suspends operations without data loss.</li>
    </ul>

    <h2>3.3 Global System Policies &amp; Governance</h2>
    <p>
        Central administrators configure business guardrails under <strong>Settings &gt; Global System Settings</strong>:
    </p>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 32%;">Setting Name</th>
                <th style="width: 20%;">Default Value</th>
                <th style="width: 48%;">Operational Impact &amp; Security Control</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>session_lifetime_minutes</code></td>
                <td>120 Minutes</td>
                <td>Auto-logs out inactive till terminals to prevent unauthorized register access.</td>
            </tr>
            <tr>
                <td><code>session_expire_on_close</code></td>
                <td>Enabled</td>
                <td>Immediately destroys cashier session tokens when the browser tab is closed.</td>
            </tr>
            <tr>
                <td><code>enforce_previous_day_reconciliation</code></td>
                <td>Enabled</td>
                <td>Blocks cashiers from starting new POS sales until yesterday's cash drawer count is signed off.</td>
            </tr>
            <tr>
                <td><code>allow_negative_stock</code></td>
                <td>Disabled</td>
                <td>Strictly prevents selling out-of-stock products, eliminating unrecorded inventory overdrafts.</td>
            </tr>
            <tr>
                <td><code>prevent_backdated_invoices</code></td>
                <td>Enabled</td>
                <td>Restricts sales dates to current calendar day, eliminating revenue manipulation.</td>
            </tr>
            <tr>
                <td><code>allow_manual_discount_entry_by_staff</code></td>
                <td>Controlled</td>
                <td>Enables or disables cashier manual discount overrides at the checkout screen.</td>
            </tr>
            <tr>
                <td><code>purchase_edit_window_hours</code></td>
                <td>24 Hours</td>
                <td>Sets strict grace period for updating supplier bills before permanent ledger lock.</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- ================= CHAPTER 4 ================= -->
<div class="chapter-break">
    <h1>Chapter 4: Automated Daily Transaction Emails &amp; Reports</h1>

    <h2>4.1 Automated End-of-Day (EOD) Email Architecture</h2>
    <p>
        DarpPOS features an automated executive intelligence engine that dispatches an audited daily financial summary to directors and management at close of business.
    </p>

    <div class="callout callout-info">
        <span class="callout-title">Daily Email Summary Contains:</span>
        <ul>
            <li><strong>Total Gross Sales:</strong> Consolidated revenue generated across all operating branches.</li>
            <li><strong>Cash Collections:</strong> Realized cash, Mobile Money, and bank card payments collected today.</li>
            <li><strong>Credit Sales:</strong> Unpaid invoices issued to corporate customers (Accounts Receivable created).</li>
            <li><strong>Approved Expenses:</strong> Operational cash outflows paid out via authorized Expense Vouchers.</li>
            <li><strong>Cash Drawer Variances:</strong> Overages or shortages flagged during shift reconciliations.</li>
            <li><strong>Reconciliation Status:</strong> List of branches that completed drawer audits vs pending stations.</li>
            <li><strong>Net Operating Cash Flow:</strong> Net liquidity added to the business across all cash points.</li>
        </ul>
    </div>

    <h2>4.2 Managing the Daily Email Reporting Dashboard</h2>
    <p>
        Navigate to <strong>Settings &gt; Daily Email Report</strong>:
    </p>
    <ul>
        <li><strong>Real-Time Financial Totals:</strong> Displays running daily figures before email dispatch.</li>
        <li><strong>Delivery Status Indicator:</strong> Shows whether today's report has already been sent (with exact timestamp).</li>
        <li><strong>Scheduler Health Check:</strong> Displays background cron worker heartbeat (checks if automated scheduler is active).</li>
        <li><strong>Configurable Dispatch Time:</strong> Set automated send time (e.g., <code>18:00</code> for 6:00 PM close of business).</li>
    </ul>

    <h2>4.3 Manual On-Demand Email Dispatch</h2>
    <div class="step-box">
        <span class="step-number">Step-by-Step Manual Send:</span>
        <ol>
            <li>Navigate to <strong>Settings &gt; Daily Email Report</strong>.</li>
            <li>Review the daily totals card (Sales, Collections, Reconciliations, Expenses, Net Flow).</li>
            <li>Click the green <strong>Send Daily Transaction Report Now</strong> button.</li>
            <li>Confirm the prompt. The system aggregates branch metrics, compiles the responsive executive email, dispatches it to configured recipients, and writes an entry to the audit log.</li>
        </ol>
    </div>

    <h2>4.4 Email Audit Logs &amp; Delivery Tracking</h2>
    <p>
        Navigate to <strong>Settings &gt; Daily Email Logs</strong> to monitor delivery history:
    </p>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 15%;">Business Date</th>
                <th style="width: 20%;">Sent Timestamp</th>
                <th style="width: 25%;">Recipients</th>
                <th style="width: 25%;">Financial Payload</th>
                <th style="width: 15%;">Status</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>2026-10-31</td>
                <td>18:00:15 (Automated)</td>
                <td><code>ceo@darppos.com, cfo@darppos.com</code></td>
                <td>Sales: ₵45,200 | Collections: ₵41,800 | Net: ₵39,400</td>
                <td><span class="badge badge-success">Sent (200 OK)</span></td>
            </tr>
            <tr>
                <td>2026-10-30</td>
                <td>19:15:42 (Manual - Admin)</td>
                <td><code>directors@darppos.com</code></td>
                <td>Sales: ₵38,900 | Collections: ₵38,900 | Net: ₵37,100</td>
                <td><span class="badge badge-success">Sent (200 OK)</span></td>
            </tr>
            <tr>
                <td>2026-10-29</td>
                <td>18:00:02 (Automated)</td>
                <td><code>management@darppos.com</code></td>
                <td>SMTP Connection Timeout (Port 587)</td>
                <td><span class="badge badge-danger">Failed (Logged)</span></td>
            </tr>
        </tbody>
    </table>
    <p>
        If an email delivery fails (e.g. SMTP credentials expired), the log captures the full diagnostic trace and permits administrators to trigger an instant re-send once connection is restored.
    </p>
</div>

<!-- ================= CHAPTER 5 ================= -->
<div class="chapter-break">
    <h1>Chapter 5: Product Catalog &amp; Dual-Packaging Setup</h1>

    <h2>5.1 Units &amp; Sub-Units Master</h2>
    <p>
        Navigate to <strong>Units</strong> to establish packaging units:
    </p>
    <ul>
        <li><strong>Wholesale Outer Units:</strong> Box, Bag, Carton, Crate, Roll, Bale.</li>
        <li><strong>Retail Inner Sub-Units:</strong> Piece, Bottle, Sachet, Packet, Kilogram, Meter.</li>
    </ul>

    <h2>5.2 Creating Dual-Packaging Products</h2>
    <div class="step-box">
        <span class="step-number">Step-by-Step Product Creation:</span> Navigate to <strong>Catalog &gt; Products &gt; Create</strong>:
        <ol>
            <li><strong>General Information:</strong> Enter Product Name, Category, and Barcode (scan or enter manual code).</li>
            <li><strong>Wholesale Packaging Unit:</strong> Select Outer Unit (e.g., <em>Box</em>).</li>
            <li><strong>Sub-Unit Packaging Switch:</strong> Toggle <em>Has Sub-Unit (Loose Pieces)</em> to <strong>YES</strong>.</li>
            <li><strong>Sub-Unit Selection:</strong> Select Inner Unit (e.g., <em>Piece</em>).</li>
            <li><strong>Pieces per Pack:</strong> Specify conversion factor (e.g., <code>10</code> pieces per box; <code>24</code> cans per carton).</li>
            <li><strong>Wholesale Pricing:</strong> Enter Cost Price per Box (e.g., ₵100.00) and Selling Price per Box (e.g., ₵150.00).</li>
            <li><strong>Retail Piece Pricing:</strong> Enter Loose Piece Cost (e.g., ₵10.00) and Loose Piece Selling Price (e.g., ₵16.00). DarpPOS auto-calculates piece cost if left blank.</li>
            <li><strong>Alert Thresholds:</strong> Define Minimum Stock Level for reorder alerts.</li>
        </ol>
    </div>

    <h2>5.3 Barcode Label Generation &amp; Printing</h2>
    <p>
        Navigate to <strong>Products &gt; Print Barcodes</strong> to print thermal stickers or A4 label sheets:
    </p>
    <ul>
        <li>Select products and enter quantity of barcode labels required.</li>
        <li>System formats standard Code 128 / EAN-13 barcodes containing Product Name, Retail Price, and Barcode number.</li>
        <li>Affix printed barcodes directly to shelves, wholesale boxes, or individual loose pieces.</li>
    </ul>
</div>

<!-- ================= CHAPTER 6 ================= -->
<div class="chapter-break">
    <h1>Chapter 6: Inbound Procurement &amp; Supplier Management</h1>

    <h2>6.1 Supplier Directory Setup</h2>
    <p>
        Navigate to <strong>Suppliers &gt; Create</strong> to record supplier profiles: Company Name, Contact Representative, Telephone, Email, Physical Address, and Credit Terms.
    </p>

    <h2>6.2 Receiving Purchases into Central Store</h2>
    <div class="step-box">
        <span class="step-number">Step-by-Step Purchase Workflow:</span> Navigate to <strong>Purchases &gt; Create</strong>:
        <ol>
            <li>Select <strong>Purchase Date</strong> and <strong>Supplier Name</strong>. Enter vendor Invoice/Bill Number.</li>
            <li><strong>Line Items Entry:</strong> Select Product. Enter quantity in <strong>Wholesale Packs (Bags/Boxes)</strong> and any loose <strong>Pieces</strong>.</li>
            <li><strong>Auto-Calculation:</strong> The system automatically computes total base pieces:
                $$\text{Total Pieces} = (\text{Packs} \times \text{Pieces per Pack}) + \text{Loose Pieces}$$
            </li>
            <li>Enter Unit Cost Price per Pack. Total line item cost is calculated automatically.</li>
            <li><strong>Payment Terms:</strong> Select <em>Full Paid (Cash/Bank)</em>, <em>Partial Payment</em>, or <em>Full Credit Purchase</em>.</li>
            <li>Click <strong>Submit Purchase</strong>. Goods are immediately committed into Central Store inventory, and supplier accounts payable are posted.</li>
        </ol>
    </div>

    <h2>6.3 Purchase Auditing &amp; Edit Trail</h2>
    <p>
        Purchases can be viewed under <strong>Purchases &gt; All Purchases</strong>. If an invoice requires adjustment (e.g. supplier issued a credit note), edits are permitted within the configured <code>purchase_edit_window_hours</code>. All modifications are permanently recorded in the <strong>Purchase Edit Trail Report</strong>.
    </p>
</div>

<!-- ================= CHAPTER 7 ================= -->
<div class="chapter-break">
    <h1>Chapter 7: Logistics &amp; Inter-Branch Stock Transfers</h1>

    <h2>7.1 The Two-Phase Transfer Security Handshake</h2>
    <p>
        To prevent stock loss between warehouses and retail branches, DarpPOS enforces a rigorous two-phase chain of custody:
    </p>

    <div class="step-box">
        <span class="step-number">Phase 1: Dispatch (Central Warehouse):</span>
        <ol>
            <li>Navigate to <strong>Stock Transfers &gt; Create</strong>.</li>
            <li>Select <strong>Source Outlet</strong> (Central Warehouse) and <strong>Destination Outlet</strong> (Retail Branch).</li>
            <li>Add line items entering Transfer Quantity in <strong>Bags/Boxes</strong> and <strong>Pieces</strong>.</li>
            <li>Click <strong>Dispatch Transfer</strong>. Stock is immediately deducted from Central Warehouse and marked as <span class="badge badge-warning">In-Transit</span>. A Transfer Manifest slip prints with driver sign-off lines.</li>
        </ol>
    </div>

    <div class="step-box">
        <span class="step-number">Phase 2: Confirmation (Receiving Retail Branch):</span>
        <ol>
            <li>When the delivery truck arrives, the receiving branch manager logs into their terminal.</li>
            <li>Navigate to <strong>Stock Transfers &gt; All Transfers</strong>. The transfer appears with status <span class="badge badge-warning">In-Transit</span>.</li>
            <li>Click <strong>View (Eye)</strong> to inspect the manifest and physically inspect boxes and pieces.</li>
            <li>Click the green <strong>Confirm &amp; Receive</strong> button. Stock is committed to the local retail branch's inventory, and status updates to <span class="badge badge-success">Received</span>.</li>
        </ol>
    </div>
</div>

<!-- ================= CHAPTER 8 ================= -->
<div class="chapter-break">
    <h1>Chapter 8: Point of Sale (POS) &amp; Retail Cashiering</h1>

    <h2>8.1 POS Cashiering Interface</h2>
    <p>
        Navigate to <strong>Point of Sale (POS)</strong>. The screen is optimized for high-speed barcode checkout:
    </p>
    <ul>
        <li><strong>Barcode Input Field:</strong> Scan product barcode with handheld scanner. The product is added directly to the active sale cart.</li>
        <li><strong>Manual Catalog Search:</strong> Type product name or category to manually add non-barcoded goods.</li>
        <li><strong>Dual-Unit Selection in Cart:</strong> Cashiers can switch item quantity between <strong>Whole Packs</strong> (Bags/Boxes) or <strong>Loose Pieces</strong> with live price updates.</li>
        <li><strong>Customer Assignment:</strong> Defaults to <em>Walk-in Cash Customer</em>. Select a registered customer to enable credit sales or issue invoices.</li>
    </ul>

    <h2>8.2 Checkout &amp; Payment Processing</h2>
    <div class="step-box">
        <span class="step-number">Processing Payment:</span>
        <ol>
            <li>Review Cart Subtotal, applied discounts, and net bill total.</li>
            <li>Select Payment Mode:
                <ul>
                    <li><strong>Cash Sale:</strong> Cashier enters <em>Amount Tendered</em>. System displays exact <em>Change Due</em>.</li>
                    <li><strong>Mobile Money (MoMo):</strong> Cashier verifies mobile transaction reference.</li>
                    <li><strong>Bank POS / Card:</strong> Credit/debit card swipe authorization.</li>
                    <li><strong>Credit Sale:</strong> Charged to customer's account balance (subject to credit limit).</li>
                </ul>
            </li>
            <li>Click <strong>Complete Sale</strong>. The cash drawer kicks open, and the 80mm receipt prints instantly.</li>
        </ol>
    </div>

    <h2>8.3 Receipt Reprinting &amp; Delivery Verification</h2>
    <p>
        Cashiers can re-print any receipt via <strong>Sales &gt; All Sales &gt; Reprint Receipt</strong>. For warehouse pick-ups, navigate to <strong>Sales &gt; Delivery Verification</strong> to scan invoice barcodes and confirm physical departure of goods.
    </p>
</div>

<!-- ================= CHAPTER 9 ================= -->
<div class="chapter-break">
    <h1>Chapter 9: Customer &amp; Supplier Credit (AR / AP)</h1>

    <h2>9.1 Customer Credit (Accounts Receivable)</h2>
    <p>
        When goods are sold on credit, outstanding debts are managed under <strong>Customer Credit</strong>:
    </p>
    <ul>
        <li><strong>Credit Customer List:</strong> Displays customer name, phone number, total credit invoices, total amount paid, and current unpaid balance.</li>
        <li><strong>Aging Analysis:</strong> Debts are categorized into aging buckets (0-30 days, 31-60 days, 61-90 days, 90+ days overdue).</li>
        <li><strong>Collecting Debt Payments:</strong> Click <em>Collect Payment</em> next to any customer. Enter amount paid, payment method (Cash/MoMo/Bank), and receipt notes. The payment is applied against the oldest unpaid invoices, reducing their balance and recording a cash inflow.</li>
    </ul>

    <h2>9.2 Supplier Credit (Accounts Payable)</h2>
    <p>
        Supplier liabilities from wholesale procurement are audited under <strong>Supplier Credit</strong>:
    </p>
    <ul>
        <li>Displays pending manufacturer bills, payment terms, and total outstanding supplier balances.</li>
        <li>Click <em>Record Supplier Payment</em> to record corporate bank transfers or cash settlements, keeping accounts payable reconciled.</li>
    </ul>
</div>

<!-- ================= CHAPTER 10 ================= -->
<div class="chapter-break">
    <h1>Chapter 10: Expense Management &amp; Voucher Control</h1>

    <h2>10.1 Expense Categories</h2>
    <p>
        Navigate to <strong>Expense Categories</strong> to configure expense accounts (e.g. <em>Shop Rent</em>, <em>Staff Transport</em>, <em>Utility Bills</em>, <em>Packaging Materials</em>, <em>Generator Fuel</em>).
    </p>

    <h2>10.2 Creating &amp; Auditing Expense Vouchers</h2>
    <div class="step-box">
        <span class="step-number">Expense Voucher Workflow:</span> Navigate to <strong>Expense Vouchers &gt; Create</strong>:
        <ol>
            <li>Select <strong>Voucher Date</strong> and <strong>Assigned Outlet</strong>.</li>
            <li>Select <strong>Expense Category</strong> and enter paid amount.</li>
            <li>Enter descriptive <strong>Particulars / Purpose</strong> (e.g., <em>"Purchased 50 litres of diesel for generator"</em>).</li>
            <li>Select Payment Mode (Cash Drawer vs Corporate Bank Account).</li>
            <li>Click <strong>Save &amp; Print Voucher</strong>. The voucher prints for payee signature, and cash is deducted from the daily drawer balance.</li>
        </ol>
    </div>
</div>

<!-- ================= CHAPTER 11 ================= -->
<div class="chapter-break">
    <h1>Chapter 11: Shift End &amp; Cash Drawer Reconciliation</h1>

    <h2>11.1 The Shift Balancing Protocol</h2>
    <p>
        At the end of every operating shift, cashiers must perform a physical denomination count under <strong>Cash Reconciliation &gt; Create</strong>:
    </p>

    <div class="step-box">
        <span class="step-number">Reconciliation Procedure:</span>
        <ol>
            <li><strong>Select Shift &amp; Cashier:</strong> Select current date and cashier till station.</li>
            <li><strong>Physical Denomination Audit:</strong> Enter count for each physical note and coin in the drawer:
                <ul>
                    <li>Count of ₵200 notes &bull; ₵100 notes &bull; ₵50 notes &bull; ₵20 notes</li>
                    <li>Count of ₵10 notes &bull; ₵5 notes &bull; ₵2 notes &bull; ₵1 notes &bull; Coins</li>
                </ul>
            </li>
            <li><strong>Automatic Variance Calculation:</strong> The system computes:
                $$\text{Expected Cash} = \text{Opening Float} + \text{Cash Sales} + \text{Credit Collections} - \text{Cash Expenses}$$
                $$\text{Variance} = \text{Actual Counted Cash} - \text{Expected Cash}$$
            </li>
            <li><strong>Status Categorization:</strong>
                <ul>
                    <li><span class="badge badge-success">Balanced:</span> Actual matches Expected exactly (₵0.00 difference).</li>
                    <li><span class="badge badge-danger">Shortage:</span> Actual cash is less than Expected. Cashier must enter an explanatory justification note.</li>
                    <li><span class="badge badge-warning">Overage:</span> Actual cash exceeds Expected. Justification note required.</li>
                </ul>
            </li>
            <li>Click <strong>Submit Reconciliation</strong>. The shift is locked, and a supervisor reviews and approves the record.</li>
        </ol>
    </div>
</div>

<!-- ================= CHAPTER 12 ================= -->
<div class="chapter-break">
    <h1>Chapter 12: Stock Adjustments &amp; Inventory Audits</h1>

    <h2>12.1 Conducting Physical Inventory Counts</h2>
    <p>
        Stock adjustments align system records with physical shelf counts under <strong>Stock Adjustments &gt; Create</strong>:
    </p>

    <div class="step-box">
        <span class="step-number">Stock Adjustment Steps:</span>
        <ol>
            <li>Select <strong>Outlet</strong> and <strong>Adjustment Date</strong>.</li>
            <li>Search and add products. System displays <strong>Current System Stock</strong> in Wholesale Packs and Loose Pieces.</li>
            <li>Enter <strong>Physical Count</strong> in Bags/Boxes and Pieces.</li>
            <li>Select <strong>Adjustment Reason:</strong>
                <ul>
                    <li><em>Damage / Breakage:</em> Broken or expired goods removed from shelf.</li>
                    <li><em>Shrinkage / Loss:</em> Unexplained physical count deficit.</li>
                    <li><em>Stock Count Correction:</em> Discrepancy discovered during routine audit.</li>
                    <li><em>Surplus Found:</em> Physical count exceeds recorded ledger.</li>
                </ul>
            </li>
            <li>Click <strong>Save Adjustment</strong>. System balances are adjusted immediately and posted to the general ledger.</li>
        </ol>
    </div>
</div>

<!-- ================= CHAPTER 13 ================= -->
<div class="chapter-break">
    <h1>Chapter 13: Double-Entry Accounting &amp; Banking</h1>

    <h2>13.1 Chart of Accounts (COA) Structure</h2>
    <p>
        Navigate to <strong>Accounting Setup &gt; Chart of Accounts</strong>. DarpPOS maintains a standardized 5-tier financial hierarchy:
    </p>
    <ul>
        <li><strong>1000 - Assets:</strong> Cash in Drawer, Bank Operating Accounts, Inventory on Hand, Accounts Receivable.</li>
        <li><strong>2000 - Liabilities:</strong> Accounts Payable (Suppliers), Short-Term Bank Loans, Accrued Taxes.</li>
        <li><strong>3000 - Equity:</strong> Owner's Capital, Retained Earnings, Partner Contributions.</li>
        <li><strong>4000 - Revenue:</strong> POS Walk-in Sales, Wholesale Delivery Revenue, Miscellaneous Income.</li>
        <li><strong>5000 - Cost of Goods Sold (COGS):</strong> Direct Inventory Cost of items sold.</li>
        <li><strong>6000 - Operating Expenses:</strong> Rent, Utilities, Staff Salaries, Logistics &amp; Transport.</li>
    </ul>

    <h2>13.2 Banking &amp; Capital Loan Accounts</h2>
    <p>
        Manage bank accounts under <strong>Accounting Setup &gt; Bank Accounts</strong>. Record corporate deposits, withdrawals, and bank charges. Track capital injections and business loan principal/interest repayments under <strong>Capital Loans</strong>.
    </p>

    <h2>13.3 Financial Statements &amp; Executive Reports</h2>
    <p>
        Navigate to <strong>Accounting Reports</strong> for full financial intelligence:
    </p>
    <ul>
        <li><strong>Trial Balance:</strong> Real-time debit and credit equality verification across all active ledger accounts.</li>
        <li><strong>General Ledger:</strong> Comprehensive audit trail of all automated transaction journal postings.</li>
        <li><strong>Income Statement (P&amp;L):</strong> Calculates Gross Margin and Net Operating Profit over any selected date range.</li>
        <li><strong>Balance Sheet (PDF Export):</strong> Formal statement of Financial Position (Assets = Liabilities + Equity) ready for board review and banking partners.</li>
        <li><strong>Cash Flow Statement:</strong> Operational, investing, and financing cash movements.</li>
    </ul>
</div>

<!-- ================= CHAPTER 14 ================= -->
<div class="chapter-break">
    <h1>Chapter 14: Business Intelligence &amp; Movement Reports</h1>

    <h2>14.1 Core Executive &amp; Operational Reports</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25%;">Report Name</th>
                <th style="width: 35%;">Data &amp; Metrics Provided</th>
                <th style="width: 40%;">Executive Management Value</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Stock Balance Report</strong></td>
                <td>Current stock across wholesale packs (Bags/Boxes) and loose pieces with sub-unit breakdown.</td>
                <td>Eliminates stock-outs and optimizes warehouse reorder planning.</td>
            </tr>
            <tr>
                <td><strong>Stock Valuation Report</strong></td>
                <td>Inventory quantity multiplied by unit cost and unit selling price per piece.</td>
                <td>Accurate working capital valuation for monthly balance sheet audit.</td>
            </tr>
            <tr>
                <td><strong>Product-Wise Stock Trail</strong></td>
                <td>Complete historical audit trail of purchases, transfers in/out, sales, and adjustments.</td>
                <td>Pinpoints exact date, time, and user responsible for any physical inventory discrepancy.</td>
            </tr>
            <tr>
                <td><strong>Sales Lines Detail</strong></td>
                <td>Item-by-item breakdown of products sold, units (packs/pieces), cost, selling price, and profit.</td>
                <td>Identifies highest-margin product lines and top performing cashiers.</td>
            </tr>
            <tr>
                <td><strong>Cash Movement Report</strong></td>
                <td>Daily walk-in sales cash vs credit repayments vs supplier payments and expenses.</td>
                <td>Essential for tracking branch cash liquidity and banking deposits.</td>
            </tr>
            <tr>
                <td><strong>Receipt Audit Report</strong></td>
                <td>Full historical log of issued receipts, re-prints, and cancellations.</td>
                <td>Detects cashier till anomalies and prevents unauthorized duplicate prints.</td>
            </tr>
        </tbody>
    </table>
    <p>
        All reports feature interactive date-range pickers, outlet filters, and one-click export to <strong>PDF</strong> and <strong>Excel / CSV</strong>.
    </p>
</div>

<!-- ================= CHAPTER 15 ================= -->
<div class="chapter-break">
    <h1>Chapter 15: AI Business Chat Assistant (`/admin/chat`)</h1>

    <h2>15.1 Overview &amp; Natural Language Analytics</h2>
    <p>
        The <strong>Business Chat</strong> module allows executives and branch staff to query the system using natural conversational English. Navigate to <strong>Business Chat</strong> from the top navigation bar.
    </p>

    <h2>15.2 Querying Stock in Bags/Boxes &amp; Loose Pieces</h2>
    <p>
        The AI assistant is fully integrated with DarpPOS dual-packaging architecture:
    </p>
    <ul>
        <li><code>"Show stock balance in bags/boxes & pieces"</code> &rarr; Returns full table with whole packs and loose pieces.</li>
        <li><code>"How many boxes and pieces of Indomie in stock?"</code> &rarr; Instant breakdown (e.g. <em>5 boxes, 4 pieces (54 total pcs)</em>).</li>
        <li><code>"Stock value for this outlet"</code> &rarr; Multi-outlet valuation computed using unit piece costs.</li>
        <li><code>"Best-selling products this month"</code> &rarr; Top 10 items sold formatted in packs and loose pieces.</li>
        <li><code>"What were total sales today?"</code> &rarr; Instant daily sales count and gross revenue.</li>
        <li><code>"Customer outstanding balances"</code> &rarr; Full accounts receivable debtor breakdown.</li>
    </ul>

    <h2>15.3 Scoped Multi-Outlet Filtering</h2>
    <p>
        Central administrators can toggle the chat scope dropdown between <em>All Outlets &amp; Central HQ (Consolidated)</em> or individual branch stations. Branch employees are automatically locked to their assigned outlet station.
    </p>
</div>

<!-- ================= CHAPTER 16 ================= -->
<div class="chapter-break">
    <h1>Chapter 16: Daily Checklists, Troubleshooting &amp; Support</h1>

    <h2>16.1 Daily Cashier Opening Checklist</h2>
    <ul>
        <li>&#9744; Power on till PC, barcode scanner, and 80mm thermal receipt printer.</li>
        <li>&#9744; Verify thermal paper roll is loaded correctly (thermal side facing print head).</li>
        <li>&#9744; Log in with assigned personal credentials under <code>/admin/login</code>.</li>
        <li>&#9744; Confirm previous day's shift was reconciled. Enter opening cash float.</li>
        <li>&#9744; Scan a sample barcode to verify scanner connectivity.</li>
    </ul>

    <h2>16.2 Daily Warehouse Closing Checklist</h2>
    <ul>
        <li>&#9744; Confirm all inbound supplier shipments have been received and verified.</li>
        <li>&#9744; Verify that all dispatched stock transfers have arrived at branches or are tracked in transit.</li>
        <li>&#9744; Perform periodic stock adjustments for any broken or damaged goods.</li>
        <li>&#9744; Secure physical warehouse doors, verify locks, and arm facility alarm systems.</li>
    </ul>

    <h2>16.3 Troubleshooting Common Questions</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 28%;">Symptom</th>
                <th style="width: 36%;">Root Cause</th>
                <th style="width: 36%;">Actionable Solution</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Barcode scanner does not input product</strong></td>
                <td>USB cable disconnected or missing barcode on product master.</td>
                <td>Check cable port; search product by name and add missing barcode under <em>Catalog &gt; Products &gt; Edit</em>.</td>
            </tr>
            <tr>
                <td><strong>Cannot see products or data from other outlets</strong></td>
                <td>User account is locked to a specific retail branch.</td>
                <td>This is normal system security isolation. Only Central HQ accounts have cross-outlet visibility.</td>
            </tr>
            <tr>
                <td><strong>Thermal receipt prints blank paper</strong></td>
                <td>Thermal paper roll inserted upside-down.</td>
                <td>Flip thermal paper roll so thermal-coated side faces the printer thermal head.</td>
            </tr>
            <tr>
                <td><strong>Stock transfer cannot be confirmed</strong></td>
                <td>Logged into wrong outlet, or transfer already received.</td>
                <td>Ensure you are logged into the destination branch specified on the transfer manifest.</td>
            </tr>
            <tr>
                <td><strong>Daily transaction email not received</strong></td>
                <td>SMTP credentials error or email feature disabled in settings.</td>
                <td>Check <em>Settings &gt; Daily Email Logs</em> to review delivery error message; verify SMTP settings.</td>
            </tr>
            <tr>
                <td><strong>Cannot create new user accounts</strong></td>
                <td>User is logged into a retail branch station.</td>
                <td>User and role administration is restricted strictly to Central Headquarters staff.</td>
            </tr>
        </tbody>
    </table>

    <div style="height: 15px;"></div>
    <div class="callout callout-info" style="text-align: center;">
        <span class="callout-title" style="font-size: 10px;">Technical Support &amp; Customer Care</span>
        For enterprise technical assistance, software configuration, or training inquiries:<br>
        <strong>Email:</strong> <code>support@darppos.com</code> &bull; <strong>Emergency Helpdesk:</strong> <code>024-222-2874</code> &bull; <strong>Portal:</strong> <code>https://darppos.com/support</code>
    </div>
</div>

</body>
</html>
HTML;

$pdf = Pdf::loadHTML($html);
$pdf->setPaper('a4', 'portrait');
$pdf->render();

$canvas = $pdf->getDomPDF()->getCanvas();
$canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
    if ($pageNumber > 1) {
        $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
        $size = 7.5;
        $color = [0.45, 0.52, 0.62];
        $text = "DarpPOS Multi-outlet Management System  |  Page {$pageNumber} of {$pageCount}";
        $width = $fontMetrics->getTextWidth($text, $font, $size);
        $x = ($canvas->get_width() - $width) / 2;
        $y = $canvas->get_height() - 22;
        $canvas->text($x, $y, $text, $font, $size, $color);
    }
});

// Output to docs directory
$publicPath = public_path('docs/DarpPOS_User_Manual.pdf');
$rootPath = base_path('DarpPOS_User_Manual.pdf');
$legacyPublicPath = public_path('docs/Confidence_POS_User_Manual.pdf');
$legacyRootPath = base_path('Confidence_POS_User_Manual.pdf');

$output = $pdf->output();

file_put_contents($publicPath, $output);
file_put_contents($rootPath, $output);
file_put_contents($legacyPublicPath, $output);
file_put_contents($legacyRootPath, $output);

echo "PDF Successfully Generated!\n";
echo "Public Path: " . $publicPath . " (" . filesize($publicPath) . " bytes)\n";
echo "Root Path: " . $rootPath . " (" . filesize($rootPath) . " bytes)\n";
