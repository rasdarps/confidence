<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('accounting_journals')) {
            return;
        }

        if (!Schema::hasColumn('accounting_journals', 'outlet_id')) {
            Schema::table('accounting_journals', function (Blueprint $table) {
                $table->unsignedBigInteger('outlet_id')->nullable()->after('id');
                $table->foreign('outlet_id')->references('id')->on('outlets')->nullOnDelete();
                $table->index(['outlet_id', 'posting_date', 'status'], 'acc_journals_outlet_date_status_idx');
            });
        }

        $centralOutletId = (int) (DB::table('outlets')->where('is_central', true)->value('id')
            ?? DB::table('outlets')->orderBy('id')->value('id')
            ?? 0);

        if (DB::getDriverName() === 'mysql') {
            if (Schema::hasTable('sales')) {
                DB::statement("
                    UPDATE accounting_journals j
                    INNER JOIN sales s ON s.id = j.source_id
                    SET j.outlet_id = s.outlet_id
                    WHERE j.outlet_id IS NULL
                      AND j.source_type IN ('sale', 'sale_cancel', 'sale_receipt')
                      AND s.outlet_id IS NOT NULL
                ");
            }

            if (Schema::hasTable('expense_vouchers')) {
                DB::statement("
                    UPDATE accounting_journals j
                    INNER JOIN expense_vouchers ev ON ev.id = j.source_id
                    SET j.outlet_id = ev.outlet_id
                    WHERE j.outlet_id IS NULL
                      AND j.source_type IN ('expense_voucher', 'expense_voucher_reversal')
                      AND ev.outlet_id IS NOT NULL
                ");
            }

            if (Schema::hasTable('stock_adjustments')) {
                DB::statement("
                    UPDATE accounting_journals j
                    INNER JOIN stock_adjustments sa ON sa.id = j.source_id
                    SET j.outlet_id = sa.outlet_id
                    WHERE j.outlet_id IS NULL
                      AND j.source_type IN ('stock_adjustment', 'stock_adjustment_reversal')
                      AND sa.outlet_id IS NOT NULL
                ");
            }

            if (Schema::hasTable('cash_reconciliations')) {
                DB::statement("
                    UPDATE accounting_journals j
                    INNER JOIN cash_reconciliations cr ON cr.id = j.source_id
                    SET j.outlet_id = cr.outlet_id
                    WHERE j.outlet_id IS NULL
                      AND j.source_type IN ('cash_reconciliation', 'cash_reconciliation_reversal')
                      AND cr.outlet_id IS NOT NULL
                ");
            }
        }

        if ($centralOutletId > 0) {
            DB::table('accounting_journals')
                ->whereNull('outlet_id')
                ->update(['outlet_id' => $centralOutletId]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('accounting_journals') && Schema::hasColumn('accounting_journals', 'outlet_id')) {
            Schema::table('accounting_journals', function (Blueprint $table) {
                $table->dropForeign(['outlet_id']);
                $table->dropIndex('acc_journals_outlet_date_status_idx');
                $table->dropColumn('outlet_id');
            });
        }
    }
};

