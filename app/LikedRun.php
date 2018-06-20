<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LikedRun extends Model
{
    //
	protected $fillable = [
		'runId', 'gameId', 'categoryId', 'levelId', 'userId', 'platformId', 'regionId', 'competition', 'primaryTime', 'date', 'youtubeId', 'twitchId'
	];

	public function users() {
		return $this->belongsToMany('App\User');
	}

}
