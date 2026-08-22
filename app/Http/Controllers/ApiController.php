<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use App\Record;
use App\LikedRun;
use App\EasterEgg;

class ApiController extends Controller
{

    /**
     * How many candidate records newRun() will check against speedrun.com
     * before giving up.
     *
     * Each attempt is one outbound HTTP request, and that request is bounded
     * by VERIFY_CONNECT_TIMEOUT / VERIFY_TIMEOUT below, so this really is the
     * request's worst-case latency budget as much as it is a bound on the
     * search.
     */
    private const VERIFICATION_ATTEMPTS = 5;

    /**
     * Wall-clock bounds for one verification call to speedrun.com. Guzzle
     * defaults both of these to 0, i.e. no timeout at all, which is how five
     * bounded attempts could still run until max_execution_time.
     *
     * The arithmetic: `timeout` is curl's total-transfer bound and already
     * covers the connect phase, so the true ceiling is
     * VERIFICATION_ATTEMPTS * VERIFY_TIMEOUT = 5 * 3s = 15s. Reading the two
     * as additive — the pessimistic version the ticket asks for — gives
     * 5 * (2s + 3s) = 25s. Both sit under PHP's max_execution_time of 30s,
     * which is the 500 that issue #7 set out to kill.
     *
     * One leaderboard lookup with top=1 is a small response, so 3s of transfer
     * is generous; 2s to connect leaves room for DNS plus a TLS handshake to a
     * host that is not always nearby.
     */
    private const VERIFY_CONNECT_TIMEOUT = 2.0;
    private const VERIFY_TIMEOUT = 3.0;

    /**
     * Guzzle handler for the verification client. Left null in production so
     * Guzzle picks its own; a test sets it to a MockHandler stack to exercise
     * verifiedRecord() without touching speedrun.com.
     *
     * @var callable|null
     */
    private $verificationHandler = null;

    private ?Client $verificationClient = null;

    public function setVerificationHandler(callable $handler): void
    {
	    $this->verificationHandler = $handler;
	    $this->verificationClient = null;
    }

    /**
     * The client both branches of verifiedRecord() share. Built once: the two
     * branches only ever differed in the URL.
     */
    private function verificationClient(): Client
    {
	    if($this->verificationClient === null) {
		    $config = [
			    'connect_timeout' => self::VERIFY_CONNECT_TIMEOUT,
			    'timeout'         => self::VERIFY_TIMEOUT,
		    ];

		    if($this->verificationHandler !== null) {
			    $config['handler'] = $this->verificationHandler;
		    }

		    $this->verificationClient = new Client($config);
	    }

	    return $this->verificationClient;
    }

