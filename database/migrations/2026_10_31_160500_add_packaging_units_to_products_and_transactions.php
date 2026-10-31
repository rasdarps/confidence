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
        // 1. Products table
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'has_sub_unit')) {
                $table->boolean('has_sub_unit')->default(false)->after('unit_id');
            }
            if (!Schema::hasColumn('products', 'sub_unit_id')) {
                $table->unsignedBigInteger('sub_unit_id')->nullable()->after('has_sub_unit');
                $table->foreign('sub_unit_id')->references('id')->on('units')->nullOnDelete();
            }
            if (!Schema::hasColumn('products', 'pieces_per_pack')) {
                $table->unsignedInteger('pieces_per_pack')->nullable()->after('sub_unit_id');
            }
            if (!Schema::hasColumn('products', 'sub_unit_cost_price')) {
                $table->decimal('sub_unit_cost_price', 15, 2)->nullable()->after('cost_price');
            }
            if (!Schema::hasColumn('products', 'sub_unit_selling_price')) {
                $table->decimal('sub_unit_selling_price', 15, 2)->nullable()->after('selling_price');
            }
        });

        // 2. Sale details table
        Schema::table('sale_details', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_details', 'pack_qty')) {
                $table->decimal('pack_qty', 15, 2)->default(0)->after('selling_qty');
            }
            if (!Schema::hasColumn('sale_details', 'piece_qty')) {
                $table->decimal('piece_qty', 15, 2)->default(0)->after('pack_qty');
            }
            if (!Schema::hasColumn('sale_details', 'pieces_per_pack')) {
                $table->unsignedInteger('pieces_per_pack')->nullable()->after('piece_qty');
            }
            if (!Schema::hasColumn('sale_details', 'sub_unit_selling_price')) {
                $table->decimal('sub_unit_selling_price', 15, 2)->nullable()->after('unit_selling_price');
            }
        });

        // 3. Purchase details table
        Schema::table('purchase_details', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_details', 'pack_qty')) {
                $table->decimal('pack_qty', 15, 2)->default(0)->after('buying_qty');
            }
            if (!Schema::hasColumn('purchase_details', 'piece_qty')) {
                $table->decimal('piece_qty', 15, 2)->default(0)->after('pack_qty');
            }
            if (!Schema::hasColumn('purchase_details', 'pieces_per_pack')) {
                $table->unsignedInteger('pieces_per_pack')->nullable()->after('piece_qty');
            }
            if (!Schema::hasColumn('purchase_details', 'sub_unit_cost_price')) {
                $table->decimal('sub_unit_cost_price', 15, 2)->nullable()->after('unit_cost_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_details', function (Blueprint $table) {
            $cols = ['pack_qty', 'piece_qty', 'pieces_per_pack', 'sub_unit_cost_price'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('purchase_details', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('sale_details', function (Blueprint $table) {
            $cols = ['pack_qty', 'piece_qty', 'pieces_per_pack', 'sub_unit_selling_price'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('sale_details', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'sub_unit_id')) {
                $table->dropForeign(['sub_unit_id']);
                $table->dropColumn('sub_unit_id');
            }
            $cols = ['has_sub_unit', 'pieces_per_pack', 'sub_unit_cost_price', 'sub_unit_selling_price'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

