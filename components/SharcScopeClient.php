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

    const TYPE_GET = 'get';
    const TYPE_DELETE = 'delete';

    public $domain;
    public $username;
    public $password;
    public $appName;
    public $appKey;

    public $responseData;
    public $client;

    public $respError;
    public $userInfo;
    public $playerGroupResponse;

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
     * @param string $type
     * @return bool
     */
    public function request($type, $resource, $filter = [])
    {

        $this->respError = $this->userInfo = [];

        $url = $this->domain . '/api/' . $this->appName . '/' . $resource;
            echo $type .' '. $url .PHP_EOL;
        if ($filter) {
            $url .= '?filter=' . implode(';', $filter);
        }

        $this->responseData = $this->client->createRequest()
            ->setMethod($type)
            ->setUrl($url)
            ->setOptions([ 'protocolVersion' => '1.1'])
            ->setHeaders(['Accept' => 'application/json'])
            ->addHeaders(['User-Agent' => 'Mozzila'])
            ->addHeaders(['Username' => $this->username])
            ->addHeaders(['Password' => $this->password])
            // ->setData($data)
            ->send()
            ->getData();;

        if (isset($this->responseData['Response']['UserInfo'])) {
            $this->userInfo = $this->responseData['Response']['UserInfo'];
        }
        if (isset($this->responseData['Response']['ErrorResponse'])) {
            $this->respError = $this->responseData['Response']['ErrorResponse'];
            return false;
        }

        return true;
    }

    /**
     *
     * @param string $playerName
     * @return bool
     */
    public function requestPlayerSummary($playerName)
    {

        $resource = 'networks/fulltilt/players/' . $playerName;
        return $this->request(self::TYPE_GET, $resource);

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
        return $this->request(self::TYPE_GET, $resource, $filter);

    }

    public function requestGroupList()
    {

        $resource = 'playergroups';
        return $this->request(self::TYPE_GET, $resource);

    }

    /**
     * ?????
     * @param $groupName
     * @return bool
     */
    public function requestGroupRetrieval($groupName)
    {

        $resource = 'playergroups/'.$groupName;
        return $this->request(self::TYPE_GET, $resource);

    }

    public function getRemainingSearches(){
        if(!$this->userInfo){
            return false;
        }
        return $this->userInfo['RemainingSearches'];
    }

    /**
     * 3.4.4.	ADDING PLAYERS
     * @param string $groupName
     * @param string $network
     * @param string $playerName
     * @param array $filter
     * @return bool
     */
    public function addPlayerToGroup($groupName, $network, $playerName,$filter = [])
    {
        $resource = 'playergroups/'.$groupName.'/members/'.$network.'/'.$playerName;
        return $this->request(self::TYPE_GET, $resource, $filter);
    }

    /**
     * 3.4.6.	DELETING MEMBERS
     * do not work ????
     * @param string $groupName
     * @param string $network
     * @param string $playerName
     * @return bool
     */
    public function removePlayerFromGroup($groupName, $network, $playerName)
    {
        $resource = 'playergroups/'.$groupName.'/members/'.$network.'/'.$playerName;
        return $this->request(self::TYPE_DELETE, $resource);
    }

    /**
     * @param string $groupName
     * @return bool
     */
    public function removeGroup($groupName)
    {
        $resource = 'playergroups/'.$groupName;
        return $this->request(self::TYPE_DELETE, $resource);
    }


}
