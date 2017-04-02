<?php

namespace uldisn\sharkscope\components;


class FilterHelper
{

    public static function dateActualMonth($filter = [])
    {
        $firstDay = new \DateTime('first day of this month 00:00:00');
        $lastDay = new \DateTime('first day of next month 00:00:00');

        $filter[] = 'Date:' . $firstDay->format('U') . '~' . $lastDay->format('U');

        return $filter;

    }

}