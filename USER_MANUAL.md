# DarpPOS Credit User Manual

Version: 1.0  
Prepared for: DarpPOS Credit users and administrators  
Last updated: 2026-08-30

## 1. Introduction

DarpPOS Credit is a web-based point of sale and business management system for handling sales, purchases, inventory, customers, suppliers, payments, expenses, cash reconciliation, accounting, reports, users, and role permissions.

The system is permission-based. Your menu may show fewer options than this manual depending on your assigned role.

## 2. Getting Started

### 2.1 Logging In

1. Open the application URL provided by your administrator.
2. Enter your email address and password.
3. Click **Login**.
4. If the system asks you to change your password on first login, enter a new password before continuing.

### 2.2 Dashboard and Insight

After login, users with dashboard permission can access:

- **Dashboard**: A summary area for business activity and key system information.
- **Insight**: A reporting/analytics area for reviewing trends and performance indicators.

### 2.3 Insight Page

Use **Insight** to review visual business performance charts and stock risk indicators.

The Insight page includes:

- **Hybrid Sales Trend (Last 30 Days)**: Compares invoice value, cash collected, credit due, and the 7-day invoice average.
- **Hybrid Purchase Trend (Last 30 Days)**: Compares purchase invoice value, cash paid, purchase credit due, and the 7-day purchase invoice average.
- **Top Category Contribution**: Shows which product categories contribute most to sales value.
- **Stock Health Risk (Shortfall)**: Highlights products below their stock level and shows the quantity shortfall.
- **Monthly Hybrid Overview**: Shows invoice value, cash collected, and credit due by month for the current year.
- **Monthly Purchase Hybrid Overview**: Shows purchase invoice value, cash paid, and purchase credit due by month for the current year.
- **Expense Insight**: Shows spending by expense category when expense data is available.

Use this page when reviewing sales performance, purchase activity, credit exposure, stock shortages, and expense patterns.

### 2.4 Business Chat

Open **Business Chat** from the sidebar. Access requires the `chat-use` permission, available under **Other Permissions** when editing a role, together with the permissions for each requested report. Super Admin access follows the existing application rules.

Chat is built-in, read-only conversational reporting. It does not call an external AI service or change transactions. Suggested questions reflect your report permissions.

Supported examples:

- `Total sales today` or `Sales from 2026-09-01 to 2026-09-30`.
- `Sales for customer "Example Customer" this month`.
- `Money collected this week` and `Supplier payments this month`.
- `Customer outstanding balances` and `Supplier outstanding balances`.
- `Purchases for supplier "Example Supplier" in September 2026`.
- `Expenses for category "Transport" this month`.
- `Show low stock` and `Best-selling products this month`.
- `Show total stock balance` or `Show total stock value`.
- `Stock balance for product "Widget"` or `Stock value for product "Widget"`.
- Stock questions also accept unquoted names, for example `Stock value for Stanley Cup 1.5litres`.
- `Stock value for category "General"` and the follow-up `And its value?` after a product balance question.
- `Bank balances`, `Bank deposits today`, or `Bank transactions for account "Main Bank" this week`.
- Follow up with `And last month?`, `Break it down by customer`, or `Compare with last month`.
- `Compare sales in September 2026 with August 2026`.

Use quotes around customer, supplier, product, category, and bank account names. Ambiguous matches prompt you to repeat the question with a more precise name or quoted record ID, such as `customer "#12"` or `product "#12"`. Use the new-conversation button to clear previous filters.

Dates use the application timezone. Weeks start on Monday; this week/month/year means the start of that period through today. Without a date, transaction reports default to today. Current balances and stock are not historical snapshots. Customer and supplier balances cover all invoice dates unless a date range is specified; that range selects invoices, while outstanding amounts remain current.

Sales and purchase totals use recorded invoice totals including recorded taxes, not payment collections. Collections and supplier payments use payment dates, including payments against older invoices. Cancelled and deleted invoices are excluded. Expenses exclude deleted vouchers and items. Bank movements keep transfers and currencies separate; bank balances are recorded app balances, not live bank balances.

