<?php

namespace Tests\Unit;

use App\Http\Controllers\ApiController;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * VERIFICATION_ATTEMPTS only bounds the request's latency if each attempt is
 * itself bounded. Guzzle's defaults are timeout => 0 and connect_timeout => 0,
 * so the interesting assertion is on the options the request actually carries,
 * not on the happy path.
 */
class VerifiedRecordTimeoutTest extends TestCase
{
    /** @var array<int, array<string, mixed>> */
    private array $transactions = [];

    private function controller(array $responses): ApiController
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->transactions));

        $controller = new ApiController();
        $controller->setVerificationHandler($stack);

        return $controller;
    }

    private function leaderboard(string $topRunId): Response
    {
        return new Response(200, [], json_encode([
            'data' => ['runs' => [['run' => ['id' => $topRunId]]]],
        ]));
    }

    private function record(?string $levelId = null): array
    {
        return [
            'runId' => 'run-1',
            'gameId' => 'game-1',
            'categoryId' => 'cat-1',
            'levelId' => $levelId,
        ];
    }

    public function test_verification_request_carries_both_timeouts(): void
    {
        $controller = $this->controller([$this->leaderboard('run-1')]);

        $this->assertTrue($controller->verifiedRecord($this->record()));

        $this->assertCount(1, $this->transactions);
        $options = $this->transactions[0]['options'];

        $this->assertSame(2.0, $options['connect_timeout']);
        $this->assertSame(3.0, $options['timeout']);
    }

    /**
     * 5 attempts x (2s connect + 3s transfer) = 25s worst case, under PHP's
     * 30s max_execution_time. The pessimistic reading of the arithmetic is the
     * one that has to fit, so hold the numbers still.
     */
    public function test_five_attempts_stay_under_max_execution_time(): void
    {
        $controller = $this->controller([$this->leaderboard('run-1')]);
        $controller->verifiedRecord($this->record());

        $options = $this->transactions[0]['options'];
        $perAttempt = $options['connect_timeout'] + $options['timeout'];

        $this->assertLessThan(30.0, $perAttempt * 5);
    }

    public function test_level_and_category_runs_hit_different_urls_with_the_same_options(): void
    {
        $controller = $this->controller([
            $this->leaderboard('run-1'),
            $this->leaderboard('run-1'),
        ]);

        $controller->verifiedRecord($this->record('lvl-1'));
        $controller->verifiedRecord($this->record());

        $this->assertCount(2, $this->transactions);

        $this->assertSame(
            'https://www.speedrun.com/api/v1/leaderboards/game-1/level/lvl-1/cat-1',
            (string) $this->transactions[0]['request']->getUri()->withQuery('')
        );
        $this->assertSame(
            'https://www.speedrun.com/api/v1/leaderboards/game-1/category/cat-1',
            (string) $this->transactions[1]['request']->getUri()->withQuery('')
        );

        foreach($this->transactions as $transaction) {
            $this->assertSame('top=1', $transaction['request']->getUri()->getQuery());
            $this->assertSame(2.0, $transaction['options']['connect_timeout']);
            $this->assertSame(3.0, $transaction['options']['timeout']);
        }
    }

    public function test_a_stale_record_is_not_verified(): void
    {
        $controller = $this->controller([$this->leaderboard('someone-elses-run')]);

        $this->assertFalse($controller->verifiedRecord($this->record()));
    }

    /**
     * A timed-out attempt has to come back as "not verified" so newRun() moves
     * on to the next candidate, rather than escaping as a 500.
     */
    public function test_a_transport_failure_is_swallowed(): void
    {
        $controller = $this->controller([
            new \GuzzleHttp\Exception\ConnectException(
                'cURL error 28: Operation timed out',
                new \GuzzleHttp\Psr7\Request('GET', 'https://www.speedrun.com/')
            ),
        ]);

        $this->assertFalse($controller->verifiedRecord($this->record()));
    }
}
