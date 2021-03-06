<?php

namespace CookieParser;

class CookieJar
{
    /** @var Cookie[] */
    public $cookies = array();

    public function isEmpty(): bool
    {
        return count($this->cookies) == 0;
    }

    public static function fromAuto($contents): ?self
    {
        if (is_array($contents)) {
            return self::fromChrome($contents);
        }

        if (is_array(json_decode($contents))) {
            $json = json_decode($contents, true);
            return self::fromChrome($json);
        }

        // TODO: also look for \tNone|Medium|High
        $check = mb_chr(10003);

        if (mb_strpos($contents, $check) !== false) {
            return self::fromChromeCookiesTable($contents);
        }

        return self::fromNetscape($contents);
    }

    protected static function fromChromeCookiesTable($contents): ?self
    {
        $lines = preg_split('/\r\n|\r|\n/', $contents);
        $lines = array_map('trim', $lines);

        $jar = new CookieJar();

        foreach ($lines as $line) {

            $parts = explode("\t", $line);

            if (count($parts) == 11) {

                $cookie = new Cookie();
                $cookie->name = $parts[0];
                $cookie->value = $parts[1];
                $cookie->domain = $parts[2];
                $cookie->path = $parts[3];

                $expires = $parts[4];

                if ($expires == 'Session') {
                    $cookie->expires = 0;
                } else {
                    $cookie->expires = strtotime($expires);
                }

                $cookie->httpOnly = !empty($parts[6]);
                $cookie->secure = !empty($parts[7]);

                $jar->cookies[] = $cookie;
            }
        }

        return $jar;
    }

    protected static function fromNetscape($contents): ?self
    {
        // $lines = explode("\r\n", $contents);
        $lines = preg_split('/\r\n|\r|\n/', $contents);
        $lines = array_map('trim', $lines);

        $jar = new CookieJar();

        foreach ($lines as $line) {
            $cookie = Cookie::fromNetscape($line);

            if ($cookie) {
                $jar->cookies[] = $cookie;
            }
        }

        return $jar;
    }

    protected static function fromChrome(array $data): ?self
    {
        $jar = new CookieJar();

        foreach ($data as $item) {
            $jar->cookies[] = Cookie::fromChromeArray($item);
        }

        return $jar;
    }

    public function toChrome(): string
    {
        $temp = array_map(function (Cookie $cookie) {
            return $cookie->toChromeArray();
        }, $this->cookies);

        return json_encode($temp, JSON_PRETTY_PRINT);
    }

    public function toGuzzle(): string
    {
        $temp = array_map(function (Cookie $cookie) {
            return $cookie->toGuzzle();
        }, $this->cookies);

        return json_encode($temp, JSON_PRETTY_PRINT);
    }

    public function toNetscape(): string
    {
        $lines = [
            '# Netscape HTTP Cookie File',
            '# https://curl.haxx.se/docs/http-cookies.html',
            '# This file was generated by libcurl! Edit at your own risk.',
            ''
        ];

        foreach ($this->cookies as $cookie) {
            $lines[] = $cookie->toNetscape();
        }

        // must end in new line apparently
        $lines[] = PHP_EOL;

        return implode(PHP_EOL, $lines);
    }
}
