<?php

namespace Diffy;

use GuzzleHttp\Client;

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
     * @return \GuzzleHttp\Client
     */
    public static function getClient() {
      if (empty(self::$client)) {
        self::$client = new Client(
          [
            'base_uri' => self::getApiBaseUrl(),
            'headers' => [
              'Accept' => 'application/json',
              'Content-Type' => 'application/json',
            ],
          ]
        );
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


        $response = self::getClient()->request(
            'POST',
            'auth/key',
            [
                'json' => ['key' => self::getApiKey()],
            ]
        );

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
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();

                $responseBodyAsString = $response->getBody()->getContents();

                if ($statusCode == '400') {
                    // Diffy knows errors. We need this code because of GuzzleHttp truncated errors.
                    $responseErrroData = json_decode((string)$response->getBody(), true);
                    if (isset($responseErrroData['errors'])) {
                        throw new \Exception(implode(PHP_EOL, $responseErrroData['errors']));
                    }
                }
            }
            throw $e;
        }

        return $responseBodyAsString;
    }

}
