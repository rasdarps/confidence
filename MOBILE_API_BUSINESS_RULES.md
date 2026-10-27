# DarpPOS Mobile API Business Rules

These notes were checked against the current Laravel source. They describe
verified rules and implemented first-release mobile API safeguards.

## Verified Application Behavior

### Identity and Current API

- `routes/api.php` defines the four authentication routes and all26 business
  routes listed in MOBILE_API_CONTRACT.md. Business APIs are implemented.
- `App\\Models\\User` now uses Spatie `HasRoles` and Sanctum `HasApiTokens`.
- Login accepts email or username, is throttled, returns a bearer token once,
  and uses a generic invalid-credentials response. Accounts marked
  `must_change_password` receive a 10-minute restricted token and
  `requires_password_change: true`, with no normal profile or business access.
- `/api/user` returns a profile allowlist, roles/permissions, server-computed
  capabilities and POS settings. Logout deletes only the current normal token.
- Normal Sanctum token expiration remains unconfigured (`null`); set a production
  token lifetime/rotation policy before deployment.

### First-Login Password Change (Implemented)

- `POST /api/auth/change-password` requires current password, new password,
  and confirmation. New passwords must be 8-255 characters, contain upper/
  lowercase letters and a number, and differ from the temporary password.
  Login and password-change routes throttle requests at 5/minute.
- For a correct login where `must_change_password=true`, issue a short-lived
  restricted token with exactly `password:change` ability, a 10-minute expiry,
  and `requires_password_change=true` in an HTTP 200 response.
- The restricted token must not access `/api/user`, sales, purchases, reports,
  or any normal app endpoint. Enforce this with middleware on all normal API
  routes using `RestrictMobilePasswordChange` in the `api` middleware group.
  This also protects future business endpoints; Sanctum abilities alone do
  not restrict routes automatically.
- On successful password change, update the hash and clear
  `must_change_password` in a transaction with a locked user/token. Revoke all
  this user's prior API tokens and return a new normal token and profile.
  Another login is not necessary. A stale restricted token never becomes a
  normal token when the flag changes.
- Tests cover invalid current password, weak/unconfirmed/reused password,
  restricted-token business access, expiry, successful change/token rotation,
  unchanged account state after rejection, and rate limits.
- Browser login and business pages use web authentication. The API must use
  bearer-token authentication rather than relying on browser cookies.

### Sales and Payments

- Payment statuses are `full_payment`, `partial_payment`, and `full_credit`
  (`App\\Support\\PaymentStatus`).
- Full payment means paid equals invoice total and due is zero. Partial payment
  requires a positive payment less than the total. Full credit means paid zero
  and due equals the full total.
- Sale tax calculation in `SaleController::store` treats entered line totals
  as tax-inclusive: taxable = net total / 1.20; NHIL 2.5%, GETFund 2.5%, VAT
  15% of taxable. The API should return the same configured tax breakdown.
- Partial/credit sales cannot be assigned to the configured `cash_sales`
  customer. Cash-customer full-payment behavior exists in the web controller.
- If `pos.allow_negative_stock` is false, a sale cannot exceed available
  stock. The default config is false.
- If `pos.prevent_backdated_invoices` is true, sales before today are rejected.
  Previous-day cash reconciliation may gate sale creation when
  `pos.enforce_previous_day_reconciliation` is true.
- A sale writes sale header, sale lines, payment, payment detail, product
  quantity decrement, stock ledger outflow, and accounting posting in a DB
  transaction. API sale writes must preserve these consistency guarantees.
- Customer payments cannot exceed the current due, must be positive, append a
  payment-detail record, update paid/due/status, and post an accounting receipt.
- Cashier ownership has been applied in the web All Sales and customer
  paid/credit pages: a Cashier sees sales/payment rows only when the related
  sale's `created_by` is their own user ID. API list, detail, dashboard,
  report, and payment mutations must enforce the same scope.

### Sale Pricing Hardening Applied

`SaleController::store` now ignores client-supplied unit and line prices,
loads active products, derives current selling prices, recalculates each line
and invoice total, then derives discount/tax/payment from those server values.
It also rejects a discount greater than the calculated subtotal and detects a
product price change before the locked stock mutation. The mobile API must
preserve this behavior and accept product IDs/quantities rather than totals.

### Purchases and Supplier Payments

- Purchase statuses use the same three canonical values.
- Purchase quantities must be at least 1; purchase entry verifies selling
  price is greater than unit cost.
