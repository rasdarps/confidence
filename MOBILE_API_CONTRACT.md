# DarpPOS Mobile API: Implemented Contract

## Status and Setup

All first-release APIs are implemented: 30 endpoint/method pairs, comprising
4 authentication routes and 26 business routes. No Flutter application is
included. Paths start with `/api` with no version segment or `/api/me` duplicate.
Supporting reads beyond the original list are supplier lookup, paid customer
invoices, and expense details. See MOBILE_API_PERMISSION_MATRIX.md for access.

Run `php artisan migrate` before using writes. The new migration creates
mobile_api_mutations for persistent idempotency and adds the nullable
payment_details.reference column. It does not seed or alter invoice values.
Accounting accounts and open fiscal periods must be ready. Existing runtime
POS settings apply. Use HTTPS in production; normal-token expiry still needs
a deployment policy. First-login tokens already expire in 10 minutes.

## HTTP Conventions

- JSON requests; normal authenticated requests use `Authorization: Bearer`.
- Single resources use `{"data": {...}}`. Lists use data plus pagination meta.
- IDs/counts: integers. Quantities: numbers. Money: decimal strings (`"48.00"`).
  Invoice/purchase/expense currency is GHS; bank currencies remain distinct.
- Dates: YYYY-MM-DD in the application timezone (UTC default); timestamps ISO.
  Date filters are inclusive; missing list/report dates mean all time.
  Dashboard defaults today. Stock/bank balances are current, not snapshots.
- page defaults 1, per_page defaults 25 and is capped at 100. Ordering is
  deterministic. Invalid/reversed date ranges are 422.
- GET succeeds with 200; business POST with 201; logout with 204.
- 401 invalid/missing/expired token; 403 permission/forced-change denial;
  404 missing/out-of-scope record; 422 validation; 409 retry-key conflict;
  429 throttle. API responses do not redirect to Blade pages.

```json
{
  "data": [],
  "meta": {
    "current_page": 1, "per_page": 25, "total": 0, "last_page": 1,
    "from_date": null, "to_date": null
  }
}
```

Validation responses use Laravel's message and field errors:

```json
{ "message": "Payment must not exceed the current outstanding balance.",
  "errors": { "amount": ["Payment must not exceed the current outstanding balance."] } }
```

## Authentication: 4 Endpoints

### POST /api/auth/login

Body: username (email or username), password. Throttled at 5/minute. Invalid
credentials: generic 401. Normal login: HTTP 200 with token_type, access_token,
requires_password_change:false, and user. The user fields are id, name,
username, email, phone, user_type, must_change_password, roles, permissions,
capabilities, and pos_settings.

Forced-change login returns HTTP 200, without a profile:

```json
{
  "message": "Change your temporary password before accessing the application.",
  "code": "password_change_required",
  "requires_password_change": true,
  "token_type": "Bearer",
  "access_token": "<restricted_token>",
  "expires_at": "2026-10-04T12:10:00.000000Z"
}
```

Flutter must show password change, not dashboard. This token expires after
10 minutes and cannot access /api/user, logout, or any business endpoint.

### POST /api/auth/change-password

Send the restricted token and current_password, password, password_confirmation.
The new password must be 8-255 characters, contain uppercase/lowercase and a
number, be confirmed, and differ from the temporary password. Throttled 5/min.
Success clears the flag, revokes all prior API tokens for this user, and returns
HTTP 200 with a NEW normal token and user, requires_password_change:false.
Replace the token in secure storage; no second login is necessary.
Validation 422 leaves account/token unchanged; expired token 401 requires login
again. Normal tokens cannot call this first-login-only endpoint (403).
If the success response is lost, sign in with the new password.

### GET /api/user

Normal token required. Returns data:<safe user profile>. Use the server-computed
capabilities map to show actions, not role names alone. pos_settings contains
negative-stock, backdating, manual-discount and reconciliation flags. Laravel
still checks each operation independently of mobile UI visibility.

### POST /api/auth/logout

No body. Revoke current NORMAL token only; 204. Discard restricted tokens
locally if abandoning first login and let them expire; they cannot call logout.

## Read Endpoints: 22

