<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_profiles', function (Blueprint $table) {
            $table->string('headmaster_nip', 30)->nullable()->after('headmaster_name');
            $table->dropColumn(['npsn', 'description']);
        });
    }

    public function down(): void
    {
        Schema::table('school_profiles', function (Blueprint $table) {
            $table->string('npsn', 30)->nullable()->after('name');
            $table->text('description')->nullable()->after('logo_path');
            $table->dropColumn('headmaster_nip');
        });
    }
};
