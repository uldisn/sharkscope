<?php

namespace uldisn\sharkscope\components;

use yii\httpclient\Client;


class SharcScopeClient
{

    public $domain = 'http://www.sharkscope.com';
    public $username;
    public $password;

    public function __construct($domain,$username,$encodedPassword,$aplicationKey)
    {
        $this->domain = $domain;
        $this->username = $username;
        $this->password =  md5(strtolower($encodedPassword).$aplicationKey);
    }

    public function request($appName, $resource, $data)
    {

        $client = new Client();
        return $client->createRequest()
            ->setMethod('get')
            ->setUrl($domain . '/api/' .  $appName . '/' . $resource)
            ->setHeaders(['Accept' => 'application/json'])
            ->addHeaders(['User-Agent' => 'Mozzila'])
            ->addHeaders(['Username' => $this->username])
            ->addHeaders(['Password' => $this->password])
            ->setData($data)
            ->send();

    }


    public function requestPublic($appName, $resource, $data)
    {

        $client = new Client();
        return $client->createRequest()
            ->setMethod('get')
            ->setUrl($domain . '/api/' .  $appName . '/' . $resource)
            ->setHeaders(['Accept' => 'application/json'])
            ->addHeaders(['User-Agent' => 'Mozzila'])
            //->addHeaders(['Username' => $this->username])
            //->addHeaders(['Password' => $this->password])
            ->setData($data)
            ->send();

    }
}
// respons XML
//Defines a player statistic. The id attribute contains the identifier included in player responses, the
//name is the human-readable name of the statistic and the type defines the value type.

//Examples:
//&lt;PlayerStatisticDefinition name=&quot;Av Stake&quot; type=&quot;Currency&quot; id=&quot;AvStake&quot;/&gt;
//&lt;PlayerStatisticDefinition name=&quot;Av ROI&quot; type=&quot;Percentage&quot; id=&quot;AvROI&quot;/&gt;
