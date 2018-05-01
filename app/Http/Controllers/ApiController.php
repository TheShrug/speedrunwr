<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use App\Game;
use App\Record;

class ApiController extends Controller
{
    public function games() {
		$client = new Client();
		$result = $client->request('GET','https://www.speedrun.com/api/v1/games', [
			'query'  => [
				'max' => 200,
				'embed' => 'categories,platforms',
				'offset' => 200
			],
			'delay' => 600
		]);
		return $result->getBody();
    }

    public function newRun() {
    	//$randomGame = Game::inRandomOrder()->get()->first();
	    $randomGameId = rand(1, Game::max('id'));
		$randomGame = Game::findOrFail($randomGameId);
		return ['test' => $randomGame];
    }

    public function gameCount() {
	    $gameCount = Game::all()->count();
	    return ['count' => $gameCount];
    }

	public function getGames($offset) {
		$client = new Client();
		$result = $client->request('GET','https://www.speedrun.com/api/v1/games', [
			'query'  => [
				'max' => 200,
				'offset' => $offset
			]
		]);
		return json_decode($result->getBody());
	}

	public function test() {
		$records = [];
		$games = Game::limit(10)->get();
		foreach($games as $game) {
			$records = array_merge($records, Record::createRecordsFromSpeedrunComEndpoint($game->records));
			echo 'Created records for endpoint ' . $game->records;
			echo 'Count ' . count(array_filter($records, array($this, 'filterFalse')));
		}

		//foreach($records as $record) {
			var_dump($records);
		//}

	}

	public function filterFalse($var) {
		return ($var !== false);
	}
}
