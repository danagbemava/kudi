<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            // Integer minor units (pesewas): net_amount = gross_amount - fee_amount.
            $table->unsignedBigInteger('gross_amount');
            $table->unsignedBigInteger('fee_amount');
            $table->unsignedBigInteger('net_amount');
            $table->string('status')->default('pending')->index();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
