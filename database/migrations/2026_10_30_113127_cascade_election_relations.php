<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $tables = ['voters', 'candidates', 'settings', 'votes'];

    public function up(): void
    {
        $this->replaceForeignKey('cascadeOnDelete');
    }

    public function down(): void
    {
        $this->replaceForeignKey('nullOnDelete');
    }

    private function replaceForeignKey(string $onDelete): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['election_id']);
            });

            Schema::table($tableName, function (Blueprint $table) use ($onDelete) {
                $table->foreign('election_id')->references('id')->on('elections')->{$onDelete}();
            });
        }
    }
};
