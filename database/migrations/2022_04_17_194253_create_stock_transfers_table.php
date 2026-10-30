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
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_no', 50)->unique();
            $table->unsignedBigInteger('from_outlet_id');
            $table->unsignedBigInteger('to_outlet_id');
            $table->enum('status', ['draft', 'dispatched', 'received', 'cancelled'])->default('draft');
            $table->date('transfer_date');
            $table->dateTime('dispatched_at')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->unsignedBigInteger('dispatched_by')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('from_outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('to_outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('dispatched_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('received_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['from_outlet_id', 'status']);
            $table->index(['to_outlet_id', 'status']);
            $table->index('transfer_date');
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_transfer_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('qty_dispatched', 15, 2);
            $table->decimal('qty_received', 15, 2)->default(0);
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->timestamps();

            $table->foreign('stock_transfer_id')->references('id')->on('stock_transfers')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();

            $table->index(['stock_transfer_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
    }
};

