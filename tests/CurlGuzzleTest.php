<?php

namespace CookieParser\Tests;

use CookieParser\CookieJar;
use Curl\BrowserClient;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use Nesk\Puphpeteer\Puppeteer;
use PHPUnit\Framework\TestCase;

class CurlGuzzleTest extends TestCase
{
    protected $curl_cookie_file;
    protected $guzzle_cookie_file;

    protected function setUp(): void
    {
        $this->curl_cookie_file = tempnam(sys_get_temp_dir(), 'curl');
        $this->guzzle_cookie_file = tempnam(sys_get_temp_dir(), 'guzzle');
    }

    protected function tearDown(): void
    {
        unlink($this->curl_cookie_file);
        unlink($this->guzzle_cookie_file);
    }

    public function testCurlToGuzzle()
    {
        $curl = new BrowserClient();
        $curl->setCookieFile($this->curl_cookie_file);

        $response = $curl->get("https://httpbin.org/cookies/set?one=111&two=222");

        $this->assertEquals(200, $response->status);

        // import Netscape-formatted cookies into our own CookieJar
        $cookieJar = CookieJar::fromAuto($curl->getCookies());

        // export new cookies into Guzzle compatible format
        file_put_contents($this->guzzle_cookie_file, $cookieJar->toGuzzle());

        // Guzzle client will be using those cookies when making requests
        $guzzleCookieJar = new FileCookieJar($this->guzzle_cookie_file, true);

        $client = new Client();

        $response = $client->get("https://httpbin.org/cookies", [
            'cookies' => $guzzleCookieJar
        ]);

        $contents = $response->getBody()->getContents();

        $this->assertStringContainsString('"one": "111"', $contents);
        $this->assertStringContainsString('"two": "222"', $contents);
    }

    public function testChromeToCurl()
    {
        $puppeteer = new Puppeteer();

        $browser = $puppeteer->launch([
            'headless' => true,
            'ignoreDefaultArgs' => ['--enable-automation'],
        ]);

        $page = $browser->newPage();
        $page->goto('https://httpbin.org/cookies/set?one=111&two=222');

        $jar = CookieJar::fromAuto($page->cookies());

        $this->assertCount(2, $jar->cookies);

        file_put_contents($this->curl_cookie_file, $jar->toNetscape());

        $curl = new BrowserClient();
        $curl->setCookieFile($this->curl_cookie_file);

        $responseBody = $curl->get('https://httpbin.org/cookies')->body;

        $this->assertStringContainsString('"one": "111"', $responseBody);
        $this->assertStringContainsString('"two": "222"', $responseBody);
    }
}
