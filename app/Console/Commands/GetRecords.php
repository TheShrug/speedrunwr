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
		$records = [];
	    $games = Game::limit(10)->get();
        foreach($games as $game) {
        	$records = array_merge($records, Record::createRecordsFromSpeedrunComEndpoint($game->records));
        	$this->info('Created records for endpoint ' . $game->records);
        	$this->info('Count ' . count(array_filter($records, array($this, 'filterFalse'))));
        }




    }

	public function filterFalse($var) {
		return ($var !== false);
	}



}
