<?php

namespace uldisn\sharkscope\components;

class ResponseHelper
{
    /** @var  array */
    public $playerGroupResponse = [];

    /** @var  array */
    public $playerStatistic = [];
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


}