    public function newRun(Request $request) {

	    $videoType      = $request->query('videoType');
	    $includeLevels  = $request->query('includeLevels');
        $beforeDate     = $request->query('beforeDate');
        $afterDate      = $request->query('afterDate');
        $minRunLength   = $request->query('minRunLength');
        $maxRunLength   = $request->query('maxRunLength');
        $runCompetition = $request->query('runCompetition');
        $platform       = $request->query('platform');

	    $recordsQuery = Record::query();
	    if($videoType == 1) {
		    $recordsQuery->whereNotNull('twitchId');
	    } else if($videoType == 2) {
		    $recordsQuery->whereNotNull('youtubeId');
	    }

	    if($includeLevels == 'false') {
	    	$recordsQuery->whereNull('levelId');
	    }

	    if($beforeDate) {
	    	$beforeDateFormatted = date('Y-m-d H:i:s', strtotime($beforeDate));
	    	$recordsQuery->where('date', '<', $beforeDateFormatted);
	    }
	    if($afterDate) {
		    $afterDateFormatted = date('Y-m-d H:i:s', strtotime($afterDate));
		    $recordsQuery->where('date', '>', $afterDateFormatted);
	    }

	    if($minRunLength) {
	    	$recordsQuery->where('primaryTime', '>=', $minRunLength * 60);
	    }
	    if($maxRunLength) {
	    	$recordsQuery->where('primaryTime', '<=', $maxRunLength * 60);
	    }

	    if($runCompetition) {
		    $queryCompetitionMin = 0;
		    $queryCompetitionMax = 0;
		    switch ($runCompetition) {
			    case(1) :
				    $queryCompetitionMin = 0;
				    $queryCompetitionMax = 5;
				    break;
			    case(2) :
				    $queryCompetitionMin = 6;
				    $queryCompetitionMax = 20;
				    break;
			    case(3) :
				    $queryCompetitionMin = 21;
				    $queryCompetitionMax = 9999;
				    break;
		    }
		    $recordsQuery->where('competition', '>=', $queryCompetitionMin);
		    $recordsQuery->where('competition', '<=', $queryCompetitionMax);

	    }

	    if($platform) {
	    	$recordsQuery->whereIn('platformId', $platform);
	    }


		// Let the database draw the random sample. The whole matching set is
		// never hydrated, and the loop below cannot cost more than
		// VERIFICATION_ATTEMPTS calls out to speedrun.com.
		//
		// This is ORDER BY RANDOM() LIMIT 5, which Postgres cannot push the
		// limit into: it seq-scans the matching set and top-N heapsorts it.
		// Measured before leaving it alone (issue #10) on Postgres 16 with
		// 275,000 synthetic rows at production width — 39 MB heap, 143 bytes
		// avg per row, the production scale of ~275k:
		//
		//   unfiltered, warm, ten runs: 98-125 ms, median ~103 ms
		//   whereNotNull('twitchId') + a date bound over 52k matches: ~34-42 ms
		//
		// Under the 150 ms bar, so it stays. It is linear in the size of the
		// matching set, though, and it is warm-cache linear only while the
		// heap fits in shared_buffers — recheck if records grows well past
		// 275k or the row gets wider.

		$records = $recordsQuery->inRandomOrder()->limit(self::VERIFICATION_ATTEMPTS)->get();

		if(count($records) < 1) {
			return response(['message' => 'no runs found'], 404);
		}

		foreach($records as $record) {
			if($this->verifiedRecord($record)) {
				return ['record' => $record];
			}
		}

		// Candidates exhausted: they are all stale on speedrun.com, or the API
		// is unreachable. Same response as no candidates at all — rather than
		// the null this used to return, or the recursion it used to spin in.
		return response(['message' => 'no runs found'], 404);

    }

    public function findRun(Request $request) {

    	$runId = $request->query('runId');

    	$record = Record::where('runId', $runId)->first();
    	if($record) {
    		return response()->json($record);
	    }

	    $likedRun = LikedRun::where('runId', $runId)->first();
		if($likedRun) {
			return response()->json($likedRun);
		}

    	return response()->json(['message' => 'No Run Found'], 404);

    }



    public function verifiedRecord($record) {
    	try {
		    $gameId = $record['gameId'];
		    $categoryId = $record['categoryId'];
		    $runId = $record['runId'];
		    $levelId = $record['levelId'];

		    // The two cases differ only in the URL, so build the client once.
		    if($levelId) {
			    $url = 'https://www.speedrun.com/api/v1/leaderboards/' . $gameId . '/level/' . $levelId . '/' . $categoryId;
		    } else {
			    $url = 'https://www.speedrun.com/api/v1/leaderboards/' . $gameId . '/category/' . $categoryId;
		    }

		    $result = $this->verificationClient()->request('GET', $url, [
			    'query'  => [
				    'top' => 1,
			    ]
		    ]);

		    $jsonResult = json_decode($result->getBody());
			// TODO : update entry with new values here
		    if($jsonResult->data->runs[0]->run->id == $runId){
			    return true;
		    } else {
			    return false;
		    }
	    } catch(\Exception $e) {
	    	// Guzzle's transfer and HTTP-status exceptions all extend
	    	// \Exception. An import at the top of this file used to point the
	    	// bare `Exception` here at a require-dev class instead, which does
	    	// not exist under --no-dev, so the catch never fired in production.
    		return false;
	    }


    }

    public function easterEgg(Request $request) {
	    $ip = $request->ip();
	    $time = $request->params['time'];

    	$easterEgg = Easteregg::updateOrCreate(
    	    ['ip' => $ip],
	        ['time' => $time]
	    );

    	$placesBefore = EasterEgg::where('time', '<', $time)->orderBy('time')->get();
    	$place = count($placesBefore) + 1;

    	return response()->json([
    		'place' => $place
	    ]);

    }
}
