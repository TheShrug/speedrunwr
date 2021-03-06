<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use App\Record;
use App\LikedRun;
use App\EasterEgg;
use Mockery\Exception;

class ApiController extends Controller
{

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


	    $recordQuery = $recordsQuery->toSql();

		$records = $recordsQuery->get();

		if(count($records) < 1) {
			return response(['message' => 'no runs found'], 404);
		}

    	//$records = Record::where('levelId', null)->get();
    	$record = $records->random();



		if($this->verifiedRecord($record)){
			return ['record' => $record];
		} else {
			$this->newRun($request);
		}

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
	    } catch(Exception $e) {
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
