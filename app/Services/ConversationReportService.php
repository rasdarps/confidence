<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Category;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\ExpenseVoucherItem;
use App\Models\Outlet;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use App\Models\User;
use App\Support\PaymentStatus;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ConversationReportService
{
    public const PERMISSIONS = [
        'sales' => 'sale-list',
        'purchases' => 'purchase-list',
        'supplier_payments' => 'report-purchase-cash-movement',
        'collections' => 'report-sales-cash-movement',
        'customer_balance' => 'payment-sales-credit-list',
        'supplier_balance' => 'payment-purchases-credit-list',
        'low_stock' => 'stock-report',
        'stock_balance' => 'stock-report',
        'stock_value' => 'stock-value-report',
        'top_products' => 'report-sales-lines',
        'expenses' => 'expense-list',
        'bank_balances' => 'bank-account-list',
        'bank_transactions' => 'bank-transaction-list',
    ];

    public function answer(User $user, array $report, ?int $requestOutletId = null): array
    {
        $intent = $report['intent'];
        abort_unless(isset(self::PERMISSIONS[$intent]) && $user->can(self::PERMISSIONS[$intent]), 403, 'You do not have permission for this report.');

        // Determine multi-outlet scope
        $canFilterOutlets = $user->isCentral() || $user->can('dashboard-outlet-filter') || $user->hasRole('Super Admin');
        $assignedOutletId = $user->assignedOutletId() ?? ($user->outlet_id ? (int) $user->outlet_id : null);

        if (!$canFilterOutlets && $assignedOutletId) {
            if (!empty($report['outlet'])) {
                $requestedId = $this->entity(Outlet::class, $report['outlet']);
                if ($requestedId !== $assignedOutletId) {
                    $branchName = $user->outlet ? $user->outlet->name : 'your assigned branch';
                    throw new InvalidArgumentException("You are scoped to {$branchName}. You cannot access reports for other outlets.");
                }
            }
            $effectiveOutletId = $assignedOutletId;
        } else {
            if (!empty($report['outlet'])) {
                $effectiveOutletId = $this->entity(Outlet::class, $report['outlet']);
            } elseif ($requestOutletId) {
                $effectiveOutletId = (int) $requestOutletId;
            } else {
                $effectiveOutletId = null;
            }
        }

        $outletRecord = $effectiveOutletId ? Outlet::find($effectiveOutletId) : null;
        if ($effectiveOutletId && !$outletRecord) {
            throw new InvalidArgumentException('The specified outlet was not found.');
        }

        if ($outletRecord) {
            $report['location'] = $outletRecord->code ? "{$outletRecord->name} ({$outletRecord->code})" : $outletRecord->name;
            $report['outlet'] = $outletRecord->name;
        } else {
            $report['location'] = 'All Outlets & Central HQ (Consolidated)';
            unset($report['outlet']);
        }

        $allowedFilters = match ($intent) {
            'sales', 'collections', 'customer_balance' => ['customer', 'outlet'],
            'purchases', 'supplier_balance', 'supplier_payments' => ['supplier', 'outlet'],
            'expenses' => ['category', 'outlet'],
            'bank_balances', 'bank_transactions' => ['account', 'outlet'],
            'stock_balance', 'stock_value' => ['product', 'category', 'outlet'],
            'low_stock', 'top_products' => ['outlet'],
            default => [],
        };
        foreach (['customer', 'supplier', 'category', 'account', 'product', 'outlet'] as $filter) {
            if (!empty($report[$filter]) && !in_array($filter, $allowedFilters, true)) {
                throw new InvalidArgumentException('That filter is not supported for this report. Start a new question with the report and date range.');
            }
        }
        $allowedGroup = match ($intent) {
            'sales', 'collections', 'customer_balance' => 'customer',
            'purchases', 'supplier_balance', 'supplier_payments' => 'supplier',
            'expenses' => 'category',
            default => null,
        };
        if (!empty($report['group']) && $report['group'] !== $allowedGroup) {
            throw new InvalidArgumentException('That breakdown is not supported for this report.');
        }
        if (!empty($report['payment_status']) && !in_array($intent, ['sales', 'purchases', 'customer_balance', 'supplier_balance'], true)) {
            throw new InvalidArgumentException('Credit status filters apply to invoices and outstanding balances, not this report.');
        }
        if (!empty($report['customer']) || ($report['group'] ?? null) === 'customer') {
            abort_unless($user->can('customer-list'), 403);
        }
        if (!empty($report['supplier']) || ($report['group'] ?? null) === 'supplier') {
            abort_unless($user->can('supplier-list'), 403);
        }
        if (in_array($intent, ['low_stock', 'stock_balance', 'stock_value', 'bank_balances', 'customer_balance', 'supplier_balance'], true) && !empty($report['compare'])) {
            throw new InvalidArgumentException('This report shows current balances or stock, not historical snapshots.');
        }
        if (in_array($intent, ['bank_balances', 'low_stock', 'stock_balance', 'stock_value'], true) && !empty($report['date_requested']) && ($report['from'] !== now()->toDateString() || $report['to'] !== now()->toDateString())) {
            throw new InvalidArgumentException('This report shows current recorded balances or stock. Historical snapshots are not available.');
        }
        $result = $this->run($report, $effectiveOutletId);
        if ($effectiveOutletId) {
            $result['notes'][] = "Scoped strictly to location: {$report['location']}.";
        } else {
            $result['notes'][] = 'Consolidated enterprise data across all outlets and central warehouse.';
        }
        if (!empty($report['compare'])) {
            $previous = $report;
            [$previous['from'], $previous['to']] = $report['comparison'];
            $comparison = $this->run($previous, $effectiveOutletId);
            $result['comparison'] = [
                'period' => $this->period($previous),
                'metrics' => $comparison['metrics'],
                'columns' => $comparison['columns'],
                'rows' => $comparison['rows'],
            ];
            $result['notes'][] = 'Comparison uses the stated periods; a current partial period may be shorter than the previous full period.';
        }
        return $result;
    }

    private function run(array $report, ?int $outletId = null): array
    {
        return match ($report['intent']) {
            'sales', 'purchases', 'customer_balance', 'supplier_balance' => $this->invoices($report, $outletId),
            'collections', 'supplier_payments' => $this->collections($report, $outletId),
            'expenses' => $this->expenses($report, $outletId),
            'low_stock', 'top_products' => $this->stock($report, $outletId),
            'stock_balance', 'stock_value' => $this->stockBalance($report, $outletId),
            'bank_balances', 'bank_transactions' => $this->banking($report, $outletId),
        };
    }

    private function invoices(array $report, ?int $outletId = null): array
    {
        $purchase = in_array($report['intent'], ['purchases', 'supplier_balance'], true);
        $balance = in_array($report['intent'], ['customer_balance', 'supplier_balance'], true);
        $entity = $purchase ? 'supplier' : 'customer';
        $table = $purchase ? 'purchase_payments' : 'payments';
        $parent = $purchase ? 'purchases' : 'sales';
        $foreign = $purchase ? 'purchase_id' : 'sale_id';
        $query = ($purchase ? PurchasePayment::query() : Payment::query())
            ->join($parent, $parent.'.id', '=', $table.'.'.$foreign)
            ->whereNull($parent.'.deleted_at')->whereNull($parent.'.cancelled_at');
        if (!$purchase && $outletId) {
            $query->where($parent.'.outlet_id', $outletId);
        }
        if (!$balance) {
            $this->range($query, $parent.'.date', $report);
        } elseif (!empty($report['date_requested'])) {
            $this->range($query, $parent.'.date', $report);
        }
        $entityId = $this->entity($purchase ? Supplier::class : Customer::class, $report[$entity] ?? null);
        if ($entityId) {
            $query->where($table.'.'.$entity.'_id', $entityId);
        }
        if ($balance) {
            $query->where($table.'.due_amount', '>', 0);
        }
        if (($report['payment_status'] ?? null) === 'credit') {
            $query->whereIn($table.'.paid_status', [PaymentStatus::FULL_CREDIT, PaymentStatus::PARTIAL_PAYMENT]);
        }
        $metrics = $balance ? ['Current outstanding' => round((float) (clone $query)->sum($table.'.due_amount'), 2)] : [
            'Invoice total (including recorded taxes)' => round((float) (clone $query)->sum($table.'.total_amount'), 2),
            'Invoice count' => (clone $query)->count(),
        ];
        $rows = [];
        if ($balance || ($report['group'] ?? null) === $entity) {
            $amount = $balance ? 'due_amount' : 'total_amount';
            $rows = (clone $query)->leftJoin($entity.'s', $entity.'s.id', '=', $table.'.'.$entity.'_id')
                ->selectRaw($table.'.'.$entity.'_id as entity_id, '.$entity.'s.name as name, SUM('.$table.'.'.$amount.') as amount')
                ->groupBy($table.'.'.$entity.'_id', $entity.'s.name')->orderByDesc('amount')->limit(50)->get()
                ->map(fn ($row) => [$row->name ?? 'Unassigned', number_format((float) $row->amount, 2)])->all();
        }
        $notes = ['Cancelled and deleted invoices are excluded.'];
        if ($purchase && $outletId) {
            $notes[] = 'Purchases and supplier payables are managed centrally at Central HQ.';
        }
        if ($balance) {
            $notes[] = 'Outstanding amounts are current, not historical balances. A date range filters invoice dates, not the date the debt was outstanding.';
        } else {
            $notes[] = 'Filtered by invoice date. Invoice value is not money collected or paid.';
        }
        if (($report['payment_status'] ?? null) === 'credit') {
            $notes[] = 'Credit includes partially paid and full-credit invoices using their current payment status.';
        }
        $route = $balance ? ($purchase ? 'credit.supplier' : 'credit.customer') : ($purchase ? 'purchases.index' : 'sales.index');
        return $this->result($report, $balance ? ucfirst($entity).' balances' : ucfirst($parent), $metrics, [ucfirst($entity), $balance ? 'Outstanding' : 'Invoice total'], $rows, $notes, $route, $balance && empty($report['date_requested']) ? 'Current, all invoice dates' : null, $outletId);
    }

    private function collections(array $report, ?int $outletId = null): array
    {
        $purchase = $report['intent'] === 'supplier_payments';
        $details = $purchase ? 'purchase_payment_details' : 'payment_details';
        $payments = $purchase ? 'purchase_payments' : 'payments';
        $parent = $purchase ? 'purchases' : 'sales';
        $foreign = $purchase ? 'purchase_id' : 'sale_id';
        $entity = $purchase ? 'supplier' : 'customer';
        $query = DB::table($details)->join($payments, $payments.'.'.$foreign, '=', $details.'.'.$foreign)
            ->join($parent, $parent.'.id', '=', $details.'.'.$foreign)->whereNull($parent.'.deleted_at')->whereNull($parent.'.cancelled_at');
        $this->range($query, $details.'.date', $report);
        if (!$purchase && $outletId) {
            $query->where($parent.'.outlet_id', $outletId);
        }
        if ($id = $this->entity($purchase ? Supplier::class : Customer::class, $report[$entity] ?? null)) {
            $query->where($payments.'.'.$entity.'_id', $id);
        }
        if (($report['payment_status'] ?? null) === 'credit') {
            throw new InvalidArgumentException('Collections are payment-date movements. Ask for credit sales or customer balances separately.');
        }
        $total = round((float) (clone $query)->sum($details.'.current_paid_amount'), 2);
        $rows = [];
        if (($report['group'] ?? null) === $entity) {
            $rows = (clone $query)->leftJoin($entity.'s', $entity.'s.id', '=', $payments.'.'.$entity.'_id')
                ->selectRaw($payments.'.'.$entity.'_id, '.$entity.'s.name, SUM('.$details.'.current_paid_amount) as amount')
                ->groupBy($payments.'.'.$entity.'_id', $entity.'s.name')->orderByDesc('amount')->limit(50)->get()
                ->map(fn ($row) => [$row->name ?? 'Unassigned', number_format((float) $row->amount, 2)])->all();
        }
        $notes = ['Filtered by payment date, including payments against older invoices. Cancelled and deleted invoices are excluded.'];
        if ($purchase && $outletId) {
            $notes[] = 'Supplier payments are managed centrally at Central HQ.';
        }
        return $this->result($report, $purchase ? 'Supplier payments' : 'Sales collections', [$purchase ? 'Money paid' : 'Money collected' => $total], [ucfirst($entity), $purchase ? 'Paid' : 'Collected'], $rows,
            $notes, $purchase ? 'reports.purchase-cash-movement' : 'reports.sales-cash-movement', null, $outletId);
    }

    private function expenses(array $report, ?int $outletId = null): array
    {
        $query = ExpenseVoucherItem::query()->join('expense_vouchers', 'expense_vouchers.id', '=', 'expense_voucher_items.expense_voucher_id')
            ->whereNull('expense_vouchers.deleted_at');
        $this->range($query, 'expense_vouchers.voucher_date', $report);
        if ($outletId) {
            $query->where('expense_vouchers.outlet_id', $outletId);
        }
        if ($id = $this->entity(ExpenseCategory::class, $report['category'] ?? null)) {
            $query->where('expense_voucher_items.expense_category_id', $id);
        }
        $total = round((float) (clone $query)->sum('expense_voucher_items.amount'), 2);
        $rows = (clone $query)->leftJoin('expense_categories', 'expense_categories.id', '=', 'expense_voucher_items.expense_category_id')
            ->selectRaw('expense_voucher_items.expense_category_id, expense_categories.name, SUM(expense_voucher_items.amount) as amount')
            ->groupBy('expense_voucher_items.expense_category_id', 'expense_categories.name')->orderByDesc('amount')->limit(50)->get()
            ->map(fn ($row) => [$row->name ?? 'Unassigned', number_format((float) $row->amount, 2)])->all();
        return $this->result($report, 'Expenses', ['Expense total' => $total], ['Category', 'Amount'], $rows,
            ['Filtered by voucher date. Deleted vouchers and items are excluded.'], 'expense-vouchers.index', null, $outletId);
    }

    private function stock(array $report, ?int $outletId = null): array
    {
        if ($report['intent'] === 'low_stock') {
            $query = Product::with(['unit', 'subUnit', 'outletStocks' => function ($q) use ($outletId) {
                if ($outletId) $q->where('outlet_id', $outletId);
            }])->lowStock($outletId);

            $count = (clone $query)->count();
            $rows = $query->orderBy('name')->limit(50)->get(['id', 'name', 'unit_id', 'has_sub_unit', 'sub_unit_id', 'pieces_per_pack'])
                ->map(function ($row) use ($outletId) {
                    $stock = $outletId ? $row->stockForOutlet($outletId) : null;
                    $qty = $outletId ? ($stock ? (float) $stock->quantity : 0) : (float) $row->quantity;
                    $threshold = $outletId ? ($stock ? (float) $stock->stock_level : 0) : (float) $row->stock_level;

                    $breakdown = $row->formatQuantity($qty);
                    if ($row->has_sub_unit && ($row->pieces_per_pack ?: 1) > 1) {
                        $subName = $row->subUnit?->name ?? 'pcs';
                        $breakdown .= " ({$qty} {$subName})";
                    }

                    $threshBreakdown = $row->formatQuantity($threshold);

                    return [$row->name, $breakdown, $threshBreakdown];
                })->all();
            return $this->result($report, 'Low stock', ['Products at or below threshold' => $count], ['Product', 'Current Stock (Bags/Boxes & Pieces)', 'Threshold'], $rows,
                ['Current stock, including out-of-stock products; not a historical snapshot. Quantities display wholesale packs (Bags/Boxes) and loose pieces.'], 'stock.report', 'Current stock', $outletId);
        }

        $query = DB::table('sale_details')->join('sales', 'sales.id', '=', 'sale_details.sale_id')
            ->join('products', 'products.id', '=', 'sale_details.product_id')->whereNull('sales.deleted_at')->whereNull('sales.cancelled_at')->whereNull('products.deleted_at');
        $this->range($query, 'sales.date', $report);
        if ($outletId) {
            $query->where('sales.outlet_id', $outletId);
        }
        $rows = $query->selectRaw('products.id, products.name, SUM(sale_details.selling_qty) as quantity')
            ->groupBy('products.id', 'products.name')->orderByDesc('quantity')->limit(10)->get()
            ->map(function ($row) {
                $product = Product::with(['unit', 'subUnit'])->find($row->id);
                $breakdown = $product ? $product->formatQuantity($row->quantity) : (float) $row->quantity;
                $totalPieces = (float) $row->quantity;
                return [$row->name, $breakdown, $totalPieces];
            })->all();
        return $this->result($report, 'Best-selling products', [], ['Product', 'Sales Breakdown (Packs & Pieces)', 'Total Pieces Sold'], $rows,
            ['Top 10 by quantity sold, not revenue. Formatted into wholesale packs and loose pieces. Cancelled and deleted sales are excluded.'], 'reports.sales-lines', null, $outletId);
    }

    private function stockBalance(array $report, ?int $outletId = null): array
    {
        $value = $report['intent'] === 'stock_value';
        $query = Product::query()->with(['unit', 'subUnit']);
        if ($id = $this->entity(Product::class, $report['product'] ?? null)) {
            $query->where('products.id', $id);
        }
        if ($id = $this->entity(Category::class, $report['category'] ?? null)) {
            $query->where('products.category_id', $id);
        }

        $metrics = ['Product count' => (clone $query)->count()];

        $totalsQuery = (clone $query)->leftJoin('outlet_stocks', function ($join) use ($outletId) {
            $join->on('outlet_stocks.product_id', '=', 'products.id');
            if ($outletId) {
                $join->where('outlet_stocks.outlet_id', '=', $outletId);
            }
        });

        $totalPieces = (float) (clone $totalsQuery)->toBase()->selectRaw('COALESCE(SUM(outlet_stocks.quantity), 0) as total_qty')->value('total_qty');
        $metrics['Total loose pieces'] = round($totalPieces, 0);

        $columns = ['Product', 'Packaging Type', 'Stock Breakdown (Bags/Boxes & Pieces)', 'Total Pieces'];
        $notes = [
            'Current recorded stock, not a historical snapshot. Deleted products are excluded.',
            'Stock is displayed with full wholesale packs (Bags/Boxes) and loose pieces breakdown according to the pieces-per-pack conversion ratio.',
        ];

        if ($value) {
            $valueCostSql = 'COALESCE(SUM(outlet_stocks.quantity * (CASE WHEN products.has_sub_unit = 1 AND products.pieces_per_pack > 1 THEN COALESCE(products.sub_unit_cost_price, products.cost_price / products.pieces_per_pack, 0) ELSE COALESCE(products.cost_price, 0) END)), 0)';
            $valueSellSql = 'COALESCE(SUM(outlet_stocks.quantity * (CASE WHEN products.has_sub_unit = 1 AND products.pieces_per_pack > 1 THEN COALESCE(products.sub_unit_selling_price, products.selling_price / products.pieces_per_pack, 0) ELSE COALESCE(products.selling_price, 0) END)), 0)';

            $metrics['Stock value at cost'] = round((float) (clone $totalsQuery)->toBase()->selectRaw($valueCostSql . ' as value')->value('value'), 2);
            $metrics['Stock value at selling price'] = round((float) (clone $totalsQuery)->toBase()->selectRaw($valueSellSql . ' as value')->value('value'), 2);

            $columns = array_merge($columns, ['Cost value (₵)', 'Selling value (₵)']);
            $notes[] = 'Stock valuation calculates unit cost and selling price per loose piece when products have sub-units (pieces/pack), matching the Stock Value report.';
        }

        $rows = $query->with(['unit', 'subUnit', 'outletStocks' => function ($q) use ($outletId) {
            if ($outletId) $q->where('outlet_id', $outletId);
        }])->orderBy('products.name')->limit(50)->get()->map(function ($product) use ($value, $outletId) {
            $qty = $outletId ? $product->quantityForOutlet($outletId) : (float) $product->quantity;

            // Packaging description
            if ($product->has_sub_unit && ($product->pieces_per_pack ?: 1) > 1) {
                $packName = $product->unit?->name ?? 'Pack';
                $subName = $product->subUnit?->name ?? 'Piece';
                $packagingType = "{$packName} ({$product->pieces_per_pack} {$subName}/{$packName})";
            } else {
                $packagingType = $product->unit?->name ?? 'Piece';
            }

            // Dual packaging breakdown
            $breakdown = $product->formatQuantity($qty);
            $totalPieces = (float) $qty == (int) $qty ? (int) $qty : number_format($qty, 2);

            $row = [
                $product->name,
                $packagingType,
                $breakdown,
                $totalPieces . ' ' . ($product->subUnit?->name ?? $product->unit?->name ?? 'pcs'),
            ];

            if ($value) {
                $unitCost = ($product->has_sub_unit && ($product->pieces_per_pack ?: 1) > 1)
                    ? (float) ($product->sub_unit_cost_price ?: ($product->cost_price / $product->pieces_per_pack))
                    : (float) $product->cost_price;

                $unitSell = ($product->has_sub_unit && ($product->pieces_per_pack ?: 1) > 1)
                    ? (float) ($product->sub_unit_selling_price ?: ($product->selling_price / $product->pieces_per_pack))
                    : (float) $product->selling_price;

                $row[] = number_format($qty * $unitCost, 2);
                $row[] = number_format($qty * $unitSell, 2);
            }

            return $row;
        })->all();

        return $this->result(
            $report,
            $value ? 'Current stock value and balances' : 'Current stock balances',
            $metrics,
            $columns,
            $rows,
            $notes,
            $value ? 'stock.stock_value' : 'stock.report',
            'Current stock',
            $outletId
        );
    }

    private function banking(array $report, ?int $outletId = null): array
    {
        $id = $this->entity(BankAccount::class, $report['account'] ?? null);
        $notes = [];
        if ($outletId) {
            $notes[] = 'Bank accounts and treasury transactions are managed centrally at Enterprise level.';
        }
        if ($report['intent'] === 'bank_balances') {
            $query = BankAccount::query();
            if ($id) $query->whereKey($id);
            $rows = $query->orderBy('name')->limit(50)->get(['name', 'currency', 'current_balance'])
                ->map(fn ($row) => [$row->name, $row->currency, number_format((float) $row->current_balance, 2)])->all();
            $notes[] = 'Current balances recorded in this app, not live bank balances. Accounts with different currencies are not combined.';
            return $this->result($report, 'Recorded bank balances', [], ['Account', 'Currency', 'Balance'], $rows,
                $notes, 'accounting.setup.bank-accounts.index', 'Current recorded balances', $outletId);
        }
        $query = BankTransaction::query()->join('bank_accounts', 'bank_accounts.id', '=', 'bank_transactions.bank_account_id');
        $this->range($query, 'bank_transactions.transaction_date', $report);
        if ($id) $query->where('bank_transactions.bank_account_id', $id);
        if (!empty($report['bank_type'])) $query->whereIn('bank_transactions.type', $report['bank_type']);
        $rows = $query->selectRaw('bank_accounts.currency, bank_transactions.type, SUM(bank_transactions.amount) as amount, COUNT(*) as count')
            ->groupBy('bank_accounts.currency', 'bank_transactions.type')->orderBy('bank_accounts.currency')->orderBy('bank_transactions.type')->limit(50)->get()
            ->map(fn ($row) => [$row->currency, ucfirst(str_replace('_', ' ', $row->type)), number_format((float) $row->amount, 2), $row->count])->all();
        $notes[] = 'Filtered by transaction date. Transfers are shown separately, not treated as business income or expense. Amounts are grouped by currency and movement type.';
        return $this->result($report, 'Bank transaction movements', [], ['Currency', 'Type', 'Amount', 'Transactions'], $rows,
            $notes, 'accounting.setup.bank-transactions.index', null, $outletId);
    }

    private function entity(string $model, ?string $name): ?int
    {
        if (!$name) return null;
        if (preg_match('/^#(\d+)$/', $name, $matches)) {
            $record = $model::query()->find((int) $matches[1]);
            if (!$record) throw new InvalidArgumentException('That record was not found.');
            return $record->id;
        }
        if ($model === Outlet::class) {
            $codeMatch = Outlet::query()->where('code', $name)->first();
            if ($codeMatch) return $codeMatch->id;
        }
        $matches = $model::query()->where('name', $name)->limit(2)->get(['id', 'name']);
        if ($matches->count() === 1) return $matches->first()->id;
        if ($matches->isEmpty()) {
            $escaped = str_replace(['\\\\', '%', '_'], ['\\\\\\\\', '\\\\%', '\\\\_'], $name);
            $matches = $model::query()->where('name', 'like', '%'.$escaped.'%')->limit(6)->get(['id', 'name']);
        }
        if ($matches->count() === 1) return $matches->first()->id;
        if ($matches->isEmpty()) throw new InvalidArgumentException('No matching record was found. Use its exact name in quotes.');
        throw new InvalidArgumentException('More than one record matches: '.$matches->map(fn ($row) => $row->name.' (#'.$row->id.')')->implode(', ').'. Repeat the question using the record ID in quotes, for example customer "#12".');
    }

    private function range($query, string $column, array $report): void
    {
        if (!empty($report['from'])) $query->whereDate($column, '>=', $report['from']);
        if (!empty($report['to'])) $query->whereDate($column, '<=', $report['to']);
    }

    private function period(array $report): string
    {
        return empty($report['from']) ? 'All time' : $report['from'].' to '.$report['to'];
    }

    private function result(array $report, string $title, array $metrics, array $columns, array $rows, array $notes, string $route, ?string $period = null, ?int $outletId = null): array
    {
        if (count($rows) === 50) $notes[] = 'Showing up to 50 rows. Totals include all matching records.';
        $filters = array_intersect_key($report, array_flip(['location', 'customer', 'supplier', 'category', 'account', 'product', 'payment_status', 'bank_type']));
        $routeParams = [];
        if ($outletId && in_array($route, ['sales.index', 'reports.sales-cash-movement', 'reports.sales-lines', 'stock.report', 'expense-vouchers.index'], true)) {
            $routeParams['outlet_id'] = $outletId;
        }
        return [
            'title' => $title,
            'period' => $period ?? $this->period($report),
            'filters' => $filters,
            'metrics' => $metrics,
            'columns' => $columns,
            'rows' => $rows,
            'notes' => $notes,
            'source' => ['url' => route($route, $routeParams), 'label' => 'Open records'],
        ];
    }
}
