<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql' || ! $this->hasNumericId()) {
            return;
        }

        DB::statement('ALTER TABLE votes ADD uuid_migration_id CHAR(36) NULL');
        DB::statement('UPDATE votes SET uuid_migration_id = UUID()');
        DB::statement('ALTER TABLE votes MODIFY id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE votes DROP PRIMARY KEY, DROP COLUMN id');
        DB::statement('ALTER TABLE votes CHANGE uuid_migration_id id CHAR(36) NOT NULL FIRST, ADD PRIMARY KEY (id)');
    }

    public function down(): void
    {
        // Converting UUID primary keys back to auto-increment values is not reversible.
    }

    private function hasNumericId(): bool
    {
        return in_array(Schema::getColumnType('votes', 'id'), ['bigint', 'int', 'integer'], true);
    }
};
