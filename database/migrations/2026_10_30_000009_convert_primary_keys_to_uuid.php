<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convert the existing auto-increment primary keys to UUIDs while keeping
     * the related rows (users, profiles, voters, candidates, settings, votes).
     *
     * Fresh databases already create UUID columns, so this migration is a no-op
     * for them.
     */
    public function up(): void
    {
        $type = Schema::getColumnType('users', 'id');

        if (! in_array($type, ['bigint', 'integer'], true)) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            throw new RuntimeException('Konversi primary key ke UUID hanya didukung untuk MySQL pada database lama.');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach (['users', 'school_profiles', 'voters', 'candidates', 'settings'] as $table) {
                DB::statement("ALTER TABLE `{$table}` ADD `uuid_migration_id` CHAR(36) NULL");
                DB::statement("UPDATE `{$table}` SET `uuid_migration_id` = UUID()");
            }

            DB::statement('ALTER TABLE votes ADD `uuid_migration_voter_id` CHAR(36) NULL, ADD `uuid_migration_candidate_id` CHAR(36) NULL');
            DB::statement('UPDATE votes v INNER JOIN voters vt ON v.voter_id = vt.id SET v.uuid_migration_voter_id = vt.uuid_migration_id');
            DB::statement('UPDATE votes v INNER JOIN candidates ct ON v.candidate_id = ct.id SET v.uuid_migration_candidate_id = ct.uuid_migration_id');

            $foreignKeys = DB::select(
                "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'votes'
                   AND COLUMN_NAME IN ('voter_id', 'candidate_id')
                   AND REFERENCED_TABLE_NAME IS NOT NULL"
            );

            foreach ($foreignKeys as $foreignKey) {
                DB::statement("ALTER TABLE votes DROP FOREIGN KEY `{$foreignKey->CONSTRAINT_NAME}`");
            }

            foreach (['users', 'school_profiles', 'voters', 'candidates', 'settings'] as $table) {
                DB::statement("ALTER TABLE `{$table}` MODIFY `id` BIGINT UNSIGNED NOT NULL");
                DB::statement(
                    "ALTER TABLE `{$table}`
                     DROP PRIMARY KEY,
                     DROP COLUMN `id`,
                     CHANGE `uuid_migration_id` `id` CHAR(36) NOT NULL,
                     ADD PRIMARY KEY (`id`)"
                );
            }

            DB::statement('ALTER TABLE votes DROP COLUMN voter_id, DROP COLUMN candidate_id');
            DB::statement(
                'ALTER TABLE votes
                 CHANGE uuid_migration_voter_id voter_id CHAR(36) NOT NULL,
                 CHANGE uuid_migration_candidate_id candidate_id CHAR(36) NOT NULL'
            );
            DB::statement('ALTER TABLE votes ADD UNIQUE votes_voter_id_unique (voter_id)');
            DB::statement('ALTER TABLE votes ADD CONSTRAINT votes_voter_id_foreign FOREIGN KEY (voter_id) REFERENCES voters (id) ON DELETE CASCADE');
            DB::statement('ALTER TABLE votes ADD CONSTRAINT votes_candidate_id_foreign FOREIGN KEY (candidate_id) REFERENCES candidates (id) ON DELETE CASCADE');

            DB::table('sessions')->delete();
            DB::statement('ALTER TABLE sessions DROP INDEX sessions_user_id_index, MODIFY user_id CHAR(36) NULL');
            DB::statement('ALTER TABLE sessions ADD INDEX sessions_user_id_index (user_id)');
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        // Converting UUID primary keys back to auto-increment values is not reversible.
    }
};