All lists also accept page/per_page. Date/query filters are optional.

| GET path | Query parameters | Response |
|---|---|---|
| /api/products | search, barcode, category_id | Product list; name search, exact barcode. |
| /api/products/{id} | None | Active Product. |
| /api/sales | from_date, to_date, customer_id, payment_status, sale_id | Sale list, date DESC then ID DESC. meta.total_sales covers all matching active invoices, not one page; meta.own_sales_only marks cashier scope. |
| /api/sales/{id} | None | Authorized Sale. |
| /api/customer-payments/credit | from_date, to_date, customer_id, sale_id, payment_status | Active Sale objects with due > 0 and meta.current_due. Dates select invoices, not historical debt. |
| /api/customer-payments/paid | Same as credit | Active settled Sale objects (due <= 0). |
| /api/customers | search | ID/name lookup; contacts only with customer-list. |
| /api/customers/{id} | None | Contacts, current_balance, currency, balance_scope. Cashier balance is own-sales-only. |
| /api/suppliers | search | ID/name lookup for purchase selection. |
| /api/purchases | from_date, to_date, supplier_id, payment_status | Purchase list by date DESC, ID DESC. |
| /api/purchases/{id} | None | Purchase. |
| /api/expense-categories | None | Active category ID/name list. |
| /api/expenses | from_date, to_date, category_id | Expense vouchers. Category selects matching vouchers; returned items/total remain the whole voucher. |
| /api/expenses/{id} | None | Expense voucher and lines. |
| /api/bank-accounts | None | Account ID/name/bank_name/currency/current_balance/is_active. No account numbers. Meta marks recorded app balance/as_of. |
| /api/bank-transactions | from_date, to_date, account_id | ID/date/account ID-name-currency/type/amount/reference_no/description. No currency mixing or banking writes. |
| /api/dashboard | from_date, to_date | Today default. Authorized data.cards: sales, collections, purchases, expenses, stock, bank_balances. Also dates/timezone/own_sales_only/settings. Omitted cards mean unauthorized. |
| /api/reports/sales | from_date, to_date, customer_id, payment_status, sale_id | data:invoice_count, invoice_total, paid_on_invoices_to_date, current_due_on_invoices, currency/date_basis/due_is_current/dates. Invoice dates. |
| /api/reports/collections | from_date, to_date, customer_id, sale_id | Receipt list ID/sale ID-no/payment_date/amount/reference, meta.money_collected totals all matches. Includes old invoices paid during the range. |
| /api/reports/stock | search, category_id | Product list plus meta.as_of/historical_snapshot:false/quantity_totals by unit. Optional value metadata with stock-value-report. Date filters rejected. |
| /api/reports/low-stock | search, category_id | Same stock structure, quantity <= stock_level, including zero/negative. |
| /api/reports/expenses | from_date, to_date, category_id | data:expense_total, currency, by_category, date_basis, dates. Category sums matching lines, not entire mixed-category vouchers. |

## Resource Fields

**Product:** id, name, barcode, category:{id,name}, unit:{id,name}, quantity,
stock_level, selling_price, currency. With stock-value-report only: cost_price,
stock_value_at_cost, stock_value_at_selling_price. Deleted products hidden.

**Sale:** id, sale_no, date, description, created_by, cancelled_at, currency,
customer:{id,name}, payment, items, receipt:{printed_at,delivered_at}.
Payment: paid_status, total_amount, paid_amount, due_amount, discount_amount,
taxable_amount, nhil_amount, getfund_amount, vat_amount, cash_tendered, change_due.
Item: product_id, product_name, quantity, unit, unit_selling_price, line_total.
Sales history lists can include cancelled rows; finance totals and paid/credit
lists exclude them. Soft-deleted invoices are hidden.

**Purchase:** id, purchase_no, date, description, created_by, cancelled_at,
currency, supplier:{id,name}, payment, items. Payment: paid_status, total_amount,
paid_amount, due_amount, discount_amount. Item: product_id, product_name,
quantity, unit, unit_cost_price, line_total.

