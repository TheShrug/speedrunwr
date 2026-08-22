<?php

namespace Tests\Feature;

use App\Record;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The regression this whole change exists to prevent: a second sync pass must
 * update the rows already there rather than adding a second copy of everything.
 */
class RecordUpsertTest extends TestCase
{
    use RefreshDatabase;

    private function row(string $runId, Carbon $syncedAt, float $time = 100.0): array
    {
        $now = Carbon::now();

        return [
            'runId' => $runId,
            'gameId' => 'g1',
            'categoryId' => 'c1',
            'levelId' => null,
            'userId' => 'u1',
            'platformId' => 'p1',
            'regionId' => null,
            'competition' => 3,
            'primaryTime' => $time,
            'date' => $now,
            'youtubeId' => 'aaaaaaaaaaa',
            'twitchId' => null,
            'synced_at' => $syncedAt,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function upsert(array $rows): void
    {
        Record::query()->upsert($rows, [Record::NATURAL_KEY], Record::upsertColumns());
    }

    public function test_a_second_pass_updates_in_place(): void
    {
        $first = Carbon::parse('2026-08-21 03:00:00');
        $second = Carbon::parse('2026-08-22 03:00:00');

        $this->upsert([$this->row('r1', $first, 100.0), $this->row('r2', $first, 200.0)]);
        $this->assertSame(2, Record::count());

        $originalIds = Record::orderBy('runId')->pluck('id', 'runId')->all();

        $this->upsert([$this->row('r1', $second, 99.0), $this->row('r2', $second, 200.0)]);

        $this->assertSame(2, Record::count(), 'the second pass must not duplicate rows');
        $this->assertSame($originalIds, Record::orderBy('runId')->pluck('id', 'runId')->all());
        $this->assertSame(99.0, (float) Record::where('runId', 'r1')->value('primaryTime'));
        $this->assertSame(
            $second->toDateTimeString(),
            Record::where('runId', 'r1')->value('synced_at')->toDateTimeString(),
        );
    }

    public function test_pruning_removes_only_rows_the_pass_did_not_touch(): void
    {
        $first = Carbon::parse('2026-08-21 03:00:00');
        $second = Carbon::parse('2026-08-22 03:00:00');

        $this->upsert([$this->row('keep', $first), $this->row('gone', $first)]);

        // A row predating the synced_at column at all.
        $this->upsert([$this->row('legacy', $first)]);
        Record::where('runId', 'legacy')->update(['synced_at' => null]);

        // Second pass only sees 'keep'.
        $this->upsert([$this->row('keep', $second)]);

        $deleted = Record::query()
            ->where(fn ($q) => $q->whereNull('synced_at')->orWhere('synced_at', '<', $second))
            ->delete();

        $this->assertSame(2, $deleted);
        $this->assertSame(['keep'], Record::pluck('runId')->all());
    }

    public function test_the_run_id_unique_index_exists(): void
    {
        $this->upsert([$this->row('dupe', Carbon::now())]);

        $this->expectException(UniqueConstraintViolationException::class);

        Record::query()->insert($this->row('dupe', Carbon::now()));
    }

    public function test_the_records_view_is_gone_and_the_model_reads_the_table(): void
    {
        $this->assertSame('records', (new Record)->getTable());
    }
}
