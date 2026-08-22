<?php

namespace App\Console\Commands;

use App\Record;
use App\Support\RateLimiter;
use App\Support\SpeedrunComApi;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * The single mechanism for getting world records out of speedrun.com and into
 * this database.
 *
 * Replaces all three of the things that used to overlap here:
 *   - app/Python/StoreGames.py         (a full table copy per run, forever)
 *   - php artisan speedrunwr:storegames
 *   - php artisan speedrunwr:getrecords
 */
class Sync extends Command
{
    protected $signature = 'speedrunwr:sync
        {--games= : Stop after this many games. Disables pruning unless --prune is also given.}
        {--game=* : Sync only these speedrun.com game ids. Disables pruning unless --prune is also given.}
        {--top=25 : Leaderboard places to fetch per category, which is what sets the "competition" count.}
        {--chunk=500 : Rows per upsert batch.}
        {--prune : Force the post-pass delete even on a partial or lossy run.}
        {--no-prune : Never delete, whatever happened.}
        {--rate-limit=99 : Requests allowed per --rate-period seconds.}
        {--rate-period=60 : Length of the rate-limit window, in seconds.}';

    protected $description = 'Sync world records from speedrun.com into the records table';

    /**
     * Skip pruning if more than this share of games failed outright — a network
     * blip that lost a tenth of the site should not delete a tenth of the table.
     */
    private const MAX_FAILURE_RATE = 0.05;

