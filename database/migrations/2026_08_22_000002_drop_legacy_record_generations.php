<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reclaim the disk the Python scraper leaked.
 *
 * `StoreGames.py` created a `records_<unix epoch>` table on every run, copied
 * the entire dataset into it, and repointed a `vw_records` view at the newest
 * one. Nothing ever dropped the previous generation. At ~275k rows and ~35 MB
 * apiece on a daily cron, that is gigabytes of exact duplication.
 *
 * The names are epoch-stamped, so they have to be discovered rather than
 * listed. This is deliberately defensive: a Postgres database built from these
 * migrations will never have any of these objects, and the production records
 * are being re-fetched from the API rather than imported. It exists for the one
 * case that would otherwise carry the leak across - somebody restoring the old
 * MySQL dump wholesale.
 *
 * Irreversible by design: the point is to delete duplicate data.
 */
return new class extends Migration
{
    public function up(): void
    {
        // The view depends on whichever generation table it was last pointed
        // at, so it has to go first or the DROP TABLE below fails.
        DB::statement('DROP VIEW IF EXISTS '.$this->quote('vw_records'));

        foreach ($this->legacyGenerationTables() as $table) {
            DB::statement('DROP TABLE IF EXISTS '.$this->quote($table));
        }
    }

    public function down(): void
    {
        // Nothing to restore. The dropped tables were duplicates of `records`
        // by construction, and the view was a pointer at one of them.
    }

    /**
     * Every `records_<digits>` base table in the current schema.
     *
     * Matched with a LIKE that both Postgres and MySQL accept, then filtered in
     * PHP so the digits-only rule is enforced identically on either driver -
     * this must not touch a table someone deliberately called `records_archive`.
     *
     * @return list<string>
     */
    private function legacyGenerationTables(): array
    {
        $connection = DB::connection();

        $schema = match ($connection->getDriverName()) {
            'pgsql' => 'current_schema()',
            'mysql', 'mariadb' => 'database()',
            default => null,
        };

        if ($schema === null) {
            // sqlite and friends have no information_schema; nothing to clean
            // up there, since the Python only ever ran against MySQL.
            return [];
        }

        $rows = $connection->select(
            'select table_name from information_schema.tables'
            ." where table_schema = {$schema}"
            ." and table_type = 'BASE TABLE'"
            ." and table_name like 'records\_%'"
        );

        $tables = [];

        foreach ($rows as $row) {
            $name = (string) ((array) $row)['table_name'];

            if (preg_match('/^records_\d+$/', $name) === 1) {
                $tables[] = $name;
            }
        }

        return $tables;
    }

    private function quote(string $identifier): string
    {
        return DB::connection()->getQueryGrammar()->wrapTable($identifier);
    }
};
