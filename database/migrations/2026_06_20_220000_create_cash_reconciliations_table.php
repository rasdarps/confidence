<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->date('business_date');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->decimal('opening_float', 12, 2)->default(0);
            $table->decimal('expected_cash_sales', 12, 2)->default(0);
            $table->decimal('expected_cash_expenses', 12, 2)->default(0);
            $table->decimal('expected_closing_cash', 12, 2)->default(0);

            $table->decimal('physical_counted_cash', 12, 2)->default(0);
            $table->decimal('variance', 12, 2)->default(0);
            $table->enum('variance_type', ['shortage', 'overage', 'balanced'])->default('balanced');

            $table->string('status', 30)->default('submitted');

             $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            
            $table->text('reversal_reason')->nullable();

            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['business_date', 'user_id']);
            $table->index(['user_id', 'business_date']);
            $table->index(['business_date', 'status']);

            
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_reconciliations');
    }
};