**Expense:** id, voucher_date, reference_no, notes, currency, total_amount,
created_by, accounting_status, items. Item: id, expense_category_id,
category_name, amount, description. Deleted vouchers/items are excluded.

## Business Writes: 4 Endpoints

All require Idempotency-Key header: 8-128 letters/digits/underscores/hyphens.
Use a UUID per action; reuse only for the identical request retry. Keys persist
per USER, across tokens/devices, with no automatic expiry. New success is 201
with Idempotency-Replayed:false; identical replay returns saved 201 response
with true. Same key but different path/validated body:409. Validation/posting
failure rolls back the key and all writes. Keep the key after a timeout; never
blindly retry with a fresh key. Responses use data:<created resource>.

### POST /api/sales

```json
{
  "date": "2026-10-04", "customer_id": 3,
  "items": [{ "product_id": 4, "quantity": 2 }],
  "paid_status": "partial_payment", "paid_amount": 20,
  "cash_tendered": 20, "discount_amount": 0, "description": "Optional note"
}
```

Returns Sale. Items:1-100 distinct active products. Quantities >=0.01, max4
decimals. Money max2 decimals. Server loads prices; unit_selling_price,
line_total, total_amount and created_by are prohibited. Full payment requires
cash_tendered >= total; partial requires 0 < paid_amount < total; full credit
uses cash_tendered:0. Existing customer required; no inline create. Cash_sales
cannot hold credit/partial. Discounts follow POS settings and must be below
subtotal. Invalid stored price, insufficient stock, missing reconciliation or
backdated invoice restrictions cause 422. Price changes while checking out
are detected. The save updates stock/payment/history/accounting atomically.

### POST /api/sales/{id}/payments

```json
{ "amount": 20, "payment_date": "2026-10-04", "reference": "Optional reference" }
```

Returns data:{payment_id,reference,amount,payment_date,sale}. Amount is a NEW
payment, not cumulative paid; positive/max2 decimals and <= locked current
due. Sale must be active, authorized and outstanding. Date cannot precede
invoice; POS backdating setting applies. Reference max120 is persisted.
Receipt history and accounting are atomic; duplicate retry does not add debt
collection twice. Cashier cannot pay another cashier's invoice.

### POST /api/purchases

```json
{
  "date": "2026-10-04", "supplier_id": 7,
  "items": [{ "product_id": 4, "quantity": 10,
    "unit_cost_price": 12.50, "unit_selling_price": 20.00 }],
  "paid_status": "partial_payment", "paid_amount": 50,
  "discount_amount": 0, "description": "Optional note"
}
```

Returns Purchase. Existing supplier/products, 1-100 distinct products,
integer quantity >=1. Money max2 decimals; selling price > cost. Purchase-create
permission authorizes entered unit prices, matching web purchase entry. Server
recalculates lines/discounts; total_amount, estimated_amount, line_total and
created_by prohibited. Full credit/partial cannot use cash_purchase. Updates
product prices/history, inventory/payment/accounting atomically. Mobile POS
backdating restrictions apply. No supplier create/edit/cancel API.

### POST /api/expenses

```json
{
  "voucher_date": "2026-10-04", "reference_no": "Optional unique reference",
  "notes": "Optional note",
  "items": [{ "expense_category_id": 2, "amount": 25, "description": "Fuel" }]
}
```

Returns Expense. One to100 active-category lines; amount >0/max2 decimals.
Reference max100 and not reused even after soft deletion. Notes/descriptions
max1000. Server sums total; total_amount/created_by prohibited. Voucher/lines/
accounting atomic. Closed fiscal periods cause 422 date error and no commit.

## Deployment and Limits

No banking writes, customer/supplier creation, user/role/settings editing,
cancellation/deletion, forgotten-password, receipt authorization/reprint or
cash-reconciliation APIs are provided. Receipt data can be viewed but does not
authorize bypassing the web printing/audit workflow. Missing reconciliation
must be handled through the web app in this release.

Default Cashier roles lack sale-list, customer-list and dashboard-view; grant
needed permissions explicitly through the admin UI, never in Flutter. Test
against the real database engine in staging, especially concurrent writes;
local feature tests use isolated SQLite and do not certify MySQL concurrency.