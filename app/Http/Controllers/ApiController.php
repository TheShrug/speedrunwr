<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use App\Game;
use App\Record;
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
        $runCompetition   = $request->query('runCompetition');

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
			    case(0) :
			    	$queryCompetitionMin = 0;
			    	$queryCompetitionMax = 3;
			    	break;
			    case(1) :
				    $queryCompetitionMin = 4;
				    $queryCompetitionMax = 10;
				    break;
			    case(2) :
				    $queryCompetitionMin = 11;
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


	    $recordQuery = $recordsQuery->toSql();

		$records = $recordsQuery->get();



    	//$records = Record::where('levelId', null)->get();
    	$record = $records->random();



		if($this->verifiedRecord($record)){
			return ['record' => $record, 'query' => $recordQuery];
		} else {
			$this->newRun($request);
		}

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
}
