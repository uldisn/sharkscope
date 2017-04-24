<?php

namespace uldisn\sharkscope\components;

class ResponseHelper
{
    /** @var  array */
    public $playerGroupResponse = [];

    /** @var  array */
    public $playerStatistic = [];

    /** @var  array */
    public $groupStatistic = [];
    public function __construct($response)
    {
        if (isset($response['Response']['PlayerGroupResponse'])) {
            $this->playerGroupResponse = $response['Response']['PlayerGroupResponse'];
        }

        if (isset($response['Response']['PlayerResponse']['PlayerView'])) {
            if (isset($playerView['Player'])) {
                $this->playerGroupResponse = $playerView['Player']['Statistics']['Statistic'];
            } elseif (isset($playerView[0]) && isset($playerView[0]['Player'])) {
                $this->playerGroupResponse = $playerView[0]['Player']['Statistics']['Statistic'];
            }
        }

        if (isset($response['Response']['PlayerResponse']['PlayerView'])) {
            $playerView = $response['Response']['PlayerResponse']['PlayerView'];
            if (isset($playerView['PlayerGroup'])) {
                $this->groupStatistic = $playerView['PlayerGroup']['Statistics']['Statistic'];
            } elseif (isset($playerView[0]) && isset($playerView[0]['Player'])) {
                $this->groupStatistic = $playerView[0]['PlayerGroup']['Statistics']['Statistic'];
            }
        }
    }

    public function getGroups()
    {
        if(!isset($this->playerGroupResponse['PlayerGroup'])){
            return [];
        }

        return $this->playerGroupResponse['PlayerGroup'];
    }

    public function findGroup($groupName){
        foreach($this->getGroups() as $group){
            if($group['@name'] === $groupName){
                return $group;
            }
        }

        return [];
    }

    public function findGroupPlayer($groupName, $network, $playerName, $filter){
        if(!$group = $this->findGroup($groupName)){
            return false;
        }
        foreach($group['Players']['Player'] as $player){
            if($player['@name'] !== $playerName){
                continue;
            }

            if($player['@network'] !== $network){
                continue;
            }
            $match = false;
            foreach($player['Filter']['Constraint'] as $constraint){
                if($constraint['@id'] !== 'Date'){
                    continue;
                }
                if($constraint['Value'] === $filter){
                    return $player;
                }
            }
        }

        return [];
    }

    public function findGroupPlayersAll($groupPrefix,$groupSuffix){

        $groupsList = [];

        foreach($this->getGroups() as $group){
            $groupName = $group['@name'];
            if(!preg_match('#^'. $groupPrefix.'#', $groupName) ){
                continue;
            }
            if(!preg_match('#'. $groupSuffix.'$#', $groupName) ){
                continue;
            }
dump($groupName);
            if(isset($group['Players']['Player']['@name'])) {
                $player = $group['Players']['Player'];
                $playerName = $player['@name'];
                $network = $player['@network'];

                if (isset($player['Filter']['Constraint']['@id'])) {
                    if ($player['Filter']['Constraint']['@id'] !== 'Date') {
                        $filter = '-';
                    }else {
                        $filter = $player['Filter']['Constraint']['Value'];
                    }
                } else{
                    foreach ($player['Filter']['Constraint'] as $constraint) {
                        if ($constraint['@id'] !== 'Date') {
                            continue;
                        }
                        $filter = $constraint['Value'];
                    }
                }

                $groupsList[$groupName][] = $playerName . '|' . $network . '|' . $filter;
            }else {
                foreach ($group['Players']['Player'] as $player) {

                    $playerName = $player['@name'];
                    $network = $player['@network'];
                    if (isset($player['Filter']['Constraint']['@id'])) {
                        if ($player['Filter']['Constraint']['@id'] !== 'Date') {
                            $filter = '-';
                        }else {
                            $filter = $player['Filter']['Constraint']['Value'];
                        }
                    } else {
                        foreach ($player['Filter']['Constraint'] as $constraint) {
                            if ($constraint['@id'] !== 'Date') {
                                continue;
                            }
                            $filter = $constraint['Value'];
                        }
                    }

                    $groupsList[$groupName][] = $playerName . '|' . $network . '|' . $filter;
                }
            }

        }
        return $groupsList;
    }


    public function getGroupStatisticValue($name){
        if(!$this->groupStatistic){
            return false;
        }
        foreach($this->groupStatistic as $k => $row){
            if($name === $row['@id']){
                return $row['$'];
            }
        }

        return false;

    }


}