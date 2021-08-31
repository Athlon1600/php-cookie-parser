<?php

namespace CookieParser;

class Cookie
{
    public $name;
    public $value;
    public $domain;
    public $path;

    // if zero, that usually means this is a session cookie
    public $expires;

    // The "HttpOnly" flag blocks the access of the related cookie from the client-side (it can’t be used from Javascript code)
    public $httpOnly = true;

    // When a secure flag is used, then the cookie will only be sent over HTTPS
    public $secure = false;

    public function __construct($data = [])
    {
        foreach ($data as $name => $value) {
            if (property_exists($this, $name)) {
                $this->{$name} = $value;
            }
        }
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public static function fromNetscape($line): ?self
    {
        $parts = explode("\t", $line);

        if (count($parts) !== 7) {
            return null;
        }

        $httpOnly = false;
        $domain = $parts[0];

        if (strpos($domain, '#HttpOnly_') === 0) {
            $httpOnly = true;
            $domain = substr($domain, 10);
        }

        // TODO: if includeSubdomains is true, prepend dot to .domain

        $cookie = new Cookie();
        $cookie->domain = $domain;
        $cookie->path = $parts[2];
        $cookie->secure = $parts[3] == 'TRUE';
        $cookie->httpOnly = $httpOnly;
        $cookie->expires = intval($parts[4]);
        $cookie->name = $parts[5];
        $cookie->value = $parts[6];

        return $cookie;
    }

    public static function fromChromeArray($array): Cookie
    {
        $cookie = new Cookie($array);

        // Will sometimes appear as float. E.g: 1627414377.383679
        if ($cookie->expires) {
            $cookie->expires = preg_replace('/\.[0-9]+$/', '', $cookie->expires);
        }

        if ($cookie->expires == -1) {
            $cookie->expires = 0;
        }

        return $cookie;
    }

    public function toGuzzle(): array
    {
        $expires = (int)$this->expires;

        return [
            'Name' => $this->name,
            'Value' => $this->value,
            'Domain' => $this->domain,
            'Path' => $this->path,
            'Max-Age' => null,
            'Expires' => $expires === 0 ? null : $expires,
            'Secure' => $this->secure,
            'Discard' => false,
            'HttpOnly' => $this->httpOnly
        ];
    }

    public function toChromeArray(): array
    {
        $arr = $this->toArray();

        if ($arr['expires'] == 0) {
            $arr['expires'] = -1;
        }

        // Invalid parameters Failed to deserialize params.cookies.expires - BINDINGS: double value expected at position 280
        if (is_numeric($arr['expires'])) {
            $arr['expires'] = (int)$arr['expires'];
        }

        return $arr;
    }

    public function toNetscape(): string
    {
        $include_subdomains = strpos($this->domain, '.') === 0 ? 'TRUE' : 'FALSE';

        $parts = [
            $this->domain,
            $include_subdomains,
            $this->path,
            $this->httpOnly ? 'TRUE' : 'FALSE',
            $this->expires, // expires at - seconds since Jan 1st 1970, or 0
            $this->name,
            $this->value
        ];

        return implode("\t", $parts);
    }
}

