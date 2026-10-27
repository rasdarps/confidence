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
        Schema::create('expense_vouchers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('outlet_id')->nullable();
            $table->date('voucher_date');
            $table->string('reference_no')->nullable()->unique();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();

            // Accounting Integration
            $table->enum('accounting_status', ['unposted', 'posted', 'reversed'])->default('unposted');
            $table->unsignedBigInteger('accounting_journal_id')->nullable();
            $table->timestamp('accounting_posted_at')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('outlet_id')->references('id')->on('outlets')->nullOnDelete();
            $table->foreign('accounting_journal_id')->references('id')->on('accounting_journals')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['outlet_id', 'voucher_date']);
            $table->index('voucher_date');
            $table->index('accounting_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_vouchers');
    }
};
