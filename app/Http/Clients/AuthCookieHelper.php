<?php

namespace App\Http\Clients;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Client;

class AuthCookieHelper
{
    private $baseUrl;

    public function __construct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * Get current authentication cookies from the current request
     * This is like JWT token validation but for Sanctum SPA cookies
     */
    public function getCurrentAuthCookies()
    {
        $request = request();
        $cookieJar = new CookieJar();

        if ($request->hasCookie(config('session.cookie'))) {
            $cookieJar->setCookie(new \GuzzleHttp\Cookie\SetCookie([
                'Name' => config('session.cookie'),
                'Value' => $request->cookie(config('session.cookie')),
                'Domain' => parse_url($this->baseUrl, PHP_URL_HOST),
                'Path' => '/'
            ]));
        }

		$client = new Client();
        // This call will set the XSRF-TOKEN and session cookies into the cookie jar
        $client->get(str_replace('/api', '', $this->baseUrl) . '/sanctum/csrf-cookie', [
            'cookies' => $cookieJar,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
		$xsrfToken = null;
		foreach ($cookieJar->toArray() as $cookie) {
            if (isset($cookie['Name']) && $cookie['Name'] === 'XSRF-TOKEN') {
                $xsrfToken = $cookie['Value'];
                break;
            }
        }
        // Sanctum encodes the token in the cookie value
        $csrfToken = $xsrfToken !== null ? urldecode($xsrfToken) : null;

        return [
            'cookies' => $cookieJar,
            'csrf_token' => $csrfToken
        ];
    }
}