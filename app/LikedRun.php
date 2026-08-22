<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LikedRun extends Model
{
    //
	protected $fillable = [
		'runId', 'gameId', 'categoryId', 'levelId', 'userId', 'platformId', 'regionId', 'competition', 'primaryTime', 'date', 'youtubeId', 'twitchId'
	];

	protected function casts(): array
	{
		return [
			'date' => 'datetime',
			'primaryTime' => 'float',
			'competition' => 'integer',
		];
	}

	public function users() {
		return $this->belongsToMany('App\User');
	}

}
