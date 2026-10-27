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
        Schema::create('purchase_edit_trails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id');
            $table->unsignedBigInteger('edited_by')->nullable();
            $table->decimal('before_total', 15, 2)->default(0);
            $table->decimal('after_total', 15, 2)->default(0);
            $table->decimal('delta_total', 15, 2)->default(0);
            $table->string('reason', 500)->nullable();
            $table->json('changes')->nullable();
            $table->timestamps();

            $table->foreign('purchase_id')->references('id')->on('purchases')->onDelete('cascade');
            $table->foreign('edited_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['purchase_id', 'created_at']);
            $table->index(['edited_by', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_edit_trails');
    }
};
