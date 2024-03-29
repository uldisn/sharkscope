<?php

namespace uldisn\sharkscope;

use Exception;

/**
 * Class SharcScopeClient
 * https://www.sharkscope.com/#SharkScope-API.html
 * @package uldisn\sharkscope
 */
class SharcScopeClient
{

    private const TYPE_GET = 'GET';
    private const TYPE_DELETE = 'DELETE';

    public string $domain;
    public string $appName;

    public ?array $responseData =null;
    public array $respError = [];
    public ?array $respHeader;
    public ?array $userInfo = null;
    public $playerGroupResponse;
    public $error;

    /** @var array  */
    private array $curlOptions;
    private ?string $loggingDirectory = null;
    private ?string $loggingFilePrefix = null;
    private ?string $loggingSource = null;
    /**
     * @var mixed
     */
    private ?string $userName = null;

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
        $this->appName = $appName;
        $this->userName = $username;

        $this->curlOptions = [
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER         => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => "",       // handle all encodings
            CURLOPT_USERAGENT      => "Mozzila", // who am i
            CURLOPT_AUTOREFERER    => false,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT        => 120,      // timeout on response
            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
            CURLOPT_SSL_VERIFYPEER => false,     // Disabled SSL Cert checks
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Username: '.$username,
                'Password: ' . md5(md5($encodedPassword) . $appKey),
            ],
        ];
    }

    public function setLogging(string $directory, string $filePrefix, string $source): void
    {
        $this->loggingDirectory = $directory;
        $this->loggingFilePrefix = $filePrefix;
        $this->loggingSource = $source;
    }

    /**
     * @throws \yii\base\Exception
     */
    public function request($type, $resource, array $filter = []): bool
    {
        $remainSearches = 0;
        if ($resource !== 'user' && $this->loggingDirectory) {
            $this->requestUser();
            if (!$remainSearches = $this->getRemainingSearches()) {
                $this->requestUser();
                $remainSearches = $this->getRemainingSearches();
            }
        }

        $this->respError = $this->userInfo = [];

        $url = $this->domain . '/api/' . $this->appName . '/' . $resource;

        if ($filter) {
            $url .= '?filter=' . implode(';', $filter);
        }

        $options = $this->curlOptions;
        $options[CURLOPT_CUSTOMREQUEST] = $type;

        $counter = 0;
        while (true) {
            $curl = curl_init($url);
            curl_setopt_array($curl, $options);
            $content = curl_exec($curl);
            $err = curl_errno($curl);
            $errmsg = curl_error($curl);
            $header = curl_getinfo($curl);
            curl_close($curl);

            $header['errno'] = $err;
            $header['errmsg'] = $errmsg;

            $this->respHeader = $header;

            /** OK */
            if ((int)$header['http_code'] === 200) {
                $this->respError = [];
                break;
            }
            $this->respError['error'] = 'http_code =' . $header['http_code'];
            $this->respError['CURL'] = $type . ' ' . $url;
            $this->respError['responseHeader'] = $header;
            $this->respError['responseContent'] = $content;
            $this->respError['user'] = $this->userName;

            /** 503 - service unavailable */
            if ((int)$header['http_code'] !== 503) {
                return false;
            }
            $counter ++;
            if ($counter > 5) {
                return false;
            }
            sleep(5);
        }

        try {
            $this->responseData = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            $this->responseData['CURL'] = $type . ' ' . $url;
        } catch (Exception $e) {
            $this->respError['error'] = 'Error on json_decode content: ' . $e->getMessage();
            $this->respError['CURL'] = $type . ' ' . $url;
            $this->respError['responseHeader'] = $header;
            $this->respError['responseContent'] = $content;
            return false;
        }
        if (isset($this->responseData['Response']['UserInfo'])) {
            $this->userInfo = $this->responseData['Response']['UserInfo'];
        }
        $error = '';
        if (isset($this->responseData['Response']['ErrorResponse'])) {
            $this->respError = array_merge(
                $this->respError,
                $this->responseData['Response']['ErrorResponse']
            );
			try {
                $error = json_encode($this->respError, JSON_THROW_ON_ERROR);
            } catch (Exception $e) {
                $this->respError['respError'] = print_r($this->respError, true);
                $this->respError['error'] = 'Error on json_encode content: ' . $e->getMessage();
            }
        }
        $this->log($remainSearches,$type,$resource,$filter, $error);
        if ($this->respError) {
			return false;
		}
		return true;
    }

    /**
     */
    private function log(int $remainSearches, string $type, string $resource, array $filter, string $error): void
    {
        if ($resource === 'user') {
            return;
        }
        if (!$this->loggingDirectory) {
            return;
        }
        $remainingSearches = $this->getRemainingSearches();
        $content = implode(
            "\t",
            [
                date('H:i:s'),
                $this->loggingSource,
                $type,
                $resource,
                $remainSearches,
                $remainingSearches,
                $remainSearches - $remainingSearches,
                implode(';',$filter),
                $error
            ]
        );
        $filePath = $this->loggingDirectory . '/' . $this->loggingFilePrefix . '-' . date('Ymd') . '.log';
        if (!file_exists($filePath)) {
            /** header */
            file_put_contents(
                $filePath,
                implode(
                    "\t",
                    ['time', 'process', 'method', 'request', 'remain after', 'remain before', 'used remains', 'filter', 'Error']
                ) . PHP_EOL .
                $content
            );
        } else {
            file_put_contents($filePath, PHP_EOL . $content, FILE_APPEND);
        }

    }
