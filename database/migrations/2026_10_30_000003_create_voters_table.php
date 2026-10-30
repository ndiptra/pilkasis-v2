<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('student_number', 50)->unique();
            $table->string('name');
            $table->string('grade', 50);
            $table->string('gender', 20)->default('male');
            $table->boolean('is_active')->default(true);
            $table->boolean('has_voted')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voters');
    }
};
