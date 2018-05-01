<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use App\Game;

class StoreGames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'speedrunwr:storegames';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Store Games from speedrun.com';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->storeGamesRecursive();
    }

	public function storeGamesRecursive($offset = null) {
		if(is_null($offset)) {
			$offset = 0;
		}

		$games = $this->getGames($offset);

		$paginationSize = $games->pagination->size;

		foreach($games->data as $gameInfo) {
			$currentGame = Game::where('speedrunId', $gameInfo->id)->first();
			if(!$currentGame) {
				$game = new Game();
			} else {
				$game = $currentGame;
			}
			$game->setFromSpeedrun($gameInfo);
			$game->save();
		}

		$offset = $offset + $paginationSize;
		$this->info('Offset currently: ' .$offset);

		if($paginationSize < 200) {
			$this->info('All finished!');
		} else {
			return $this->storeGamesRecursive($offset);
		}

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