- Partial and full-credit purchases cannot use configured `cash_purchase`
  supplier. Cash supplier is for full-payment purchases only.
- A purchase increments current product quantity, may update stored cost/sale
  prices and price history, writes purchase lines, stock-ledger inflow, supplier
  payment/payment detail, and accounting posting transactionally.
- Supplier payment updates must preserve the same due/payment-history and
  accounting invariants as customer payments.

### Purchase Total Hardening Applied

`PurchaseController::store` still accepts user-entered supplier cost and
selling prices, as the purchase screen is designed to record invoice prices.
It now recalculates each line as quantity times entered cost, recalculates the
subtotal and discount, rejects discounts at or above the calculated subtotal,
and ignores client `buying_price`/`estimated_amount` totals. The mobile API
must preserve those rules and separately decide who may enter/change unit
prices.

### Expenses

- Expense vouchers require a voucher date and at least one line.
- Each line needs an active expense category and positive amount. Optional
  reference is unique among non-deleted vouchers; notes/line descriptions are
  bounded strings.
- Voucher total is the sum of line amounts. Voucher, lines, and accounting
  posting occur in a transaction.
- Expense list/report queries should exclude soft-deleted vouchers and items.

### Products, Stock, and Values

- Products have a current quantity, stock threshold, category, unit, cost
  price, and selling price; products use soft deletes.
- A product lookup for a cashier/sale should expose only POS-required values.
  Cost price and valuation are sensitive and require `stock-value-report`.
- Total quantities must be grouped by unit; do not add pieces to kilograms.
- Current quantity is not a historical snapshot. Do not offer past stock
  balances unless ledger-based historical calculations are designed and tested.

### Banking and Accounting

- The app's `bank_accounts.current_balance` is a recorded application balance,
  not a live bank balance.
- Bank movement types include deposit, withdrawal, transfer_in, transfer_out,
  bank_fee, interest_income, receipt, payment, and reconciliation_adjustment.
- Preserve account currency and movement type. Transfers are not business
  income/expense and different currencies must not be combined.
- Journal posting rejects closed fiscal periods. Sale, purchase, and expense
  APIs must preserve journal balance and rollback the whole transaction if
  posting fails.
- Existing finance calculations and reports have route-specific permissions;
  do not expose financial values just because a user can access the dashboard.

## Implemented API Rules

- **Server is authoritative:** recalculate sale/purchase/expense values; derive
  creator from token; derive stock and payment balances from database state.
- **Atomic writes:** sale/purchase/payment/expense domain rows, inventory and
  ledger changes, and accounting posting either all succeed or all roll back.
- **Concurrency:** lock the relevant product/payment rows and prevent oversell
  or overpayment when two devices submit simultaneously.
- **Retries:** all four business writes require Idempotency-Key. Per-user key,
  validated path/body hash and response JSON are stored in mobile_api_mutations
  in the same transaction; user row locking serializes same-user mutations.
  Replays return the saved result; changed route/body returns409. No automatic
  expiry is configured for retry records.
- **Cashier scope:** apply ownership predicates before rows, counts, aggregate
  totals, filters, pagination, nested details, exports, and writes.
- **Date policy:** use the application timezone. Enforce POS backdating and
  reconciliation settings on API writes, not only in Blade controllers.
- **Soft deletes/cancellation:** define consistent treatment for each endpoint.
  Reports should not silently include cancelled/deleted transactions where
  existing report behavior excludes them.
- **Audit:** persist authenticated creator IDs and payment/transaction history;
  do not accept user identity from the client.
- **Mobile errors:** return stable JSON with 401, 403 or masked 404, and 422
  validation errors; never return redirect/HTML responses from API routes.

## Verified Source Anchors

- `routes/api.php`: current API route.
- `app/Models/User.php`: roles and Sanctum token trait.
- `app/Http/Controllers/Backend/SaleController.php`: sale validation,
  pricing/tax/status/stock behavior.
- `app/Http/Controllers/Backend/PurchaseController.php`: purchase validation,
  pricing, stock, payment, and ledger behavior.
- `app/Http/Controllers/Backend/CustomerController.php`: customer payments
  and cashier payment-list/action scope.
- `app/Services/Accounting/AccountingIntegrationService.php`: sale, purchase,
  customer receipt, and expense accounting integration.
- `app/Services/StockLedgerService.php`: supported stock movement types.
- `database/seeders/PermissionTableSeeder.php`: permission names and default
  role grants (defaults are assigned when a role is first created).