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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('order_id');

            // VNPAY info
            $table->string('gateway', 20)->default('vnpay');
            $table->string('transaction_code', 100)->nullable();
            $table->string('bank_code', 20)->nullable();
            $table->string('card_type', 20)->nullable();

            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');

            // VNPAY response raw
            $table->json('gateway_response')->nullable();
            $table->string('response_code', 10)->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('order_id', 'idx_transactions_order');
            $table->index('transaction_code', 'idx_transactions_code');

            // Foreign key
            $table->foreign('order_id')
                  ->references('id')->on('orders')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
