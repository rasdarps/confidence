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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sale_id')
                ->constrained('sales')
                ->unsigned()
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->unsigned()
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->unsignedBigInteger('bank_account_id')->nullable();

            $table->enum('paid_status', ['full_payment', 'partial_payment', 'full_credit'])->default('full_payment');
            $table->double('paid_amount')->nullable();
            $table->double('due_amount')->nullable();
            $table->double('total_amount')->nullable();
            $table->double('discount_amount')->nullable();
            $table->decimal('taxable_amount', 12, 2)->default(0);
            $table->decimal('nhil_amount', 12, 2)->default(0);
            $table->decimal('getfund_amount', 12, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('cash_tendered', 12, 2)->default(0);
            $table->decimal('change_due', 12, 2)->default(0);

            // Accounting Integration
            $table->enum('accounting_status', ['unposted', 'posted', 'reversed'])->default('unposted');
            $table->unsignedBigInteger('accounting_journal_id')->nullable();
            $table->timestamp('accounting_posted_at')->nullable();

            $table->timestamps();

            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->nullOnDelete();
            $table->foreign('accounting_journal_id')->references('id')->on('accounting_journals')->nullOnDelete();

            $table->index('paid_status');
            $table->index(['sale_id', 'paid_status']);
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
        Schema::dropIfExists('payments');
    }
};
