<?php

namespace uldisn\sharkscope\components;

use yii\httpclient\Client;

/**
 * Class SharcScopeClient
 * https://www.sharkscope.com/#SharkScope-API.html
 * @package uldisn\sharkscope\components
 */
class SharcScopeClient
{

    public $domain;
    public $username;
    public $password;
    public $appName;
    public $appKey;

    public $responseData;
    public $client;

    public $respError;

    /**
     * SharcScopeClient constructor.
     * @param $domain
     * @param $appName
     * @param $username
     * @param $encodedPassword
     * @param $appKey
     */
    public function __construct($domain, $appName, $username, $encodedPassword, $appKey)
    {
        $this->domain = $domain;
        $this->username = $username;
        $this->appName = $appName;
        $this->appKey = $appKey;
        $this->password = md5(md5($encodedPassword) . $this->appKey);
        $this->client = new Client();
    }

    /**
     * @param $resource
     * @param array $filter
     * @return bool
     */
    public function request($resource, $filter = [])
    {

        $this->respError = [];

        $url = $this->domain . '/api/' . $this->appName . '/' . $resource;

        if ($filter) {
            $url .= '?filter=' . implode(';', $filter);
        }

        $this->responseData = $this->client->createRequest()
            ->setMethod('get')
            ->setUrl($url)
            ->setHeaders(['Accept' => 'application/json'])
            ->addHeaders(['User-Agent' => 'Mozzila'])
            ->addHeaders(['Username' => $this->username])
            ->addHeaders(['Password' => $this->password])
            // ->setData($data)
            ->send()
            ->getData();;

        if (isset($this->responseData['Response']['ErrorResponse'])) {
            $this->respError = $this->responseData['Response']['ErrorResponse'];
            return false;
        }

        return true;
    }

    /**
     *
     * @param string $playerName
     * @param array $filter
     * @return bool
     */
    public function requestPlayerStatistic($playerName, $filter = [])
    {

        $resource = 'networks/fulltilt/players/' . $playerName . '/statistics';
        return $this->request($resource, $filter);

    }

}
