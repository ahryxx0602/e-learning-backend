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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code', 32)->unique();

            $table->unsignedBigInteger('student_id');

            // Tài chính
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);

            // Mã giảm giá (nullable — cho sau khi có module Coupon)
            $table->string('coupon_code', 50)->nullable();

            // Trạng thái
            $table->enum('status', ['pending', 'paid', 'failed', 'cancelled', 'refunded'])->default('pending');
            $table->string('payment_method', 20)->default('vnpay');

            // Metadata
            $table->text('note')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('student_id', 'idx_orders_student');
            $table->index('status', 'idx_orders_status');

            // Foreign key
            $table->foreign('student_id')
                  ->references('id')->on('students')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
