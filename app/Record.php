<?php

namespace App;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Record extends Model
{
    /**
     * One physical table. There is no `vw_records` view any more — it only ever
     * existed because the Python scraper ran `CREATE OR REPLACE VIEW` at the end
     * of each pass, and no migration in this repo created it.
     */
    protected $table = 'records';

    protected $guarded = [];

    /**
     * The natural key the sync upserts on.
     *
     * A speedrun.com run id identifies a run globally, is never null, and does
     * not move if a run gets recategorised. Verified against the production
     * dump: 271,559 rows, 271,559 distinct `runId`.
     */
    public const NATURAL_KEY = 'runId';

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'synced_at' => 'datetime',
            'primaryTime' => 'float',
            'competition' => 'integer',
        ];
    }

    /**
     * The first video URI attached to a run, or null.
     *
     * speedrun.com returns `"videos": null` routinely, and also returns a
     * `videos` object carrying only a free-text `text` field with no `links`
     * array at all. Both used to fatal here.
     */
    public static function primaryVideoUri(?object $run): ?string
    {
        $uri = data_get($run, 'videos.links.0.uri');

        return is_string($uri) && $uri !== '' ? $uri : null;
    }

    /**
     * A run is usable to us only if it has a video we can recognise and embed.
     */
    public static function isValidSpeedrunComRun(?object $run): bool
    {
        if (! is_object($run) || ! isset($run->id)) {
            return false;
        }

        $uri = self::primaryVideoUri($run);

        if ($uri === null) {
            return false;
        }

        return (new VideoIdParser($uri))->getId() !== null;
    }

    /**
     * Flatten one leaderboard from `GET /games/{id}/records?embed=players` into
     * a row ready for `upsert()`. Returns null when the leaderboard has no
     * usable run.
     *
     * @return array<string, mixed>|null
     */
    public static function rowFromLeaderboard(object $leaderboard, CarbonInterface $syncedAt): ?array
    {
        $run = data_get($leaderboard, 'runs.0.run');

        if (! self::isValidSpeedrunComRun($run)) {
            return null;
        }

        $parser = new VideoIdParser(self::primaryVideoUri($run));
        $now = Carbon::now();

        return [
            'runId' => (string) $run->id,
            'gameId' => data_get($run, 'game') ?? data_get($leaderboard, 'game'),
            'categoryId' => data_get($run, 'category') ?? data_get($leaderboard, 'category'),
            'levelId' => data_get($run, 'level') ?? data_get($leaderboard, 'level'),
            'userId' => self::playerId($run),
            'platformId' => data_get($run, 'system.platform'),
            'regionId' => data_get($run, 'system.region'),
            'competition' => count((array) data_get($leaderboard, 'players.data', [])),
            'primaryTime' => data_get($run, 'times.primary_t'),
            'date' => self::runDate($run),
            'youtubeId' => $parser->isYoutube() ? $parser->getId() : null,
            'twitchId' => $parser->isTwitch() ? $parser->getId() : null,
            'synced_at' => $syncedAt,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * Columns `upsert()` should overwrite when the run is already stored.
     * Everything except the key itself and `created_at`.
     *
     * @return list<string>
     */
    public static function upsertColumns(): array
    {
        return [
            'gameId', 'categoryId', 'levelId', 'userId', 'platformId', 'regionId',
            'competition', 'primaryTime', 'date', 'youtubeId', 'twitchId',
            'synced_at', 'updated_at',
        ];
    }

    private static function playerId(object $run): ?string
    {
        $player = data_get($run, 'players.0');

        if (! is_object($player)) {
            return null;
        }

        // Guests have no user id, only a display name.
        if (($player->rel ?? null) === 'guest') {
            return 'guest';
        }

        return isset($player->id) ? (string) $player->id : null;
    }

    /**
     * Run date, falling back to the submission timestamp — the same order the
     * Python used, without its bare `except`.
     */
    private static function runDate(object $run): ?Carbon
    {
        foreach (['date' => 'Y-m-d', 'submitted' => 'Y-m-d\TH:i:s\Z'] as $field => $format) {
            $value = data_get($run, $field);

            if (! is_string($value) || $value === '') {
                continue;
            }

            try {
                // Carbon throws on a malformed value rather than returning false.
                $parsed = Carbon::createFromFormat($format, $value);
            } catch (\Throwable) {
                continue;
            }

            if ($parsed instanceof Carbon) {
                return $field === 'date' ? $parsed->startOfDay() : $parsed;
            }
        }

        return null;
    }
}
