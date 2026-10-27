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
        Schema::create('purchase_payment_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')
                ->constrained('purchases')
                ->unsigned()
                ->onUpdate('cascade')
                ->onDelete('cascade');
            
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->double('current_paid_amount')->nullable();
            $table->date('date');
            $table->unsignedBigInteger('updated_by')->nullable(); 

            // Accounting Integration
            $table->enum('accounting_status', ['unposted', 'posted', 'reversed'])->default('unposted');
            $table->unsignedBigInteger('accounting_journal_id')->nullable();
            $table->timestamp('accounting_posted_at')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->nullOnDelete();
            $table->foreign('accounting_journal_id')->references('id')->on('accounting_journals')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['date', 'accounting_status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_payment_details');
    }
};