    public function handle(): int
    {
        $top = max(1, (int) $this->option('top'));
        $chunkSize = max(1, (int) $this->option('chunk'));
        $gameLimit = $this->option('games') !== null ? max(1, (int) $this->option('games')) : null;
        $explicitGames = array_values(array_filter((array) $this->option('game')));

        $api = new SpeedrunComApi(new RateLimiter(
            max(1, (int) $this->option('rate-limit')),
            max(0.001, (float) $this->option('rate-period')),
        ));

        // Everything written by this pass carries this exact timestamp, so the
        // prune at the end is an unambiguous "< passStartedAt" comparison.
        $passStartedAt = Carbon::now();
        $startedAtSeconds = microtime(true);

        $this->info('Sync started at '.$passStartedAt->toDateTimeString().' (top='.$top.')');

        try {
            $gameIds = $explicitGames !== []
                ? $explicitGames
                : $this->collectGameIds($api, $gameLimit);
        } catch (Throwable $e) {
            $this->error('Could not list games: '.$e->getMessage());

            return self::FAILURE;
        }

        $total = count($gameIds);
        $this->info('Walking '.$total.' game(s).');

        $written = 0;
        $skipped = 0;
        $failed = [];

        foreach ($gameIds as $index => $gameId) {
            try {
                [$gameWritten, $gameSkipped] = $this->syncGame($api, $gameId, $top, $chunkSize, $passStartedAt);
                $written += $gameWritten;
                $skipped += $gameSkipped;
            } catch (Throwable $e) {
                $failed[] = $gameId;
                $this->warn(sprintf('  ! %s failed: %s', $gameId, $e->getMessage()));

                continue;
            }

            if ($this->output->isVerbose() || ($index + 1) % 25 === 0 || $index + 1 === $total) {
                $this->line(sprintf(
                    '  [%d/%d] %s - %d rows written, %d runs skipped, %ds elapsed',
                    $index + 1,
                    $total,
                    $gameId,
                    $written,
                    $skipped,
                    (int) (microtime(true) - $startedAtSeconds),
                ));
            }
        }

        $partial = $explicitGames !== [] || $gameLimit !== null;
        $failureRate = $total > 0 ? count($failed) / $total : 0.0;

        $this->newLine();
        $this->info(sprintf(
            'Fetched %d rows across %d game(s) in %ds. %d run(s) skipped (no usable video), %d game(s) failed.',
            $written,
            $total,
            (int) (microtime(true) - $startedAtSeconds),
            $skipped,
            count($failed),
        ));

        $this->prune($passStartedAt, $partial, $failureRate);

        return $failed === [] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Page the bulk games listing. Held in memory, as the Python did - the ids
     * are ~9 bytes each and there is no games table in this schema.
     *
     * @return list<string>
     */
    private function collectGameIds(SpeedrunComApi $api, ?int $limit): array
    {
        $ids = [];
        $offset = 0;
        $pageSize = 1000;

        do {
            $page = $api->games($offset, $pageSize);
            $size = (int) data_get($page, 'pagination.size', 0);

            foreach ($page->data as $game) {
                if (! isset($game->id)) {
                    continue;
                }

                $ids[] = (string) $game->id;

                if ($limit !== null && count($ids) >= $limit) {
                    return $ids;
                }
            }

            $offset += $pageSize;
        } while ($size === $pageSize);

        return $ids;
    }

    /**
     * @return array{0: int, 1: int} [rows written, runs skipped]
     */
    private function syncGame(
        SpeedrunComApi $api,
        string $gameId,
        int $top,
        int $chunkSize,
        Carbon $passStartedAt,
    ): array {
        $offset = 0;
        $pageSize = 200;
        $written = 0;
        $skipped = 0;
        $buffer = [];

        do {
            $page = $api->gameRecords($gameId, $offset, $top, $pageSize);
            $size = (int) data_get($page, 'pagination.size', 0);

            foreach ($page->data as $leaderboard) {
                $row = Record::rowFromLeaderboard($leaderboard, $passStartedAt);

                if ($row === null) {
                    $skipped++;

                    continue;
                }

                // A run id can only appear once per pass, but a duplicate in a
                // single upsert batch is a hard Postgres error ("cannot affect
                // row a second time"), so key the buffer and let the last win.
                $buffer[$row[Record::NATURAL_KEY]] = $row;

                if (count($buffer) >= $chunkSize) {
                    $written += $this->flush($buffer);
                    $buffer = [];
                }
            }

            $offset += $pageSize;
        } while ($size === $pageSize);

        $written += $this->flush($buffer);

        return [$written, $skipped];
    }

    /**
     * @param  array<string, array<string, mixed>>  $buffer
     */
    private function flush(array $buffer): int
    {
        if ($buffer === []) {
            return 0;
        }

        Record::query()->upsert(
            array_values($buffer),
            [Record::NATURAL_KEY],
            Record::upsertColumns(),
        );

        return count($buffer);
    }

    /**
     * Delete anything this pass did not touch.
     *
     * This is the full-refresh guarantee the old table swap was reaching for.
     * The difference is that a crash halfway through leaves the table fully
     * populated - a mix of rows refreshed by this pass and rows still carrying
     * the previous pass's synced_at - rather than an orphaned half-filled
     * generation table and a view still pointing at yesterday's data.
     */
    private function prune(Carbon $passStartedAt, bool $partial, float $failureRate): void
    {
        $stale = fn () => Record::query()->where(function ($query) use ($passStartedAt) {
            $query->whereNull('synced_at')->orWhere('synced_at', '<', $passStartedAt);
        });

        $forced = (bool) $this->option('prune');

        if ($this->option('no-prune')) {
            $this->comment('Prune skipped: --no-prune. '.$stale()->count().' stale row(s) left in place.');

            return;
        }

        if ($partial && ! $forced) {
            $this->comment('Prune skipped: this was a partial walk (--games/--game). Pass --prune to force it.');

            return;
        }

        if ($failureRate > self::MAX_FAILURE_RATE && ! $forced) {
            $this->warn(sprintf(
                'Prune skipped: %.1f%% of games failed, above the %.0f%% ceiling. Pass --prune to force it.',
                $failureRate * 100,
                self::MAX_FAILURE_RATE * 100,
            ));

            return;
        }

        $deleted = $stale()->delete();

        $this->info('Pruned '.$deleted.' record(s) no longer present upstream.');
    }
}