//    /**
//     *
//     * @param string $playerName
//     * @return bool
//     */
//    public function requestPlayerSummary($playerName)
//    {
//
//        $resource = 'networks/fulltilt/players/' . rawurlencode($playerName);
//        return $this->request(self::TYPE_GET, $resource);
//
//    }
//
//    /**
//     *
//     * @param string $playerName
//     * @param array $filter
//     * @return bool
//     */
//    public function requestPlayerStatistic($playerName, $filter = [])
//    {
//
//        $resource = 'networks/fulltilt/players/' . rawurlencode($playerName) . '/statistics';
//        return $this->request(self::TYPE_GET, $resource, $filter);
//
//    }

    /**
     *
     * @param string $groupName
     * @param array $filter
     * @return bool
     * @throws \yii\base\Exception
     */
    public function requestGroupStatistic(string $groupName, array $filter = []): bool
    {
        $resource = 'networks/player%20group/players/' . rawurlencode($groupName);
        return $this->request(self::TYPE_GET, $resource, $filter);
    }

    /**
     * @throws \yii\base\Exception
     */
    public function requestUser(): bool
    {
        return $this->request(self::TYPE_GET, 'user');
    }


    /**
     * @throws \yii\base\Exception
     */
    public function requestGroupList(string $groupName = null): bool
    {
        $resource = 'playergroups';
        if ($groupName) {
            $resource .= '/' . $groupName;
        }
        return $this->request(self::TYPE_GET, $resource);
    }

    /**
     * @throws \yii\base\Exception
     */
    public function requestGroupListByFullName(string $groupName): ?array
    {
        $this->error = null;
        if (!$this->requestGroupList($groupName)) {
            if ($this->respError['Error']['$']??'' === ' Player group not found.') {
                $this->error = 'Player group not found.';
                return [];
            }
	        $this->error = $this->respError['Error']['$']??'';
            return null;
        }
        $groupList = $this->responseData;
        $playerStatistic = new ResponseHelper($groupList);
        if(!$playerStatistic->playerGroupResponse){
            $this->error = 'Empty playerGroupResponse';
            return null;
        }
        try {
            $groupsPlayers = ($playerStatistic->findGroupPlayersAll($groupName));
        } catch (Exception $e) {
            try {
                $response = json_encode($playerStatistic->playerGroupResponse, JSON_THROW_ON_ERROR);
            } catch (Exception $e) {
                $response = print_r($playerStatistic->playerGroupResponse, true);
            }
            $this->error = 'Exception: ' .
                $e->getMessage() . PHP_EOL .
                'Shark data: ' . $response . PHP_EOL .
                $e->getTraceAsString();
            return null;
        }
        return $groupsPlayers;
    }

    /**
     * API DOC point 3.5.3
     * Requests completed tournaments on an optional filter.
     * This resource requires special authorization and can be provided only
     * by prior agreement with SharkScope. The tournaments are pre-filtered with
     * an mutually agreed filter and will not include any tournaments
     * older than two days.
     * Completed Tourments
     * @param string $network
     * @param array $filter
     * @return bool
     * @throws \yii\base\Exception
     */
    public function requestCompletedTournaments(string $network, array $filter): bool
    {
        $resource = 'networks/'.urlencode($network).'/completedTournaments';
        return $this->request(self::TYPE_GET, $resource, $filter);
    }

    /**
     * 3.5.5.    BARE TOURNAMENTS
     *
     * @param string $network
     * @param int[] $tournamentIds
     * @return bool
     * @throws \yii\base\Exception
     */
    public function requestBareTournaments(string $network, array $tournamentIds): bool
    {
        $resource = 'networks/'
            . urlencode($network)
            . '/bareTournaments?tournamentIDs='
            . implode(',',$tournamentIds);
        return $this->request(self::TYPE_GET, $resource);
    }

    /**
     * 3.10.6.    DAILY SCHEDULED TOURNAMENTS REPORT (BY NETWORK)
     *    Produces a report listing the daily scheduled tournaments for a specific date and network.
     *    The last 3 days of data are available to all Commercial Gold subscribers and above.
     *    This is similar to the same report by region.
     * @param string $network
     * @param array $filter
     * @param $date
     * @return bool
     * @throws \yii\base\Exception
     * @see http://www.sharkscope.com/docs/SharkScope%20WS%20API.doc
     */
    public function requestDailyScheduledTournaments(string $network, array $filter, $date): bool
    {
        $resource = 'reports/dailyscheduledtournaments/networks/'.urlencode($network).'?date='.$date;
        return $this->request(self::TYPE_GET, $resource, $filter);
    }

    /**
     * 3.10.6.    DAILY SCHEDULED TOURNAMENTS REPORT (BY NETWORK)
     *    Produces a report listing the daily scheduled tournaments for a specific date and network.
     *    The last 3 days of data are available to all Commercial Gold subscribers and above.
     *    This is similar to the same report by region.
     * @param string $network
     * @param array $filter
     * @return bool
     * @throws \yii\base\Exception
     * @see http://www.sharkscope.com/docs/SharkScope%20WS%20API.doc
     */
    public function requestActiveTournaments(string $network, array $filter): bool
    {
        $resource = 'networks/' . urlencode($network) . '/activeTournaments';

        return $this->request(self::TYPE_GET, $resource, $filter);
    }

    /**
     * ?????
     * @param $groupName
     * @return bool
     * @throws \yii\base\Exception
     */
    public function requestGroupRetrieval($groupName): bool
    {

        $resource = 'playergroups/'.rawurlencode($groupName);
        return $this->request(self::TYPE_GET, $resource);

    }

    public function getRemainingSearches(){
        if(!$this->userInfo){
            return false;
        }
        return $this->userInfo['RemainingSearches'];
    }

    /**
     * 3.4.4.    ADDING PLAYERS
     * @param string $groupName
     * @param string $network
     * @param string $playerName
     * @param array $filter
     * @return bool
     * @throws \yii\base\Exception
     */
    public function addPlayerToGroup(string $groupName, string $network, string $playerName, array $filter = []): bool
    {
        $resource = 'playergroups/'.rawurlencode($groupName).'/members/'.rawurlencode($network).'/'.rawurlencode($playerName);
        return $this->request(self::TYPE_GET, $resource, $filter);
    }

    /**
     * 3.4.6.    DELETING MEMBERS
     * do not work ????
     * @param string $groupName
     * @param string $network
     * @param string $playerName
     * @param array $filter
     * @return bool
     * @throws \yii\base\Exception
     */
    public function removePlayerFromGroup(string $groupName, string $network, string $playerName, array $filter = []): bool
    {
        $resource = 'playergroups/'.rawurlencode($groupName).'/members/'.rawurlencode($network).'/'.rawurlencode($playerName);
        return $this->request(self::TYPE_DELETE, $resource, $filter);
    }

    /**
     * API DOC 3.4.7. DELETING THE GROUP
     * @param string $groupName
     * @return bool
     * @throws \yii\base\Exception
     */
    public function deleteGroup(string $groupName): bool
    {
        $resource = 'playergroups/'.rawurlencode($groupName);
        return $this->request(self::TYPE_DELETE, $resource);
    }

//    /**
//     * @param string $groupName
//     * @return bool
//     */
//    public function removeGroup($groupName)
//    {
//        $resource = 'playergroups/'.rawurlencode($groupName);
//        return $this->request(self::TYPE_DELETE, $resource);
//    }


    /**
     *
     * @param string $tournamentId
     * @param string $network
     * @param array $filter
     * @return bool
     * @throws \yii\base\Exception
     */
    public function requestTournamentById(string $tournamentId, string $network, array $filter = []): bool
    {
        $resource = 'networks/' . $network.'/tournaments/' . $tournamentId;

        return $this->request(self::TYPE_GET, $resource, $filter);
    }

    /**
     * @throws \yii\base\Exception
     */
    public function requestUserSummary(string $playerName, string $network, array $filter = []): bool
    {
        $resource = 'networks/' . $network.'/players/' . $playerName;

        return $this->request(self::TYPE_GET, $resource, $filter);
    }
}
