<?php

namespace Diffy;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class Diffy
{
    public static $apiKey;

    public static $apiToken;

    public static $baseUrl = 'https://app.diffy.website/api/';

    public static $uiBaseUrl = 'https://app.diffy.website/#/';

    public static $client;

    /**
     * Init guzzle client.
     *
     * @return Client
     */
    public static function getClient()
    {
        if (empty(self::$client)) {
            self::$client = new Client([
                'base_uri' => self::getApiBaseUrl(),
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ]);
        }

        return self::$client;
    }

    /**
     * @return string The API key used for requests.
     */
    public static function getApiKey()
    {
        return self::$apiKey;
    }

    /**
     * Sets the API key to be used for requests.
     *
     * @param string $apiKey
     */
    public static function setApiKey($apiKey)
    {
        self::$apiKey = $apiKey;

        self::refreshToken();
    }

    /**
     * @return string The API key used for requests.
     */
    public static function getApiToken()
    {
        return self::$apiToken;
    }

    /**
     * Sets the API key to be used for requests.
     *
     * @param string $apiToken
     */
    public static function setApiToken($apiToken)
    {
        self::$apiToken = $apiToken;
    }

    /**
     * @return string Base URL for API calls.
     */
    public static function getApiBaseUrl()
    {
        return self::$baseUrl;
    }

    /**
     * Do a call to API's to get a fresh token.
     */
    public static function refreshToken()
    {
        $response = self::getClient()->request('POST', 'auth/key', [
            'json' => ['key' => self::getApiKey()],
        ]);

        $data = json_decode($response->getBody()->getContents());

        if (isset($data->token)) {
            self::setApiToken($data->token);
        }
    }

    /**
     * Do a HTTP request. Wrapper to pass Authentication behind the scene.
     */
    public static function request($type, $uri, $data = [], $params = [])
    {
        $params['headers'] = [
            'Authorization' => 'Bearer ' . self::getApiToken(),
        ];

        if (!empty($data)) {
            $params['json'] = $data;
        }

        try {
            $response = self::getClient()->request($type, $uri, $params);
            $responseBodyAsString = json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $content = json_decode($response->getBody()->getContents(), true);

            if (isset($content['type']) && ($content['type'] == 'validation_error')) {
                $request = $e->getRequest();
                $uri = $request->getUri();

                // Client Error: `GET /` resulted in a `404 Not Found` response:
                // <html> ... (truncated)
                $message = sprintf(
                    '%s %s Error: %s %s',
                    $request->getMethod(),
                    $uri,
                    $response->getStatusCode(),
                    implode('. ', $content['errors'])
                );

                throw new \Exception($message);
            }
            // If it was something else.
            throw $e;
        }

        return $responseBodyAsString;
    }

    /**
     * Do a HTTP request. Wrapper to pass Authentication behind the scene.
     */
    public static function multipartRequest($type, $uri, array $data, array $params = [])
    {
        $params['headers'] = [
            'Authorization' => 'Bearer '.self::getApiToken(),
        ];

        $params['multipart'] = $data;

        try {
            $response = self::$client->request($type, $uri, $params);
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $content = json_decode($response->getBody()->getContents(), true);

            if (isset($content['type']) && ($content['type'] == 'validation_error')) {
                $request = $e->getRequest();
                $uri = $request->getUri();

                // Client Error: `GET /` resulted in a `404 Not Found` response:
                // <html> ... (truncated)
                $message = sprintf(
                    '%s %s Error: %s %s',
                    $request->getMethod(),
                    $uri,
                    $response->getStatusCode(),
                    implode('. ', $content['errors'])
                );

                throw new \Exception($message);
            }
            // If it was something else.
            throw $e;
        }

        return json_decode($response->getBody()->getContents(), true);
    }
}
