<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_ledgers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->enum('movement_type', [
                'purchase',
                'sale',
                'adjustment_increase',
                'adjustment_decrease',
                'purchase_cancel',
                'sale_cancel',
            ]);
            $table->string('reference_type', 60);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->unsignedBigInteger('reference_detail_id')->nullable();
            $table->dateTime('movement_at');

            $table->decimal('qty_in', 15, 2)->default(0);
            $table->decimal('qty_out', 15, 2)->default(0);
            $table->decimal('delta_qty', 15, 2)->default(0);
            $table->decimal('balance_before', 15, 2)->default(0);
            $table->decimal('balance_after', 15, 2)->default(0);

            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->string('reason', 255)->nullable();
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['product_id', 'movement_at', 'id']);
            $table->index(['movement_type', 'movement_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['created_by', 'movement_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_ledgers');
    }
};