Stock balances require `stock-report`; stock valuations require `stock-value-report`, in addition to `chat-use`. Valuations also show quantities but do not require a separate quantity-report permission, matching the existing Stock Value screen. Quantity-only answers do not expose costs or selling values. Quantities are totalled separately by unit, never by combining boxes, pieces, and kilograms. Individual product queries show that product's quantity and unit. Values use current quantity times stored cost price and selling price, matching the existing stock valuation report. Selling value is potential retail value, not realised revenue or profit; missing prices contribute zero. Deleted products are excluded, while zero and negative quantities remain included. Historical stock balances and period comparisons are not supported. Ask `Stock value for all products` to remove a product filter while keeping a category filter, or `Show all stock` to remove both.

Answers include the interpreted period, filters, totals or rows, and a link to the relevant records screen. Record links open existing screens and do not automatically reproduce every chat filter. Up to 50 breakdown rows are displayed; totals include all matching records. Best-selling products show the top 10 by quantity. Successful queries are recorded in the application log. Unsupported requests return clarification instead of a guessed answer.

### 2.5 Profile and Password

Use the account menu to:

- View your profile.
- Edit your profile details.
- Change your password.
- Log out securely.

## 3. Navigation Overview

The left sidebar groups the application into these major sections:

- **Transactions**: Purchases, sales, expenses, stock adjustments, cash reconciliation, and bank transactions.
- **Payments**: Cash and credit payment lists for sales and purchases.
- **Reports**: Transaction, stock, audit, and accounting reports.
- **Accounting Setup**: Chart of accounts, fiscal periods, bank accounts, and capital/loan entries.
- **Setups**: Companies, suppliers, customers, categories, units, expense categories, products, product prices, and system settings.
- **Emails and Notifications**: Daily transaction email sending and email logs.
- **User Setup**: Users, roles, and permissions.

## 4. Recommended Setup Order

Before daily use, an administrator should set up the system in this order:

1. Create company records.
2. Create product categories.
3. Create units of measure.
4. Create expense categories.
5. Create suppliers and customers.
6. Create products.
7. Review product prices and stock thresholds.
8. Configure accounting setup, including chart of accounts and bank accounts.
9. Configure system settings.
10. Create users and assign roles.

## 5. Setup Management

### 5.1 Companies

Use **Setups > Manage Setups > Company** to maintain company records.

Common actions:

- Click **Create** to add a company.
- Use **All Company** to view existing companies.
- Edit company details when business information changes.
- Delete a company only when it is no longer needed and is not required for existing records.

### 5.2 Suppliers

Use **Setups > Manage Setups > Suppliers** to manage vendors and purchase accounts.

Supplier information is used when creating purchases and tracking supplier credit balances.

Common actions:

- Create a supplier with name, mobile number, and email where applicable.
- View all suppliers.
- Edit supplier details.
- Delete suppliers only if they are not needed for historical records.

### 5.3 Customers

Use **Setups > Manage Setups > Customers** to manage customer accounts.

Customer information is used when creating sales and tracking credit sales.

Common actions:

- Create a customer with name, mobile number, and email where applicable.
- View all customers.
- Edit customer details.
- Delete customers only if they are not needed for historical records.

Note: The system also supports quick customer creation during a sale when **Click to Create new customer** is selected.

### 5.4 Categories

Use **Setups > Manage Setups > Category** to group products.

Examples include food items, drinks, stationery, electronics, services, or any category that fits your business.

### 5.5 Units

Use **Setups > Manage Setups > Unit** to define product measurement units.

Examples include pcs, box, carton, kg, litre, pack, bottle, or bag.

### 5.6 Expense Categories

Use **Setups > Manage Setups > Expense Category** to classify expenses.

Examples include rent, utilities, transport, salaries, bank charges, repairs, and office supplies.

### 5.7 Products

Use **Setups > Manage Setups > Product** to manage items sold or purchased by the business.

To create a product:

1. Go to **Product > Create**.
2. Enter the product name.
3. Select the product category.
4. Select the unit.
5. Enter the low stock threshold.
6. Click **Submit**.

Product notes:

- Categories and units must exist before they can be selected.
- Low stock threshold helps identify products that need restocking.
- Products can be imported where the import option is available.
- Products can be exported from the product list where the export option is available.
- Barcode lookup is supported for sales and purchases.
- Barcode printing is available from the products area.

