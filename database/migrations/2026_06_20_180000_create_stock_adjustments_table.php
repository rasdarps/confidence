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
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('outlet_id');
            $table->date('adjustment_date');
            $table->string('reference_no')->nullable()->unique();
            $table->enum('adjustment_type', ['increase', 'decrease','mixed']);
            $table->string('reason', 150)->nullable();
            $table->text('notes')->nullable();
            $table->decimal('total_qty', 15, 2)->default(0);
            $table->decimal('total_value', 15, 2)->default(0);

            // Accounting Integration
            $table->enum('accounting_status', ['unposted', 'posted', 'reversed'])->default('unposted');
            $table->unsignedBigInteger('accounting_journal_id')->nullable();
            $table->timestamp('accounting_posted_at')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('accounting_journal_id')->references('id')->on('accounting_journals')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['outlet_id', 'adjustment_date']);
            $table->index('adjustment_date');
            $table->index('adjustment_type');
            $table->index('accounting_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
