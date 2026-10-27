<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
           
            $table->foreignId('purchase_id')
                ->constrained('purchases')
                ->unsigned()
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->unsigned()
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->unsignedBigInteger('bank_account_id')->nullable();

            $table->enum('paid_status', ['full_payment', 'partial_payment', 'full_credit'])->default('full_payment');
            $table->double('paid_amount')->nullable();
            $table->double('due_amount')->nullable();
            $table->double('total_amount')->nullable();
            $table->double('discount_amount')->nullable();

            // Accounting Integration
            $table->enum('accounting_status', ['unposted', 'posted', 'reversed'])->default('unposted');
            $table->unsignedBigInteger('accounting_journal_id')->nullable();
            $table->timestamp('accounting_posted_at')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->nullOnDelete();
            $table->foreign('accounting_journal_id')->references('id')->on('accounting_journals')->nullOnDelete();

            $table->index('paid_status');
            $table->index(['purchase_id', 'paid_status']);
            $table->index(['paid_status', 'accounting_status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_payments');
    }
};