### 5.8 Product Prices

Use **Setups > Manage Setups > Update Price** to view and update product prices.

The system keeps product price history so price changes can be reviewed later under **Reports > Stock Report > Product Price History**.

## 6. Sales

Use **Transactions > Manage Transactions > Sales** to create and manage sales.

### 6.1 Creating a Sale

1. Go to **Sales > Create**.
2. Scan a barcode, use camera scan, or select a product from the product list.
3. Confirm the selected product category, unit, current stock, and price.
4. Click **Add** to add the item to the sale table.
5. Enter quantity for each item.
6. Review the calculated line total.
7. Enter discount if your role and system settings allow manual discount entry.
8. Review taxable subtotal, NHIL, GETFund, VAT, and grand total.
9. Enter cash tendered if applicable.
10. Review change due.
11. Enter a description if needed.
12. Select the paid status:
    - **Full Payment**: Customer pays the full amount.
    - **Partial Payment**: Customer pays part of the amount now and the rest remains outstanding.
    - **Full Credit**: Entire sale is recorded as credit.
13. Select the customer account.
14. If the customer does not exist, select **Click to Create new customer** and enter the customer details.
15. Click **Submit Sale**.

### 6.2 Sales Payment Rules

- Full payment records the sale as paid.
- Partial payment requires a paid amount.
- Full credit records the whole sale as outstanding customer credit.
- Credit balances can be managed from the customer credit list.
- Customer cash and credit payment lists show cashiers only records linked to sales they created, including the Sale Number and Customer filter options. Cashiers cannot open or pay another cashier's credit invoice through a direct URL. Other roles retain their existing access and permission requirements.
- If negative stock is disabled in settings, the system prevents selling more than available stock.
- If backdated invoices are disabled in settings, the sale date is restricted to today's date.

### 6.3 Viewing and Printing Sales

Use **Sales > All Sales** to view sales records.

Sales default to newest invoice date first, with newest record first when dates match. The **Total Sales (GHS)** card above the table defaults to all-time invoice totals. It updates with Sale Number, Customer, date range, Payment Status, Created By, and table search, across all matching pages. Cancelled and deleted sales are excluded from this total. Cashiers see only their own sales in both the table and total; other roles retain their existing sales access. Clearing the filter boxes and table search restores the all-time total. The table footer continues to show the current page's amount.

Available actions may include:

- View sales list.
- Print a sale receipt.
- Print the sales list.
- Cancel a sale, if your role allows it.

### 6.4 Daily Total Sales

Use **Sales > Daily Total Sales** to review sales totals by day.

## 7. Purchases

Use **Transactions > Manage Transactions > Purchase** to create and manage purchases from suppliers.

### 7.1 Creating a Purchase

1. Go to **Purchase > Create**.
2. Scan a barcode, use camera scan, or select a product.
3. Confirm category, unit, current stock, cost price, and selling price.
4. Click **Add** to add the item to the purchase table.
5. Enter quantity, cost price, and selling price where applicable.
6. Enter any discount.
7. Review the grand total.
8. Enter a description if needed.
9. Select the paid status:
    - **Full Payment**: Supplier is paid in full.
    - **Partial Payment**: Supplier is partly paid and the balance remains outstanding.
    - **Full Credit**: Purchase is fully on supplier credit.
10. Select the supplier.
11. If the supplier does not exist, select **Click to Create new supplier** and enter the supplier details.
12. Click **Submit Purchase**.

### 7.2 Purchase Effects

- Purchases increase product stock.
- Cost price and selling price can be captured during purchase entry.
- Credit purchases appear under supplier credit management.
- Purchase editing may be controlled by system settings and a time window.

### 7.3 Viewing and Printing Purchases

Use **Purchase > All Purchase** to view purchase records.

Available actions may include:

- View purchases list.
- Edit a purchase, if enabled and permitted.
- Print a purchase receipt.
- Print the purchase list.
- Cancel a purchase, if your role allows it.

## 8. Payments and Credit Management

Use **Transactions > Manage Payments** to review cash and credit movement.

### 8.1 Cash Sales

Use **Cash Sales** to view sales that were paid in cash or recorded as paid.

