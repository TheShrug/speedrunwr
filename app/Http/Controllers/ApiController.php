<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use App\Game;
use App\Record;

class ApiController extends Controller
{

    public function newRun() {
    	$records = Record::all();
    	$record = $records->random();

		if($this->verifiedRecord($record)){
			return ['record' => $record];
		} else {
			newRun();
		}

    }

    public function verifiedRecord($record) {
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

	    if($jsonResult->data->runs[0]->run->id == $runId){
	    	return true;
	    } else {
	    	return false;
	    }

    }

}
