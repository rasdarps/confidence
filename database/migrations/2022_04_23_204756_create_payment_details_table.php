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
        Schema::create('payment_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')
            ->constrained('sales')
            ->unsigned()
            ->onUpdate('cascade')
            ->onDelete('cascade');
            
            $table->double('current_paid_amount')->nullable();
            $table->date('date');
            
            // Fixed: Proper foreign key relationship to users table
            $table->unsignedBigInteger('updated_by')->nullable();
            
            $table->timestamps();

            // Add foreign key constraint for updated_by
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_details');
    }
};

