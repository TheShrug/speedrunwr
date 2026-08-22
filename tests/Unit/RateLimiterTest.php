<?php

namespace Tests\Unit;

use App\Support\RateLimiter;
use PHPUnit\Framework\TestCase;

class RateLimiterTest extends TestCase
{
    /**
     * Drive the limiter with a fake clock so the 99-requests-per-60-seconds
     * budget can be checked without spending a real minute.
     *
     * @return array{0: RateLimiter, 1: \Closure(): float}
     */
    private function limiter(int $limit, float $period): array
    {
        $now = 1_000.0;

        $clock = function () use (&$now): float {
            return $now;
        };

        $sleeper = function (float $seconds) use (&$now): void {
            $now += $seconds;
        };

        $reader = function () use (&$now): float {
            return $now;
        };

        return [new RateLimiter($limit, $period, $clock, $sleeper), $reader];
    }

    public function test_it_lets_a_full_budget_through_without_waiting(): void
    {
        [$limiter, $now] = $this->limiter(99, 60.0);
        $start = $now();

        for ($i = 0; $i < 99; $i++) {
            $this->assertSame(0.0, $limiter->acquire(), "request {$i} should not have waited");
        }

        $this->assertSame($start, $now(), 'no time should have passed');
        $this->assertSame(99, $limiter->used());
    }

    public function test_the_hundredth_request_waits_for_the_window_to_roll(): void
    {
        [$limiter, $now] = $this->limiter(99, 60.0);
        $start = $now();

        for ($i = 0; $i < 99; $i++) {
            $limiter->acquire();
        }

        $waited = $limiter->acquire();

        $this->assertSame(60.0, $waited);
        $this->assertSame($start + 60.0, $now());
    }

    public function test_it_never_exceeds_the_budget_over_a_long_run(): void
    {
        [$limiter, $now] = $this->limiter(99, 60.0);
        $start = $now();

        // 500 requests is a little over five windows' worth.
        for ($i = 0; $i < 500; $i++) {
            $limiter->acquire();
        }

        $elapsed = $now() - $start;

        // 500 requests at 99 per 60s cannot finish faster than four full
        // windows: the first 99 are free, then each subsequent 99 costs 60s.
        $this->assertGreaterThanOrEqual(4 * 60.0, $elapsed);
        $this->assertLessThanOrEqual(5 * 60.0, $elapsed);
        $this->assertLessThanOrEqual(99, $limiter->used());
    }

    public function test_requests_age_out_of_the_window(): void
    {
        [$limiter] = $this->limiter(2, 10.0);

        $limiter->acquire();
        $limiter->acquire();
        $this->assertSame(2, $limiter->used());

        // Third request has to wait out the oldest of the two.
        $this->assertSame(10.0, $limiter->acquire());
        $this->assertSame(1, $limiter->used());
    }

    public function test_it_rejects_a_nonsensical_limit(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new RateLimiter(0, 60.0);
    }
}