### 8.2 Credit Sales List

Use **Credit Sales List** to view customers with outstanding credit sales.

Typical workflow:

1. Open the credit customer list.
2. Select the sale/customer balance to update.
3. Enter payment received.
4. Save the update.
5. Confirm the credit balance is reduced or cleared.

### 8.3 Cash Purchases

Use **Cash Purchases** to view purchases that were paid in cash or recorded as paid.

### 8.4 Credit Purchases List

Use **Credit Purchases List** to view supplier purchases with outstanding balances.

Typical workflow:

1. Open the credit supplier list.
2. Select the purchase/supplier balance to update.
3. Enter payment made.
4. Save the update.
5. Confirm the supplier credit balance is reduced or cleared.

## 9. Expenses

Use **Transactions > Manage Transactions > Expenses** to record business expenses.

To create an expense voucher:

1. Go to **Expenses > Create**.
2. Select the expense category.
3. Enter the expense date, amount, and description.
4. Add all required expense lines.
5. Submit the voucher.

From **All Expense**, users with permission can view, edit, print, or delete expense vouchers.

## 10. Stock Adjustments

Use **Transactions > Manage Transactions > Stock** to adjust inventory outside normal purchase and sales activity.

Common reasons for stock adjustment:

- Damaged goods.
- Lost items.
- Stock count corrections.
- Opening stock correction.
- Internal adjustment after audit.

To create a stock adjustment:

1. Go to **Stock > Create**.
2. Select the product.
3. Enter the adjustment quantity and reason.
4. Review the effect on stock.
5. Submit the adjustment.

From **All Stock Adjustments**, users can view, edit, print, or delete adjustments according to permissions.

## 11. Cash Reconciliation

Cash reconciliation helps compare expected cash against the physical cash counted by a cashier.

Use **Transactions > Manage Transactions > Cash Reconcile**.

### 11.1 Creating a Reconciliation

1. Go to **Cash Reconcile > Create**.
2. Confirm the business date.
3. Enter the opening float.
4. Review expected cash sales and expected cash expenses.
5. Enter the physical counted cash.
6. Add notes if needed.
7. Submit the reconciliation.

The system calculates:

- Expected closing cash.
- Variance amount.
- Variance type: balanced, overage, or shortage.

### 11.2 Approval

Managers, supervisors, or authorized users can approve submitted reconciliations.

Important rules:

- An approved reconciliation is locked.
- To edit an approved reconciliation, an authorized user must reverse approval first.
- The system prevents duplicate reconciliation for the same cashier and business date.
- If the setting is enabled, cashiers who sold yesterday must reconcile yesterday before creating today's sales.

## 12. Bank Transactions

Use **Transactions > Manage Transactions > Bank Transactions** to manage bank-related activity.

Supported transaction types may include:

- Cash deposit to bank.
- Bank withdrawal.
- Transfer between bank accounts.
- Bank fees.
- Interest income.
- Customer receipt adjustment.
- Supplier payment.

Bank transaction notes:

- Bank accounts must be created first.
- Bank accounts should be linked to bank-type general ledger accounts.
- Deposits reduce cash in hand and increase bank balance.
- Withdrawals increase cash in hand and reduce bank balance.
- Deleting a bank deposit reverses the amount back to cash in hand.

## 13. Accounting Setup

### 13.1 Chart of Accounts

Use **Accounting Setup > Chart of Accounts** to manage accounting accounts.

Each account includes:

- Account code.
- Account name.
- Account type: asset, liability, equity, income, or expense.
- Bank account flag.
- Active/inactive status.

Notes:

- Account codes must be unique.
- Only active accounts should be used for new entries.
- Accounts marked as bank accounts can be linked to bank records.

### 13.2 Fiscal Periods

Use **Accounting Setup > Fiscal Periods** to review and close fiscal periods.

Important rule:

- Closed fiscal periods should not be used for further normal transaction changes unless an administrator reopens or corrects records according to company policy.

### 13.3 Bank Accounts

Use **Accounting Setup > Bank Accounts** to create and maintain business bank accounts.

Each bank account includes:

- Account name.
- Bank name.
- Account number.
- Currency.
- Opening balance.
- Linked GL account.
- Active/inactive status.

