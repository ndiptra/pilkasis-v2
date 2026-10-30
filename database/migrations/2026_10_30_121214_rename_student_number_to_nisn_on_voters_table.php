<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voters', function (Blueprint $table) {
            $table->renameColumn('student_number', 'nisn');
        });

        Schema::table('voters', function (Blueprint $table) {
            $table->string('nisn', 10)->change();
            $table->dropColumn('gender');
        });
    }

    public function down(): void
    {
        Schema::table('voters', function (Blueprint $table) {
            $table->string('gender', 20)->default('male');
        });

        Schema::table('voters', function (Blueprint $table) {
            $table->string('nisn', 50)->change();
        });

        Schema::table('voters', function (Blueprint $table) {
            $table->renameColumn('nisn', 'student_number');
        });
    }
};
