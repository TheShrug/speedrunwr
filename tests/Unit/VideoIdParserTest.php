<?php

namespace Tests\Unit;

use App\VideoIdParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VideoIdParserTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function twitchUrls(): array
    {
        return [
            'current /videos/ form' => ['https://www.twitch.tv/videos/123456789', '123456789'],
            'legacy /v/ form' => ['https://www.twitch.tv/somechannel/v/123456789', '123456789'],
            // Six digits is the minimum the Python accepted; the old PHP
            // required eight and silently dropped these.
            'six digit legacy id' => ['https://www.twitch.tv/somechannel/v/123456', '123456'],
            'six digit videos id' => ['https://www.twitch.tv/videos/123456', '123456'],
            'with query string' => ['https://www.twitch.tv/videos/987654321?t=01h02m03s', '987654321'],
            'player embed form' => ['https://player.twitch.tv/?video=v123456789', '123456789'],
            'http and no www' => ['http://twitch.tv/videos/456789123', '456789123'],
        ];
    }

    #[DataProvider('twitchUrls')]
    public function test_it_reads_twitch_ids(string $url, string $expected): void
    {
        $parser = new VideoIdParser($url);

        $this->assertTrue($parser->isTwitch(), $url);
        $this->assertFalse($parser->isYoutube(), $url);
        $this->assertSame($expected, $parser->getId(), $url);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function youtubeUrls(): array
    {
        return [
            'watch' => ['https://www.youtube.com/watch?v=iRfDtVFLoVQ', 'iRfDtVFLoVQ'],
            'short' => ['https://youtu.be/iRfDtVFLoVQ', 'iRfDtVFLoVQ'],
            'embed' => ['https://www.youtube.com/embed/iRfDtVFLoVQ', 'iRfDtVFLoVQ'],
            'nocookie' => ['https://www.youtube-nocookie.com/embed/iRfDtVFLoVQ', 'iRfDtVFLoVQ'],
            'with timestamp' => ['https://youtu.be/iRfDtVFLoVQ?t=120', 'iRfDtVFLoVQ'],
        ];
    }

    #[DataProvider('youtubeUrls')]
    public function test_it_reads_youtube_ids(string $url, string $expected): void
    {
        $parser = new VideoIdParser($url);

        $this->assertTrue($parser->isYoutube(), $url);
        $this->assertFalse($parser->isTwitch(), $url);
        $this->assertSame($expected, $parser->getId(), $url);
    }

    /**
     * @return array<string, array{0: ?string}>
     */
    public static function unusableUrls(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
            // Used to fatal with "Undefined array key 0": the class saw
            // "twitch" in the host and then indexed a failed preg_match.
            'twitch channel with no vod' => ['https://www.twitch.tv/somechannel'],
            'twitch clip' => ['https://clips.twitch.tv/SomeFunnyClipSlug'],
            'unrelated host' => ['https://example.com/video/123456789'],
            'bare text' => ['not a url at all'],
        ];
    }

    #[DataProvider('unusableUrls')]
    public function test_it_yields_no_id_for_unusable_urls(?string $url): void
    {
        $parser = new VideoIdParser($url);

        $this->assertNull($parser->getId(), var_export($url, true));
        $this->assertFalse($parser->isTwitch());
        $this->assertFalse($parser->isYoutube());
    }

    public function test_a_twitch_url_is_never_reported_as_youtube(): void
    {
        // The old ordering ran the YouTube regex first and let a later Twitch
        // match overwrite the id while leaving isYoutube() true.
        $parser = new VideoIdParser('https://www.twitch.tv/videos/123456789');

        $this->assertTrue($parser->isTwitch());
        $this->assertFalse($parser->isYoutube());
    }
}
