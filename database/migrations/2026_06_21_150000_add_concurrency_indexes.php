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
        // Products: index for stock queries during concurrent sales
        Schema::table('products', function (Blueprint $table) {
            $table->index('quantity');
            $table->index(['category_id', 'quantity']);
            $table->index('created_by');
        });

        // Payments: index for status lookups
        Schema::table('payments', function (Blueprint $table) {
            $table->index('paid_status');
            $table->index(['sale_id', 'paid_status']);
        });

        // Sale Details: composite index for line-item lookups
        Schema::table('sale_details', function (Blueprint $table) {
            $table->index(['sale_id', 'product_id']);
            $table->index('product_id');
        });

        // sales: index for user-based queries and date filtering
        Schema::table('sales', function (Blueprint $table) {
            $table->index(['created_by', 'date']);
            $table->index('created_by');
        });

        // Purchase Details: same pattern as Sale details for consistency
        if (Schema::hasTable('purchase_details')) {
            Schema::table('purchase_details', function (Blueprint $table) {
                $table->index(['purchase_id', 'product_id']);
                $table->index('product_id');
            });
        }

        // Purchases: user and date indexing for consistency
        if (Schema::hasTable('purchases')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->index(['created_by', 'date']);
                $table->index('created_by');
            });
        }

        // Purchase Payments: status indexing for consistency
        if (Schema::hasTable('purchase_payments')) {
            Schema::table('purchase_payments', function (Blueprint $table) {
                $table->index('paid_status');
                $table->index(['purchase_id', 'paid_status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Intentionally left as a no-op.
        // On MySQL, several of these indexes overlap with foreign-key columns and
        // cannot be dropped safely while those constraints are still active.
        // Keeping the indexes in place avoids migration refresh failures while still
        // allowing the schema to be re-created cleanly in a fresh database.
    }
};

