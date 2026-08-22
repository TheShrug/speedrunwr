<?php

namespace App;

/**
 * Pulls a Twitch VOD id or a YouTube video id out of a speedrun.com video URL.
 *
 * This is the PHP-side equivalent of the Python scraper's `valid_video_id()`.
 * Two differences from the Python were reconciled when the scraper was ported:
 *
 *  - Twitch: the Python matched both `.../v/123456` (legacy) and
 *    `.../videos/123456` (current) with a six-digit minimum. This class only
 *    matched a bare run of eight-or-more digits anywhere in the URL, which both
 *    missed short legacy ids and would happily pick digits out of a channel
 *    name. It now matches the same two shapes the Python did.
 *  - Twitch: `preg_match()` failing left `$videoId[0]` undefined, which is a
 *    fatal "Undefined array key 0" on PHP 8. A non-match now simply yields no id.
 *
 * Twitch is checked first and exclusively, as in the Python: a twitch.tv URL is
 * never also a YouTube URL, and the old order let a Twitch match clobber a
 * YouTube one.
 */
class VideoIdParser
{
    /**
     * `/v/123456789`, `/videos/123456789`, and the player form
     * `?video=v123456789`. Six digits minimum, matching the Python.
     */
    private const TWITCH = '#(?:^|[/?&=])v(?:ideos?)?[/=]?v?(\d{6,})#i';

    private const YOUTUBE = '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i';

    private ?string $id = null;

    private bool $isYoutubeVideo = false;

    private bool $isTwitchVideo = false;

    public function __construct(?string $videoUrl)
    {
        if (is_string($videoUrl) && $videoUrl !== '') {
            $this->processVideoUrl($videoUrl);
        }
    }

    private function processVideoUrl(string $videoUrl): void
    {
        if (stripos($videoUrl, 'twitch') !== false) {
            if (preg_match(self::TWITCH, $videoUrl, $match) === 1) {
                $this->id = $match[1];
                $this->isTwitchVideo = true;
            }

            return;
        }

        if (preg_match(self::YOUTUBE, $videoUrl, $match) === 1) {
            $this->id = $match[1];
            $this->isYoutubeVideo = true;
        }
    }

    public function isYoutube(): bool
    {
        return $this->isYoutubeVideo;
    }

    public function isTwitch(): bool
    {
        return $this->isTwitchVideo;
    }

    public function getId(): ?string
    {
        return $this->id;
    }
}
