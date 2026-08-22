<?php

namespace App\Support;

/**
 * Sliding-window rate limiter.
 *
 * The Python scraper this replaces used a `RatedSemaphore(99, 60)` — a token
 * bucket refilled at 99 tokens per 60 seconds — to stay inside speedrun.com's
 * published API limit of 100 requests per minute. This is the same budget
 * expressed as a sliding window, which is never more permissive than the
 * bucket: it simply refuses to let a 61st... 100th request happen if 99 have
 * already gone out within the trailing 60 seconds.
 *
 * The clock and the sleep are injectable so the behaviour can be tested
 * without spending a real minute.
 */
class RateLimiter
{
    /** @var list<float> Monotonic-ish timestamps of permitted requests, oldest first. */
    private array $window = [];

    /** @var \Closure(): float */
    private $clock;

    /** @var \Closure(float): void */
    private $sleeper;

    public function __construct(
        private int $limit = 99,
        private float $period = 60.0,
        ?callable $clock = null,
        ?callable $sleeper = null,
    ) {
        if ($limit < 1) {
            throw new \InvalidArgumentException('Rate limit must be at least 1 request per period.');
        }

        $this->clock = $clock ? \Closure::fromCallable($clock) : static fn (): float => microtime(true);
        $this->sleeper = $sleeper
            ? \Closure::fromCallable($sleeper)
            : static function (float $seconds): void {
                if ($seconds > 0) {
                    usleep((int) ceil($seconds * 1_000_000));
                }
            };
    }

    /**
     * Block until another request is allowed, then record it.
     *
     * @return float Seconds actually spent waiting.
     */
    public function acquire(): float
    {
        $waited = 0.0;

        while (true) {
            $now = ($this->clock)();
            $this->prune($now);

            if (count($this->window) < $this->limit) {
                $this->window[] = $now;

                return $waited;
            }

            // The window is full; wait until the oldest entry ages out of it.
            $sleep = ($this->window[0] + $this->period) - $now;
            if ($sleep <= 0) {
                continue;
            }

            ($this->sleeper)($sleep);
            $waited += $sleep;
        }
    }

    /** Requests currently counted inside the trailing window. */
    public function used(): int
    {
        $this->prune(($this->clock)());

        return count($this->window);
    }

    private function prune(float $now): void
    {
        $cutoff = $now - $this->period;

        while ($this->window !== [] && $this->window[0] <= $cutoff) {
            array_shift($this->window);
        }
    }
}
