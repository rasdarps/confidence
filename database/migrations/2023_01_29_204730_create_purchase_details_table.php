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
        Schema::create('purchase_details', function (Blueprint $table) {
            $table->id();

            $table->date('date');
            
            $table->foreignId('purchase_id')
            ->constrained('purchases')
            ->unsigned()
            ->onUpdate('cascade')
            ->onDelete('cascade');

            $table->foreignId('category_id')
            ->constrained('categories')
            ->unsigned()
            ->onUpdate('cascade')
            ->onDelete('cascade');
            
            $table->foreignId('product_id')
            ->constrained('products')
            ->unsigned()
            ->onUpdate('cascade')
            ->onDelete('cascade');

            $table->double('buying_qty');
            $table->double('unit_cost_price');

            $table->foreignId('unit_id')
            ->constrained('units')
            ->unsigned()
            ->onUpdate('cascade')
            ->onDelete('cascade');
        
            $table->double('buying_price');
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
        Schema::dropIfExists('purchase_details');
    }
};
