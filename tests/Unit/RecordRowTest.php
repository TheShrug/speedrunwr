<?php

namespace Tests\Unit;

use App\Record;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Covers the flattening of a `GET /games/{id}/records?embed=players`
 * leaderboard into a row, and in particular the null-video shapes that used to
 * fatal. The Python covered these with a bare `except`.
 */
class RecordRowTest extends TestCase
{
    private function leaderboard(array $overrides = [], array $runOverrides = []): object
    {
        $run = array_replace([
            'id' => 'z10owxrm',
            'game' => 'o1y9wo6q',
            'category' => 'zdnq4oqd',
            'level' => 'r9gzzkjd',
            'date' => '2026-03-19',
            'submitted' => '2026-03-19T23:30:06Z',
            'times' => ['primary_t' => 362.38],
            'system' => ['platform' => 'w89rwelk', 'region' => 'o316x197'],
            'players' => [['rel' => 'user', 'id' => '1xyd46wx']],
            'videos' => ['links' => [['uri' => 'https://youtu.be/iRfDtVFLoVQ']]],
        ], $runOverrides);

        $leaderboard = array_replace([
            'game' => 'o1y9wo6q',
            'category' => 'zdnq4oqd',
            'level' => 'r9gzzkjd',
            'players' => ['data' => [['id' => '1xyd46wx'], ['id' => 'other']]],
            'runs' => [['place' => 1, 'run' => $run]],
        ], $overrides);

        return json_decode(json_encode($leaderboard));
    }

    public function test_it_flattens_a_normal_leaderboard(): void
    {
        $syncedAt = Carbon::parse('2026-08-22 03:00:00');

        $row = Record::rowFromLeaderboard($this->leaderboard(), $syncedAt);

        $this->assertNotNull($row);
        $this->assertSame('z10owxrm', $row['runId']);
        $this->assertSame('o1y9wo6q', $row['gameId']);
        $this->assertSame('zdnq4oqd', $row['categoryId']);
        $this->assertSame('r9gzzkjd', $row['levelId']);
        $this->assertSame('1xyd46wx', $row['userId']);
        $this->assertSame('w89rwelk', $row['platformId']);
        $this->assertSame('o316x197', $row['regionId']);
        $this->assertSame(2, $row['competition']);
        $this->assertSame(362.38, $row['primaryTime']);
        $this->assertSame('iRfDtVFLoVQ', $row['youtubeId']);
        $this->assertNull($row['twitchId']);
        $this->assertSame('2026-03-19 00:00:00', $row['date']->toDateTimeString());
        $this->assertSame($syncedAt, $row['synced_at']);
    }

    public function test_a_full_game_leaderboard_has_a_null_level(): void
    {
        $row = Record::rowFromLeaderboard(
            $this->leaderboard(['level' => null], ['level' => null]),
            Carbon::now(),
        );

        $this->assertNotNull($row);
        $this->assertNull($row['levelId']);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function unusableVideos(): array
    {
        return [
            // speedrun.com returns this routinely.
            'videos is null' => [null],
            // A run whose only "proof" is a free-text note.
            'videos has text but no links' => [['text' => 'on my hard drive']],
            'links is an empty array' => [['links' => []]],
            'link has no uri' => [['links' => [[]]]],
            'uri is not a recognised host' => [['links' => [['uri' => 'https://example.com/x']]]],
        ];
    }

    /**
     * @param  mixed  $videos
     */
    #[DataProvider('unusableVideos')]
    public function test_it_rejects_runs_without_a_usable_video($videos): void
    {
        $leaderboard = $this->leaderboard([], ['videos' => $videos]);

        $this->assertFalse(Record::isValidSpeedrunComRun($leaderboard->runs[0]->run));
        $this->assertNull(Record::rowFromLeaderboard($leaderboard, Carbon::now()));
    }

    public function test_it_rejects_a_leaderboard_with_no_runs(): void
    {
        $this->assertNull(Record::rowFromLeaderboard($this->leaderboard(['runs' => []]), Carbon::now()));
    }

    public function test_it_falls_back_to_the_submission_timestamp(): void
    {
        $row = Record::rowFromLeaderboard(
            $this->leaderboard([], ['date' => null]),
            Carbon::now(),
        );

        $this->assertSame('2026-03-19 23:30:06', $row['date']->toDateTimeString());
    }

    public function test_a_run_with_no_date_at_all_stores_null(): void
    {
        $row = Record::rowFromLeaderboard(
            $this->leaderboard([], ['date' => null, 'submitted' => null]),
            Carbon::now(),
        );

        $this->assertNull($row['date']);
    }

    public function test_guest_players_are_recorded_as_guest(): void
    {
        $row = Record::rowFromLeaderboard(
            $this->leaderboard([], ['players' => [['rel' => 'guest', 'name' => 'Somebody']]]),
            Carbon::now(),
        );

        $this->assertSame('guest', $row['userId']);
    }

    public function test_a_twitch_run_stores_a_twitch_id_only(): void
    {
        $row = Record::rowFromLeaderboard(
            $this->leaderboard([], ['videos' => ['links' => [['uri' => 'https://www.twitch.tv/videos/123456789']]]]),
            Carbon::now(),
        );

        $this->assertSame('123456789', $row['twitchId']);
        $this->assertNull($row['youtubeId']);
    }

    public function test_the_upsert_key_is_never_null(): void
    {
        $this->assertSame('runId', Record::NATURAL_KEY);
        $this->assertNotContains(Record::NATURAL_KEY, Record::upsertColumns());
        $this->assertNotContains('created_at', Record::upsertColumns());
    }
}
