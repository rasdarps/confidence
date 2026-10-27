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

            $table->enum('paid_status', ['full_payment', 'partial_payment', 'full_credit'])->default('full_payment');
            $table->double('paid_amount')->nullable();
            $table->double('due_amount')->nullable();
            $table->double('total_amount')->nullable();
            $table->double('discount_amount')->nullable();

            $table->timestamps();
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
