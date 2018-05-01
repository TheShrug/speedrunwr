<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use App\Game;

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

}
