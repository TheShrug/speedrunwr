<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use App\Record;
use App\LikedRun;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {



        return view('home');
    }

	public function run($vue_capture)
	{

		// One of my biggest regrets is not considering SEO/meta value early enough and using something like Nuxt
		// this is going to be a pretty hacky attempt to provide some OpenGraph meta

		$runId = $vue_capture;
		$run = $this->findRun($runId);
		if($run === false) {
			abort(404);
		}

		$runDetails = $this->getRunDetails($run);
		$runMeta = $this->generateRunMeta($runDetails);

		return view('home', ['meta' => $runMeta]);
	}

	/**
	 * Look for and return the run from storage. Returns false if not found.
	 *
	 * @param $runId
	 *
	 * @return bool
	 */
    public function findRun($runId) {
	    $record = Record::where('runId', $runId)->first();
	    if($record) {
		    return $record;
	    }

	    $likedRun = LikedRun::where('runId', $runId)->first();
	    if($likedRun) {
		   return $likedRun;
	    }

	    return false;

    }

    public function getRunDetails($run) {
	    $client = new Client();
	    $result = $client->request('GET','https://www.speedrun.com/api/v1/runs/' . $run->runId, [
		    'query'  => [
			    'embed' => 'game,category,level,players',
		    ]
	    ]);
	    return json_decode($result->getBody());
    }

    public function generateRunMeta($runDetails) {

		$details = [];
	    $details['game'] = '';
	    $details['category'] = '';
	    $details['level'] = '';
	    $details['description'] = '';
	    $details['player'] = '';
	    $details['image'] = '';
	    $details['icon'] = '';


    	if(isset($runDetails->data->game->data->names->international)) {
		    $details['game'] = $runDetails->data->game->data->names->international;
	    }

	    if(isset($runDetails->data->category->data->name)) {
		    $details['category'] = $runDetails->data->category->data->name;
	    }

	    if(isset($runDetails->data->level->data->name)) {
		    $details['level'] = $runDetails->data->level->data->name;
	    }

	    if(isset($runDetails->data->comment)) {
		    $details['description'] = $runDetails->data->comment;
	    }

	    if(isset($runDetails->data->players->data[0]->names->international)) {
		    $details['player'] = $runDetails->data->players->data[0]->names->international;
	    }

	    if(isset($runDetails->data->game->data->assets->{'cover-large'}->uri)) {
		    $details['image'] = $runDetails->data->game->data->assets->{'cover-large'}->uri;
	    }

	    if(isset($runDetails->data->game->data->assets->icon->uri)) {
		    $details['icon'] = $runDetails->data->game->data->assets->icon->uri;
	    }

	    $meta = [];
    	$meta['title'] = ($details['game']) ? $details['game'] : '';
    	$meta['title'] .= ($details['category']) ? ' ' . $details['category'] : '';
    	$meta['title'] .= ($details['level']) ? ' ' . $details['level'] : '';
    	$meta['title'] .= ' Speedrun World Record Video | Speedrun Web Randomizer';

    	if($details['description']) {
    		$meta['description'] = $details['description'];
	    } else {
		    $meta['description'] = 'Watch the ';
		    $meta['description'] .= ($details['game']) ? $details['game'] : '';
		    $meta['description'] .= ($details['category']) ? ' ' . $details['category'] : '';
		    $meta['description'] .= ($details['level']) ? ' ' . $details['level'] : '';
		    $meta['description'] .= ' speedrun world record';
		    $meta['description'] .= ($details['player']) ? ' by ' . $details['player'] : '';
		    $meta['description'] .= ' on Speedrun Web Randomizer.';
	    }

		$meta['image'] = $details['image'];
		$meta['icon'] = $details['icon'];

	    return $meta;

    }


}
