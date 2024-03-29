<?php

namespace uldisn\sharkscope;

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
            $playerView = $response['Response']['PlayerResponse']['PlayerView'];
            if (isset($playerView['Player']['Statistics']['Statistic'])) {
                $this->playerGroupResponse = $playerView['Player']['Statistics']['Statistic'];
            } elseif (isset($playerView[0]['Player'])) {
                $this->playerGroupResponse = $playerView[0]['Player']['Statistics']['Statistic'];
            }
        }

        if (isset($response['Response']['PlayerResponse']['PlayerView'])) {
            $playerView = $response['Response']['PlayerResponse']['PlayerView'];
            if (isset($playerView['PlayerGroup']['Statistics']['Statistic'])) {
                $this->groupStatistic = $playerView['PlayerGroup']['Statistics']['Statistic'];
            } elseif (isset($playerView[0]['Player']['Statistics']['Statistic'])) {
                $this->groupStatistic = $playerView[0]['Player']['Statistics']['Statistic'];
            }
        }
    }

    public function getGroups()
    {
        /** in response one group */
        if (!isset($this->playerGroupResponse['PlayerGroup'])) {
            return [];
        }
        /** in response one group */
        if (isset($this->playerGroupResponse['PlayerGroup']['Players'])) {
            return [$this->playerGroupResponse['PlayerGroup']];
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

    public function findGroupPlayersAll(string $groupPrefix, string $groupSuffix = null): array
    {

        $groupsList = [];

        foreach($this->getGroups() as $group){
            $groupName = $group['@name'];
            if(!preg_match('#^'. $groupPrefix.'#', $groupName) ){
                continue;
            }
            if($groupSuffix && !preg_match('#'. $groupSuffix.'$#', $groupName) ){
                continue;
            }

            if(isset($group['Players']['Player']['@name'])) {
                $filter = '';
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
                    foreach ($player['Filter']['Constraint']??[] as $constraint) {
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
                    $filter = '-';

                    if (isset($player['Filter']['Constraint']['@id'])) {
                        if ($player['Filter']['Constraint']['@id'] === 'Date') {
                            $filter = $player['Filter']['Constraint']['Value'];
                        }
                    } elseif ( ! empty($player['Filter'])) {
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

    /**
     * @param string $name
     * @return bool|int
     */
    public function getGroupStatisticValue(string $name){
        if(!$this->groupStatistic){
            return false;
        }
        foreach($this->groupStatistic as $row){
            if($name === $row['@id']){
                return $row['$'];
            }
        }
        return 0;
    }
}
