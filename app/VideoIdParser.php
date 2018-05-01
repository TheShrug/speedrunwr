<?php

namespace App;

use Mockery\Exception;

class VideoIdParser {

	private $id = null;
	private $isYoutubeVideo;
	private $isTwitchVideo;



	function __construct($videoUrl) {

		$this->processVideoUrl($videoUrl);

	}

	/**
	 * Process a video url for twitch or youtube ids
	 *
	 * @param $videoUrl
	 */
	private function processVideoUrl($videoUrl) {


		if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $videoUrl, $match)) {
			$this->id = $match[1];
			$this->isYoutubeVideo = true;

		}

		if(strpos($videoUrl, 'twitch') !== false) {
			preg_match('/\d{8,}/', $videoUrl, $videoId);
			$this->id = $videoId[0];
			$this->isTwitchVideo = true;
		}

	}

	public function isYoutube() {
		return $this->isYoutubeVideo;
	}
	public function isTwitch() {
		return $this->isTwitchVideo;
	}
	public function getId() {
		return $this->id;
	}


}