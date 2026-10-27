# Implemented Mobile API Permission Matrix

All business APIs require auth:sanctum and API-wide password-change isolation.
Flutter should use server-computed capabilities, not role-name assumptions.
Laravel rechecks every request. OR means any one permission is sufficient.

| Endpoint | Permission | Capability / scope |
|---|---|---|
| POST /api/auth/login | Valid credentials, 5/min | Normal or restricted first-login token. |
| POST /api/auth/change-password | Exactly password:change token, unexpired, forced flag | No role bypass, 5/min. |
| GET /api/user | Normal authentication | Safe profile/capabilities/POS settings. |
| POST /api/auth/logout | Normal token | Revoke current token; 204. |
| GET /api/products | product-list OR sale-create OR purchase-create | product_lookup; costs only with stock-value-report. |
| GET /api/products/{id} | Same as product list | product_lookup, active only. |
| GET /api/sales | sale-list | sales_list; Cashier own-creator rows. |
| GET /api/sales/{id} | sale-list | sales_list, same scope; masked404. |
| POST /api/sales | sale-create | sales_create, authenticated creator. |
| GET /api/customer-payments/credit | payment-sales-credit-list | credit_list, Cashier own sales. |
| GET /api/customer-payments/paid | payment-sales-cash-list | paid_list, Cashier own sales. |
| POST /api/sales/{id}/payments | payment-sales-credit-pay | customer_payment, owner scope, locked due. |
| GET /api/customers | customer-list OR sale-create | customer_lookup; sale-create-only returns ID/name. |
| GET /api/customers/{id} | customer-list | customer_details; Cashier balance from own sales only. |
| GET /api/suppliers | supplier-list OR purchase-create | supplier_lookup, ID/name only. |
| GET /api/purchases | purchase-list | purchases_list. |
| GET /api/purchases/{id} | purchase-list | purchases_list. |
| POST /api/purchases | purchase-create | purchases_create, permits entered invoice costs/prices. |
| GET /api/expense-categories | expense-category-list OR expense-create OR expense-list | expense_categories. |
| GET /api/expenses | expense-list | expenses_list. |
| GET /api/expenses/{id} | expense-list | expenses_list. |
| POST /api/expenses | expense-create | expenses_create. |
| GET /api/bank-accounts | bank-account-list | bank_accounts, no account number. |
| GET /api/bank-transactions | bank-transaction-list | bank_transactions; read-only. |
| GET /api/dashboard | dashboard-view | dashboard; each card also authorized separately. |
| GET /api/reports/sales | sale-list OR report-sales-lines | sales_report, Cashier scope. |
| GET /api/reports/collections | report-sales-cash-movement | collections_report, Cashier scope/payment dates. |
| GET /api/reports/stock | stock-report | stock_report; value fields need stock-value-report. |
| GET /api/reports/low-stock | stock-report | stock_report; same field protection. |
| GET /api/reports/expenses | report-expense | expenses_report. |

stock_value capability controls cost/value fields, not route access by itself.
Ordinary logout revokes current token; first-login password change revokes all
previous API tokens for that user, then issues a new normal token.

## Dashboard Cards

- Sales: sale-list OR report-sales-lines.
- Collections: report-sales-cash-movement.
- Purchases: purchase-list.
- Expenses: expense-list OR report-expense.
- Stock: stock-report OR stock-value-report; quantities by unit.
- Bank balances: bank-account-list; grouped by currency.
- dashboard-view alone grants no access to these other metrics.

## Existing Role Notes

Cashier defaults include sale-create/print, paid/credit list/pay and cash
reconciliation permissions. Defaults lack sale-list, customer-list,
dashboard-view, purchase and expense permissions. Administrators must grant
needed reads explicitly. Manager/Supervisor defaults are only assigned at role
creation; do not assume current assignments equal defaults.

Super Admin Gate behavior is preserved and capabilities reflect it even when
no direct permission rows exist. Password-change restrictions cannot be
bypassed by a role or business permission. A user with the Cashier role is
creator-scoped regardless of other roles, matching existing web behavior.
Other roles retain existing permitted record access; this is not branch tenancy.

Apply scope before rows, filters, aggregates, nested resources and mutations.
Never accept creator IDs or roles from Flutter. Product/customer/supplier
lookup access for checkout is limited and does not grant administrative edits,
unrelated contact fields or costs. Handle 403 after permission changes and
never attempt to bypass a masked404 by choosing another invoice ID.