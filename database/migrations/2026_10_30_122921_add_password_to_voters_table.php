<?php

use App\Models\Voter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voters', function (Blueprint $table) {
            $table->string('password', 7)->nullable()->after('grade');
        });

        Voter::query()->whereNull('password')->get()->each(function (Voter $voter) {
            $voter->forceFill(['password' => Voter::generateUniquePassword($voter->election_id)])->saveQuietly();
        });

        Schema::table('voters', function (Blueprint $table) {
            $table->string('password', 7)->nullable(false)->change();
            $table->unique(['election_id', 'password']);
        });
    }

    public function down(): void
    {
        Schema::table('voters', function (Blueprint $table) {
            $table->dropUnique(['election_id', 'password']);
        });

        Schema::table('voters', function (Blueprint $table) {
            $table->dropColumn('password');
        });
    }
};
