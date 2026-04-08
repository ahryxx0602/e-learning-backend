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
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();

            $table->string('name', 100);
            $table->date('date_of_birth')->nullable();
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->float('exp')->default(0)->comment('Số năm kinh nghiệm');
            $table->string('image', 255)->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=active, 0=inactive');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
