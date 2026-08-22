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
     * Each attempt is one outbound HTTP request, so this is the request's
     * worst-case latency budget as much as it is a bound on the search.
     */
    private const VERIFICATION_ATTEMPTS = 5;

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

		    if($levelId) {
			    $client = new Client();
			    $result = $client->request('GET','https://www.speedrun.com/api/v1/leaderboards/' . $gameId . '/level/' . $levelId . '/' . $categoryId, [
				    'query'  => [
					    'top' => 1,
				    ]
			    ]);
		    } else {
			    $client = new Client();
			    $result = $client->request('GET','https://www.speedrun.com/api/v1/leaderboards/' . $gameId . '/category/' . $categoryId, [
				    'query'  => [
					    'top' => 1,
				    ]
			    ]);
		    }

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
