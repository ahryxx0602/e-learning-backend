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
        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 20)->default('local');
            $table->enum('type', ['video', 'document', 'image']);
            $table->string('original_name');
            $table->string('path', 1000);
            $table->string('url', 1000);
            $table->string('mime_type', 100);
            $table->bigInteger('size');
            $table->enum('status', ['pending', 'ready', 'orphan'])->default('ready');
            $table->integer('reference_count')->default(0);
            $table->integer('duration')->nullable(); // seconds
            $table->integer('width')->nullable(); // px
            $table->integer('height')->nullable(); // px
            $table->integer('bitrate')->nullable(); // bps
            $table->string('codec', 50)->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            
            // Foreign keys if necessary
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_files');
    }
};
