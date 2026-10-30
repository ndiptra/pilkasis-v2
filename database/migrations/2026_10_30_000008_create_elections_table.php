<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('elections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('year', 10);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::table('voters', function (Blueprint $table) {
            $table->foreignUuid('election_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->dropUnique(['student_number']);
            $table->unique(['election_id', 'student_number']);
        });

        Schema::table('candidates', function (Blueprint $table) {
            $table->foreignUuid('election_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->dropUnique(['order_number']);
            $table->unique(['election_id', 'order_number']);
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->foreignUuid('election_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->unique('election_id');
        });

        Schema::table('votes', function (Blueprint $table) {
            $table->foreignUuid('election_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        $this->assignExistingDataToAnElection();

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['election_name', 'election_year', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('election_name')->default('Pemilihan Ketua OSIS')->after('id');
            $table->string('election_year', 10)->nullable()->after('election_name');
            $table->timestamp('starts_at')->nullable()->after('election_year');
            $table->timestamp('ends_at')->nullable()->after('starts_at');
            $table->dropUnique(['election_id']);
            $table->dropConstrainedForeignId('election_id');
        });

        Schema::table('votes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('election_id');
        });

        Schema::table('candidates', function (Blueprint $table) {
            $table->dropUnique(['election_id', 'order_number']);
            $table->unique('order_number');
            $table->dropConstrainedForeignId('election_id');
        });

        Schema::table('voters', function (Blueprint $table) {
            $table->dropUnique(['election_id', 'student_number']);
            $table->unique('student_number');
            $table->dropConstrainedForeignId('election_id');
        });

        Schema::dropIfExists('elections');
    }

    private function assignExistingDataToAnElection(): void
    {
        $hasExistingData = DB::table('voters')->exists()
            || DB::table('candidates')->exists()
            || DB::table('settings')->exists()
            || DB::table('votes')->exists();

        if (! $hasExistingData) {
            return;
        }

        $setting = DB::table('settings')->first();

        $electionId = (string) Str::uuid();

        DB::table('elections')->insert([
            'id' => $electionId,
            'name' => $setting->election_name ?? 'Pemilihan Ketua OSIS',
            'year' => $setting->election_year ?: (string) now()->year,
            'starts_at' => $setting->starts_at ?? now(),
            'ends_at' => $setting->ends_at ?? now()->addDay(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('voters')->update(['election_id' => $electionId]);
        DB::table('candidates')->update(['election_id' => $electionId]);
        DB::table('votes')->update(['election_id' => $electionId]);
        DB::table('settings')->update(['election_id' => $electionId]);
    }
};
