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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('outlet_id');
            $table->string('sale_no')->unique();
            $table->date('date');
            $table->text('description')->nullable(); 

            // Cancellation Tracking
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->string('cancellation_reason', 500)->nullable();

            // Accounting Integration
            $table->enum('accounting_status', ['unposted', 'posted', 'reversed'])->default('unposted');
            $table->unsignedBigInteger('accounting_journal_id')->nullable();
            $table->timestamp('accounting_posted_at')->nullable();

            // Receipt & Delivery Tracking
            $table->timestamp('printed_at')->nullable();
            $table->unsignedBigInteger('printed_by')->nullable();
            $table->string('delivery_code', 33)->nullable()->unique();
            $table->timestamp('delivered_at')->nullable();
            $table->unsignedBigInteger('delivered_by')->nullable();
            
            // Audit Fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('outlet_id')->references('id')->on('outlets')->restrictOnDelete();
            $table->foreign('accounting_journal_id')->references('id')->on('accounting_journals')->nullOnDelete();
            $table->foreign('cancelled_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('printed_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('delivered_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
            
            // Performance & Concurrency Indexes
            $table->index('outlet_id');
            $table->index('sale_no');
            $table->index('date');
            $table->index('accounting_status');
            $table->index(['created_by', 'date']);
            $table->index('created_by');
        });
    } 

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales');
    }
};
