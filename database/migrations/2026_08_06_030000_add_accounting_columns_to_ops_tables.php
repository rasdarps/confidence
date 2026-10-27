<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('expense_vouchers', function (Blueprint $table) {
            $table->enum('accounting_status', ['unposted', 'posted', 'reversed'])->default('unposted')->after('notes');
            $table->unsignedBigInteger('accounting_journal_id')->nullable()->after('accounting_status');
            $table->timestamp('accounting_posted_at')->nullable()->after('accounting_journal_id');

            $table->foreign('accounting_journal_id')->references('id')->on('accounting_journals')->nullOnDelete();
            $table->index('accounting_status');
        });

        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->enum('accounting_status', ['unposted', 'posted', 'reversed'])->default('unposted')->after('notes');
            $table->unsignedBigInteger('accounting_journal_id')->nullable()->after('accounting_status');
            $table->timestamp('accounting_posted_at')->nullable()->after('accounting_journal_id');

            $table->foreign('accounting_journal_id')->references('id')->on('accounting_journals')->nullOnDelete();
            $table->index('accounting_status');
        });

        Schema::table('cash_reconciliations', function (Blueprint $table) {
            $table->enum('accounting_status', ['unposted', 'posted', 'reversed'])->default('unposted')->after('reversal_reason');
            $table->unsignedBigInteger('accounting_journal_id')->nullable()->after('accounting_status');
            $table->timestamp('accounting_posted_at')->nullable()->after('accounting_journal_id');

            $table->foreign('accounting_journal_id')->references('id')->on('accounting_journals')->nullOnDelete();
            $table->index('accounting_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_reconciliations', function (Blueprint $table) {
            $table->dropForeign(['accounting_journal_id']);
            $table->dropIndex(['accounting_status']);
            $table->dropColumn(['accounting_status', 'accounting_journal_id', 'accounting_posted_at']);
        });

        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->dropForeign(['accounting_journal_id']);
            $table->dropIndex(['accounting_status']);
            $table->dropColumn(['accounting_status', 'accounting_journal_id', 'accounting_posted_at']);
        });

        Schema::table('expense_vouchers', function (Blueprint $table) {
            $table->dropForeign(['accounting_journal_id']);
            $table->dropIndex(['accounting_status']);
            $table->dropColumn(['accounting_status', 'accounting_journal_id', 'accounting_posted_at']);
        });
    }
};
