<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_transaction_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->date('business_date')->index();
            $table->decimal('sales_total', 15, 2)->default(0);
            $table->decimal('expenses_total', 15, 2)->default(0);
            $table->decimal('cash_shortage', 15, 2)->default(0);
            $table->string('recipient_email')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->foreignId('sent_by')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('type', ['auto', 'manual'])->default('manual');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Ensure only one email per company per business_date
            $table->unique(['company_id', 'business_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_transaction_emails');
    }
};