Bank accounts with related transactions or capital/loan entries cannot be deleted.

### 13.4 Capital and Loans

Use **Accounting Setup > Capital & Loans** to record funds introduced into the business.

Supported entry types:

- Owner capital.
- Bank loan.
- Individual loan.

Funds can be received into:

- Cash.
- Bank account.

For loan entries, lender/source information is required. Capital and loan entries post accounting journals and update the selected bank balance when received into bank.

## 14. Reports

Reports are available according to role permissions.

### 14.1 Transaction Reports

Use **Reports > Transactions Report**.

Available reports include:

- **Sales Lines Report**: Detailed sales line items.
- **Purchase Lines Report**: Detailed purchase line items.
- **Sales Cash Movement Report**: Cash movement from sales.
- **Purchase Cash Movement Report**: Cash movement from purchases.
- **Sales Credit Movement Report**: Customer credit movement from sales.
- **Purchase Credit Movement Report**: Supplier credit movement from purchases.
- **Profit Report**: Profit summary and related performance information.
- **Summary Ledger**: Summary view of transaction balances.
- **Expense Report**: Expense activity by date/category.

Many reports support filters such as date range, customer, supplier, product, category, user, or summary view depending on the report.

### 14.2 Stock Reports

Use **Reports > Stock Report**.

Available reports include:

- **Product-wise Stock Trail**: Product movement history.
- **Summary Stock Report**: Current stock summary by product/category/unit.
- **Stock Value Report**: Inventory value by product.
- **Stock Adjustment Report**: Stock changes entered through adjustments.
- **Product Price History**: History of price changes.

### 14.3 Audit Reports

Use **Reports > Audit Report**.

Available reports include:

- **Purchase Edit Trail**: Purchase change history.
- **Stock Movement Trail**: Stock movement audit trail.

### 14.4 Accounting Reports

Use **Reports > Accounting Reports**.

Available reports include:

- **Trial Balance**: Debit and credit account balances.
- **General Ledger**: Account-level transaction history.
- **Income Statement**: Income and expense performance.
- **Balance Sheet**: Assets, liabilities, and equity position.
- **Cash Flow**: Cash movement summary.
- **A/R Aging**: Customer receivables aging.
- **A/P Aging**: Supplier payables aging.

The balance sheet can also be exported or printed as PDF where available.

## 15. Daily Transaction Email

Use **Emails and Notifications > Email Automation**.

### 15.1 Send Daily Email

Use **Send Daily Email** to manually send the daily transaction report.

The daily email may include sales, expenses, and cash shortage/variance summary depending on system configuration.

### 15.2 Daily Email Report

Use **Daily Email Report** to review email logs and confirm whether reports were sent successfully.

### 15.3 Automatic Daily Email

Automatic daily email sending is controlled from system settings. When enabled, the administrator selects the send time between evening hours configured by the system.

## 16. System Settings

Use **Setups > Manage Setups > Settings** to configure system behavior.

Available settings include:

- **Session Lifetime (minutes)**: Logs users out after a selected period of inactivity.
- **Expire Session When Browser Closes**: Ends login when the browser closes.
- **Require Yesterday Reconciliation Before Today's Sales**: Prevents applicable cashiers from selling today until yesterday is reconciled.
- **Allow Negative Stock (Overselling)**: Allows sales even when available stock is insufficient.
- **Prevent Backdated Invoices**: Restricts invoice dates to today.
- **Allow Manual Discount Entry by Staff**: Controls whether staff can type discounts during checkout.
- **Require Password Change on First Login**: Forces selected users to change password before using the system.
- **Enable Daily Transaction Email Report**: Enables automatic or manual daily reporting email workflow.
- **Daily Email Report Send Time**: Sets the automatic email send time when daily reports are enabled.
- **Enable Purchase Editing**: Allows purchase editing for authorized users.
- **Purchase Edit Window (Hours)**: Sets how long posted purchases remain editable, from 24 to 720 hours.

Click **Save Settings** after making changes.

## 17. User and Role Management

Use **User Setup > Manage Users**.

### 17.1 Users

Use **Users** to create and maintain login accounts.

Common actions:

- Create a user.
- View all users.
- View a user's details.
- Edit a user.
- Delete a user if no longer required.
- Assign role(s) to control access.

