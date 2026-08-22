<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Turn `records` into the single, self-refreshing table the sync command needs.
 *
 *  - `synced_at` marks which pass last saw a row, so the pass can delete what it
 *    did not see. That replaces the old "build a whole new table and repoint a
 *    view at it" scheme.
 *  - A unique index on `runId` is what `upsert()` needs to conflict against. The
 *    original migration gave `runId` only a plain index.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->timestamp('synced_at')->nullable()->index();
        });

        // The upsert target must be unique. `records` is empty on a fresh
        // database and was empty in production too (it was only ever the
        // template that `CREATE TABLE records_<epoch> LIKE records` copied),
        // but collapse any duplicates first so this cannot fail on a database
        // that got data into it some other way.
        $this->collapseDuplicateRunIds();

        Schema::table('records', function (Blueprint $table) {
            $table->unique('runId', 'records_runid_unique');

            // The unique constraint's backing index supersedes the plain one
            // the original migration created.
            $table->dropIndex('records_runid_index');
        });
    }

    public function down(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->index('runId', 'records_runid_index');
            $table->dropUnique('records_runid_unique');
            $table->dropIndex(['synced_at']);
            $table->dropColumn('synced_at');
        });
    }

    private function collapseDuplicateRunIds(): void
    {
        $duplicates = DB::table('records')
            ->select('runId')
            ->groupBy('runId')
            ->havingRaw('count(*) > 1')
            ->pluck('runId');

        foreach ($duplicates as $runId) {
            $keep = DB::table('records')->where('runId', $runId)->max('id');

            DB::table('records')->where('runId', $runId)->where('id', '<', $keep)->delete();
        }
    }
};
