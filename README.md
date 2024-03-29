[![Latest Stable Version](https://poser.pugx.org/uldisn/sharkscope/v)](//packagist.org/packages/uldisn/sharkscope)
[![Total Downloads](https://poser.pugx.org/uldisn/sharkscope/downloads)](https://packagist.org/packages/uldisn/sharkscope)
[![Latest Unstable Version](https://poser.pugx.org/uldisn/sharkscope/v/unstable)](https://packagist.org/packages/uldisn/sharkscope)
[![License](https://poser.pugx.org/uldisn/sharkscope/license)](https://packagist.org/packages/uldisn/sharkscope)
[![Code Climate](https://codeclimate.com/github/uldisn/sharkscope/badges/gpa.svg)](https://codeclimate.com/github/uldisn/sharkscope)

# PHP SharkScope API  


For https://www.sharkscope.com/

## Features

* Create/remove groups
* Add players to groups
* get group statistic


## Installation
```bash
php composer.phar require uldisn/sharkscope dev-master
```

 

## Usage

```php
        $domain = 'http://www.sharkscope.com';
        $userName = '???????????????';
        $password = '??????????';
        $appKey = '??????????????';
        $appName = '???????';

        $client = new SharcScopeClient($domain, $appName, $userName, $password, $appKey);
        $this->client->setLogging('shark-log',$username,'player statistic');
        
        if($client->requestGroupList()) {
            $groupList = $this->client->responseData;
        }else{
            $this->out('Invalid response from requestGroupList');
            print_r($this->client->respError);
            die();
        }
                    
        $playerStatistic = new ResponseHelper($groupList);
        $groupsPlayers = ($playerStatistic->findGroupPlayersAll('PREFIX_', '_SUFIX'));
        
        $from = new \DateTime($pgLimit->start_date);
        $to = new \DateTime($pgLimit->end_date);
        $filter = FilterHelper::createDateFromToValue($from, $to);        
                    
                    
        if(!$client->removePlayerFromGroup($groupName,$network,$player,$filter)){
            $this->out('Invalid response from removePlayerFromGroup');
            if($client->respError) {
                print_r($client->respError);
            }else {
                print_r($client->respHeader);
            }

        }                    
                    
```

### Change log
 - 1.0.0 (Apr 25, 2017) - It work!
 - 1.0.1 (Apr 27, 2017) - In FilterHelper implemented SharkScopeEnd date
 - 1.0.2 (Apr 27, 2017) - Implemented GMT time zone for DateTime
 - 1.0.3 (Apr 29, 2017) - To FilterHelper added method dateActualYear()
 - 1.0.4 (Apr 30, 2017) - Solved GMT problem
 - 1.0.8 (Mar 1, 2021) - added method requestActiveTournaments()
 - 1.0.13 (Nov 16, 2022) - added method deleteGroup()
 - 1.0.14 (Nov 22, 2022) - added method requestUser() 
 - 1.0.15 (Feb 22, 2023) - added logging
 - 1.0.16 (Mar 3, 2023) - added method requestGroupListByFullName()
