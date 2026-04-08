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
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['video', 'document', 'text'])->default('video');
            $table->longText('content')->nullable(); // Dùng khi type=text
            $table->unsignedBigInteger('video_id')->nullable();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_preview')->default(false);
            $table->integer('duration')->nullable(); // Số giây
            $table->tinyInteger('status')->default(0); // 0=draft, 1=published
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
        });

        // Chỉ thêm FK media nếu bảng media đã tồn tại (Module Upload có thể chưa migrate)
        if (Schema::hasTable('media')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->foreign('video_id')->references('id')->on('media')->onDelete('set null');
                $table->foreign('document_id')->references('id')->on('media')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
