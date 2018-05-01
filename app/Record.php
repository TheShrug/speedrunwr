<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use App\VideoIdParser;

class Record extends Model
{
    public static function createRecordsFromSpeedrunComEndpoint($endpoint) {

    	$records = array();

	    $client = new Client();
	    $result = $client->request('GET', $endpoint, [
		    'query'  => [
			    'top' => 1,
			    'embed' => 'players'

		    ]
	    ]);

	    $recordCategories = json_decode($result->getBody());

	    foreach($recordCategories->data as $recordCategory) {
	    	if(isset($recordCategory->runs[0]->run)) {
			    $records[] = self::createRecordFromSpeedrunComRun($recordCategory->runs[0]->run);

		    }
	    }
		return $records;



    }

    public static function createRecordFromSpeedrunComRun($run) {
		if(!self::isValidSpeedrunComRun($run)) {
			return false;
		}
		$currentRecord = Record::where('gameId', $run->game)->where('categoryId', $run->category)->first();

		if($currentRecord) {
			$record = $currentRecord;
		} else {
			$record = new Record();
		}



		$videoIdParser = new VideoIdParser($run->videos->links[0]->uri);

		if($videoIdParser->isTwitch()) {
			$record->hasTwitch = 1;
			$record->hasYoutube = 0;
			$record->twitchId = $videoIdParser->getId();
		}

		if($videoIdParser->isYoutube()) {
			$record->hasYoutube = 1;
			$record->hasTwitch = 0;
			$record->youtubeId = $videoIdParser->getId();
		}

	    $record->runId      = $run->id;
		$record->gameId     = $run->game;
		$record->categoryId = $run->category;

		// players can be guests...
	    if($run->players[0]->rel == 'guest') {
			$record->userId = 'guest';
	    } else {
		    $record->userId     = $run->players[0]->id;
	    }


		$record->primaryTime = $run->times->primary_t;
		$record->date = $run->date;
		$record->level = $run->level;

		$record->save();

		return $record;

    }

	public static function isValidSpeedrunComRun($run) {

		if(!$run->videos->links[0]->uri) {
			return false;
		} else {
			$videoIdParser = new VideoIdParser($run->videos->links[0]->uri);
			if(!$videoIdParser->getId()) {
				return false;
			}
		}

		return true;

	}





}
