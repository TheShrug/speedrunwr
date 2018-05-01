<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

class Record extends Model
{
    public function getRecordsFromSpeedrunComEndpoint($endpoint) {
	    $client = new Client();
	    $result = $client->request('GET', $endpoint, [
		    'query'  => [
			    'top' => 1
		    ]
	    ]);

	    $runs = json_decode($result->getBody());

	    foreach($runs as $run) {
		    $this->createRecordFromSpeedrunComRun($run);
	    }




    }

    public function createRecordFromSpeedrunComRun($run) {
		if(!$this->isValidSpeedrunComRun()) {
			return false;
		}
    }

	public function isValidSpeedrunComRun($run) {
		if(!$run->videos->links[0]->uri) {
			return false;
		}
		


	}



}
