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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
    
            $table->foreignId('unit_id')
            ->constrained('units')
            ->unsigned()
            ->onUpdate('cascade')
            ->onDelete('cascade');

            $table->foreignId('category_id')
            ->constrained('categories')
            ->unsigned()
            ->onUpdate('cascade')
            ->onDelete('cascade');

            $table->string('name')->unique();
            $table->string('barcode', 50)->nullable()->unique(); // <-- Added for scanning
            $table->double('quantity')->default(0);
            $table->double('stock_level')->default(0);
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->decimal('selling_price', 10, 2)->default(0);
            
            // Fixed: Proper foreign key relationships to users table
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Add foreign key constraints for created_by and updated_by
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
};
