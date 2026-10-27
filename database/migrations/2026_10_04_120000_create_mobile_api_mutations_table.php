<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_api_mutations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('key_hash', 64);
            $table->string('request_hash', 64);
            $table->json('response');
            $table->timestamps();
            $table->unique(['user_id', 'key_hash']);
        });
        Schema::table('payment_details', function (Blueprint $table) {
            $table->string('reference', 120)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_api_mutations');
        Schema::table('payment_details', function (Blueprint $table) {
            $table->dropColumn('reference');
        });
    }
};