### 17.2 Roles

Use **Roles** to manage role-based permissions.

Roles determine which menus and actions each user can access. Examples of permissions include list, create, edit, delete, show, approve, report access, settings management, and user management.

Recommended practice:

- Give cashiers only the permissions needed for sales, receipts, and reconciliation.
- Give supervisors approval and reporting permissions.
- Give accountants accounting setup and accounting reports permissions.
- Restrict settings, roles, and users to administrators.

## 18. Common Daily Workflows

### 18.1 Cashier Daily Sales Workflow

1. Log in.
2. Confirm yesterday's cash reconciliation is complete if required by settings.
3. Go to **Sales > Create**.
4. Scan/select items and submit sales.
5. Print receipts when needed.
6. At end of day, go to **Cash Reconcile > Create**.
7. Enter counted cash and submit reconciliation.
8. Log out.

### 18.2 Purchasing Workflow

1. Create supplier if needed.
2. Create product if needed.
3. Go to **Purchase > Create**.
4. Add purchased items.
5. Select payment status and supplier.
6. Submit purchase.
7. Print purchase receipt if required.
8. Review supplier credit if purchase was partial or full credit.

### 18.3 Inventory Control Workflow

1. Review **Summary Stock Report**.
2. Check products below low stock threshold.
3. Create purchases for restocking.
4. Use stock adjustments only for corrections, damage, loss, or stock count differences.
5. Review stock movement trails for audit.

### 18.4 End-of-Day Management Workflow

1. Review daily sales.
2. Review expenses.
3. Review cash reconciliations.
4. Approve reconciliations where appropriate.
5. Send daily transaction email manually if automatic sending is not enabled.
6. Review reports for exceptions, shortages, credit balances, or unusual stock movement.

## 19. Printing, Exporting, and Receipts

Depending on permissions and page, the system supports:

- Printing individual sale receipts.
- Printing individual purchase receipts.
- Printing sales and purchase lists.
- Printing stock adjustment records.
- Printing expense vouchers.
- Printing or exporting accounting reports such as balance sheet PDF.
- Exporting product data where available.

## 20. Good Data Entry Practices

- Always select the correct customer or supplier before submitting credit transactions.
- Use cash customer/supplier accounts only for true cash transactions.
- Confirm quantity and price before submitting sales or purchases.
- Avoid deleting setup records that may be needed for history.
- Use stock adjustments only for non-sale and non-purchase stock changes.
- Reconcile cash daily.
- Keep user permissions aligned with job responsibilities.
- Review reports regularly to identify credit balances, shortages, stock issues, and profit trends.

## 21. Troubleshooting

### Menu Item Is Missing

Your role may not have permission for that menu. Contact an administrator.

### Cannot Submit a Sale

Possible causes:

- Required customer or payment status is missing.
- Stock is insufficient and negative stock is disabled.
- Backdated invoices are disabled and an old date was selected.
- Yesterday's reconciliation is required before today's sales.

### Cannot Enter a Discount

Manual discount entry may be disabled in system settings.

### Cannot Edit a Purchase

Purchase editing may be disabled, outside the allowed edit window, or unavailable to your role.

### Cannot Delete a Bank Account

Bank accounts with related bank transactions or capital/loan entries cannot be deleted.

### Cash Reconciliation Is Locked

Approved reconciliations are locked. An authorized user must reverse approval before editing.

### Daily Email Was Not Sent

Check:

- Daily email setting is enabled.
- Send time is configured.
- Email setup is correct.
- Daily email logs for success or failure details.

## 22. Administrator Checklist

Use this checklist when preparing the system for a client or new business location:

- Company details are created.
- Categories and units are created.
- Expense categories are created.
- Customers and suppliers are created or imported.
- Products are created or imported.
- Product prices and low stock thresholds are reviewed.
- Accounting chart of accounts is reviewed.
- Bank accounts are linked to GL accounts.
- Capital and loan opening entries are recorded if applicable.
- System settings are configured.
- Daily email settings are configured if required.
- Users are created.
- Roles and permissions are assigned.
- Test sale, purchase, expense, stock adjustment, and reconciliation are completed before go-live.
