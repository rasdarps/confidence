<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->timestamp('printed_at')->nullable();
            $table->unsignedBigInteger('printed_by')->nullable();
            $table->string('delivery_code', 33)->nullable()->unique();
            $table->timestamp('delivered_at')->nullable();
            $table->unsignedBigInteger('delivered_by')->nullable();
            $table->foreign('printed_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('delivered_by')->references('id')->on('users')->nullOnDelete();
        });

        DB::table('sales')->whereNull('delivery_code')->orderBy('id')->chunkById(200, function ($sales) {
            foreach ($sales as $sale) {
                do {
                    $deliveryCode = 'D' . Str::upper(Str::random(11));
                } while (DB::table('sales')->where('delivery_code', $deliveryCode)->exists());

                DB::table('sales')->where('id', $sale->id)->update([
                    'delivery_code' => $deliveryCode,
                ]);
            }
        });

        Schema::create('sale_receipt_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 32);
            $table->string('reason', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['sale_id', 'event_type']);
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sale_receipt_events');

        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['printed_by']);
            $table->dropForeign(['delivered_by']);
            $table->dropUnique(['delivery_code']);
            $table->dropColumn(['printed_at', 'printed_by', 'delivery_code', 'delivered_at', 'delivered_by']);
        });
    }
};