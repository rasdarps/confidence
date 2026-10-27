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
        Schema::create('sale_details', function (Blueprint $table) {
            $table->id();
            $table->date('date')->nullable();
            
            $table->foreignId('sale_id')
            ->constrained('sales')
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

            $table->double('selling_qty');
            $table->double('unit_selling_price');

            $table->foreignId('unit_id')
            ->constrained('units')
            ->unsigned()
            ->onUpdate('cascade')
            ->onDelete('cascade');

            $table->double('selling_price');
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
        Schema::dropIfExists('sale_details');
    }
};

