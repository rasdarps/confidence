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
        Schema::create('capital_loan_entries', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->enum('entry_type', ['owner_capital', 'bank_loan', 'individual_loan']);
            $table->enum('receive_into', ['cash', 'bank'])->default('cash');
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->string('lender_name', 255)->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('reference_no', 120)->nullable();
            $table->text('description')->nullable();

            $table->enum('accounting_status', ['unposted', 'posted', 'reversed'])->default('unposted');
            $table->unsignedBigInteger('accounting_journal_id')->nullable();
            $table->timestamp('accounting_posted_at')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->nullOnDelete();
            $table->foreign('accounting_journal_id')->references('id')->on('accounting_journals')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['transaction_date', 'entry_type']);
            $table->index('accounting_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capital_loan_entries');
    }
};
