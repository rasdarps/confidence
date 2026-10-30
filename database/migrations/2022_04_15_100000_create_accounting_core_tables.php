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
        Schema::create('accounting_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->enum('type', ['asset', 'liability', 'equity', 'income', 'expense']);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->boolean('is_bank_account')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('accounting_accounts')->nullOnDelete();
            $table->index(['type', 'is_active']);
        });

        Schema::create('accounting_fiscal_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_closed')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamps();

            $table->foreign('closed_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['start_date', 'end_date']);
            $table->index(['is_closed', 'start_date', 'end_date']);
        });

        Schema::create('accounting_journals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('outlet_id')->nullable();
            $table->string('journal_no')->unique();
            $table->date('posting_date');
            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'posted', 'reversed'])->default('posted');
            $table->decimal('total_debit', 14, 2)->default(0);
            $table->decimal('total_credit', 14, 2)->default(0);
            $table->unsignedBigInteger('fiscal_period_id')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('reversed_journal_id')->nullable();
            $table->timestamps();

            $table->foreign('outlet_id')->references('id')->on('outlets')->nullOnDelete();
            $table->foreign('fiscal_period_id')->references('id')->on('accounting_fiscal_periods')->nullOnDelete();
            $table->foreign('posted_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reversed_journal_id')->references('id')->on('accounting_journals')->nullOnDelete();
            $table->index(['source_type', 'source_id']);
            $table->index(['posting_date', 'status']);
            $table->index(['outlet_id', 'posting_date', 'status'], 'acc_journals_outlet_date_status_idx');
        });

        Schema::create('accounting_journal_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('journal_id');
            $table->unsignedBigInteger('account_id');
            $table->text('line_description')->nullable();
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->string('entity_type', 100)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->timestamps();

            $table->foreign('journal_id')->references('id')->on('accounting_journals')->cascadeOnDelete();
            $table->foreign('account_id')->references('id')->on('accounting_accounts')->restrictOnDelete();
            $table->index(['entity_type', 'entity_id']);
            $table->index(['account_id', 'created_at']);
        });

        Schema::create('accounting_account_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('event_key', 120)->unique();
            $table->unsignedBigInteger('debit_account_id')->nullable();
            $table->unsignedBigInteger('credit_account_id')->nullable();
            $table->json('extra_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('debit_account_id')->references('id')->on('accounting_accounts')->nullOnDelete();
            $table->foreign('credit_account_id')->references('id')->on('accounting_accounts')->nullOnDelete();
        });

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('bank_name')->nullable();
            $table->string('account_number', 80)->nullable();
            $table->string('currency', 10)->default('GHS');
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->decimal('current_balance', 14, 2)->default(0);
            $table->unsignedBigInteger('gl_account_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('gl_account_id')->references('id')->on('accounting_accounts')->nullOnDelete();
            $table->index(['is_active', 'name']);
        });

        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bank_account_id');
            $table->date('transaction_date');
            $table->enum('type', ['deposit', 'withdrawal', 'transfer_in', 'transfer_out', 'bank_fee', 'interest_income', 'receipt', 'payment', 'reconciliation_adjustment']);
            $table->decimal('amount', 14, 2);
            $table->text('description')->nullable();
            $table->string('reference_no', 120)->nullable();
            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->unsignedBigInteger('counterparty_bank_account_id')->nullable();
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->restrictOnDelete();
            $table->foreign('counterparty_bank_account_id')->references('id')->on('bank_accounts')->nullOnDelete();
            $table->foreign('journal_id')->references('id')->on('accounting_journals')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['transaction_date', 'type']);
            $table->index(['source_type', 'source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('accounting_account_mappings');
        Schema::dropIfExists('accounting_journal_lines');
        Schema::dropIfExists('accounting_journals');
        Schema::dropIfExists('accounting_fiscal_periods');
        Schema::dropIfExists('accounting_accounts');
    }
};

