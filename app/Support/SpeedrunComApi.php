<?php

namespace App\Support;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

/**
 * Thin, rate-limited client for the parts of the speedrun.com v1 API the
 * sync command needs.
 *
 * Every outbound call goes through one RateLimiter and one Guzzle client —
 * unlike the Python scraper, which opened a fresh database connection per row
 * and had no shared HTTP client at all.
 */
class SpeedrunComApi
{
    public const BASE_URI = 'https://www.speedrun.com/api/v1/';

    /** speedrun.com's "Enhance Your Calm" throttle response. */
    private const THROTTLED = 420;

    private Client $client;

    public function __construct(
        private RateLimiter $limiter,
        ?Client $client = null,
        private int $retries = 3,
        private float $retryBaseDelay = 2.0,
    ) {
        $this->client = $client ?: new Client([
            'base_uri' => self::BASE_URI,
            'timeout' => 60,
            'connect_timeout' => 10,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'speedrunwr/1.0 (+https://speedrunwr.com)',
            ],
        ]);
    }

    /**
     * One page of the bulk games listing.
     *
     * `_bulk=yes` trades the full game object for id/names/abbreviation/weblink
     * and in exchange allows max=1000 instead of max=200. The sync only needs
     * the id, so bulk mode is ~40 requests for the whole site instead of ~200.
     *
     * @return object{data: array<int, object>, pagination: object}
     */
    public function games(int $offset, int $max = 1000): object
    {
        return $this->getJson('games', [
            '_bulk' => 'yes',
            'max' => $max,
            'offset' => $offset,
        ]);
    }

    /**
     * One page of a game's leaderboards, each embedding its top runs' players.
     *
     * @return object{data: array<int, object>, pagination: object}
     */
    public function gameRecords(string $gameId, int $offset, int $top, int $max = 200): object
    {
        return $this->getJson('games/'.rawurlencode($gameId).'/records', [
            'top' => $top,
            'embed' => 'players',
            'max' => $max,
            'offset' => $offset,
        ]);
    }

    /**
     * @param  array<string, scalar>  $query
     *
     * @throws GuzzleException
     */
    private function getJson(string $path, array $query): object
    {
        $response = $this->request($path, $query);

        $decoded = json_decode((string) $response->getBody());

        if (! is_object($decoded)) {
            throw new \RuntimeException("speedrun.com returned a non-object body for {$path}");
        }

        return $decoded;
    }

    /**
     * @param  array<string, scalar>  $query
     *
     * @throws GuzzleException
     */
    private function request(string $path, array $query): ResponseInterface
    {
        $attempt = 0;

        while (true) {
            $attempt++;
            $this->limiter->acquire();

            try {
                $response = $this->client->request('GET', $path, [
                    'query' => $query,
                    'http_errors' => true,
                ]);

                return $response;
            } catch (GuzzleException $e) {
                $status = method_exists($e, 'getResponse') && $e->getResponse()
                    ? $e->getResponse()->getStatusCode()
                    : 0;

                // 4xx other than the throttle response is a permanent answer
                // (404 for a game that has since been deleted, for instance).
                $retryable = $status === 0 || $status >= 500 || $status === self::THROTTLED;

                if (! $retryable || $attempt > $this->retries) {
                    throw $e;
                }

                // Back off harder for the throttle response than for a 5xx.
                $delay = $this->retryBaseDelay * (2 ** ($attempt - 1));
                if ($status === self::THROTTLED) {
                    $delay = max($delay, 30.0);
                }

                usleep((int) ceil($delay * 1_000_000));
            }
        }
    }
}
