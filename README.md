# PHP Cookie Parser

[![Build Status](https://img.shields.io/github/workflow/status/athlon1600/php-cookie-parser/CI)](https://github.com/guzzle/guzzle/actions?query=workflow%3ACI)

Needed to be able to convert between different cookies formats, which is especially useful when sharing cookies across different applications like PHP/Curl and Chrome Puppeteer.

## Formats supported

- Netscape (https://curl.haxx.se/docs/http-cookies.html)
- Chrome Puppeteer (https://chromedevtools.github.io/devtools-protocol/1-3/Network/#type-Cookie)
- Guzzle (https://github.com/guzzle/guzzle/blob/master/src/Cookie/SetCookie.php)
- Cookies copied from Developer Tools -> Cookies tab

## See it in action

Convert between formats in real-time within your browser + lots of other cool features:

- https://cookiebin.net

## Useful links

- https://github.com/andriichuk/php-curl-cookbook#cookies
