<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->whereNotIn('status', ['not_started', 'ongoing', 'closed', 'final'])->update(['status' => 'not_started']);

        Schema::table('settings', function ($table) {
            $table->string('status', 20)->default('not_started')->change();
        });
    }

    public function down(): void
    {
        DB::table('settings')->update(['status' => 'draft']);

        Schema::table('settings', function ($table) {
            $table->string('status', 20)->default('draft')->change();
        });
    }
};
