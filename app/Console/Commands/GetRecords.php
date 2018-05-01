<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use App\Game;
use App\Record;
use Carbon\Carbon;

class GetRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'speedrunwr:getrecords';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get all new records from speedrun.com';

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

    	$interval = new \DateInterval('PT1H22M21.310S');

		$this->info($interval->format('%s seconds'));



    	exit;

        $games = Game::limit(200)->get();

        foreach($games as $game) {
        	getRecordsData($game->records);
        }

	    $this->info($games[199]);

    }

    public function getRecordsData($endpoint) {
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
