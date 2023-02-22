<?php

namespace uldisn\sharkscope;

use yii\helpers\Json;

/**
 * Class SharcScopeClient
 * https://www.sharkscope.com/#SharkScope-API.html
 * @package uldisn\sharkscope
 */
class SharcScopeClient
{

    private const TYPE_GET = 'GET';
    private const TYPE_DELETE = 'DELETE';

    public $domain;
    public $appName;

    public $responseData;
    public $client;

    public $respError;
    public $respHeader;
    public $userInfo;
    public $playerGroupResponse;

    /** @var array  */
    private $curlOptions;
    private ?string $loggingDirectory = null;
    private ?string $loggingFilePrefix = null;
    private ?string $loggingSource = null;

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

    function request($type, $resource, $filter = [])
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

        $curl      = curl_init( $url );
        curl_setopt_array( $curl, $options );
        $content = curl_exec( $curl );
        $err     = curl_errno( $curl );
        $errmsg  = curl_error( $curl );
        $header  = curl_getinfo( $curl );
        curl_close( $curl );

        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;

        $this->respHeader = $header;

        if($header['http_code'] != 200){
            return false;
        }

        $this->responseData = json_decode($content, true);
        $this->responseData['CURL'] = $type .' '. $url;
        if (isset($this->responseData['Response']['UserInfo'])) {
            $this->userInfo = $this->responseData['Response']['UserInfo'];
        }
        $error = '';
        if (isset($this->responseData['Response']['ErrorResponse'])) {
            $this->respError = $this->responseData['Response']['ErrorResponse'];
			$error = Json::encode($this->respError);
        }
        $this->log($remainSearches,$type,$resource,$filter, $error);
        if ($this->respError) {
			return false;
		}
		return true;
    }

    /**
     * @throws \yii\base\Exception
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
            ',',
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
        if (file_exists($filePath)) {
            file_put_contents($filePath, PHP_EOL . $content, FILE_APPEND);
        } else {
            file_put_contents($filePath, $content);
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
     */
    public function requestGroupStatistic($groupName, $filter = []): bool
    {
        $resource = 'networks/player%20group/players/' . rawurlencode($groupName);
        return $this->request(self::TYPE_GET, $resource, $filter);
    }

    public function requestUser(): bool
    {
        return $this->request(self::TYPE_GET, 'user');
    }


    public function requestGroupList(string $groupName = null)
    {
        $resource = 'playergroups';
        if ($groupName) {
            $resource .= '/' . $groupName;
        }
        return $this->request(self::TYPE_GET, $resource);
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
     */
    public function requestCompletedTournaments(string $network, array $filter): bool
    {
        $resource = 'networks/'.urlencode($network).'/completedTournaments';
        return $this->request(self::TYPE_GET, $resource, $filter);
    }

    /**
     * 3.5.5.	BARE TOURNAMENTS
     *
     * @param string $network
     * @param int[] $tournamentIds
     * @return bool
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
     * @see http://www.sharkscope.com/docs/SharkScope%20WS%20API.doc
     */
    public function requestDailyScheduledTournaments(string $network, array $filter, $date): bool
    {
        $resource = 'reports/dailyscheduledtournaments/networks/'.urlencode($network).'?date='.$date;
        return $this->request(self::TYPE_GET, $resource, $filter);
    }

    /**
     * 3.10.6.	DAILY SCHEDULED TOURNAMENTS REPORT (BY NETWORK)
     *    Produces a report listing the daily scheduled tournaments for a specific date and network.
     *    The last 3 days of data are available to all Commercial Gold subscribers and above.
     *    This is similar to the same report by region.
     * @param string $network
     * @param array $filter
     * @return bool
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
     */
    public function requestGroupRetrieval($groupName)
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
     * 3.4.4.	ADDING PLAYERS
     * @param string $groupName
     * @param string $network
     * @param string $playerName
     * @param array $filter
     * @return bool
     */
    public function addPlayerToGroup($groupName, $network, $playerName,$filter = [])
    {
        $resource = 'playergroups/'.rawurlencode($groupName).'/members/'.rawurlencode($network).'/'.rawurlencode($playerName);
        return $this->request(self::TYPE_GET, $resource, $filter);
    }

    /**
     * 3.4.6.	DELETING MEMBERS
     * do not work ????
     * @param string $groupName
     * @param string $network
     * @param string $playerName
     * @param array $filter
     * @return bool
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
     */
    public function requestTournamentById(string $tournamentId, string $network, array $filter = []): bool
    {
        $resource = 'networks/' . $network.'/tournaments/' . $tournamentId;

        return $this->request(self::TYPE_GET, $resource, $filter);
    }

    public function requestUserSummary(string $playerName, string $network, array $filter = []): bool
    {
        $resource = 'networks/' . $network.'/players/' . $playerName;

        return $this->request(self::TYPE_GET, $resource, $filter);
    }
}
