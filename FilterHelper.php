<?php

namespace uldisn\sharkscope;


class FilterHelper
{

    public static function dateActualMonth($filter = [])
    {
        $firstDay = new \DateTime('first day of this month 00:00:00');
        $lastDay = new \DateTime('first day of next month 00:00:00');

        $filter[] = 'Date:' . $firstDay->format('U') . '~' . $lastDay->format('U');

        return $filter;

    }

    /**
     * @param array $filter
     * @param \DateTime $from
     * @param \DateTime $to
     * @return array
     */
    public static function dateFromTo($filter = [], $from, $to)
    {

        $filter[] = 'Date:' . self::createDateFromToValue($from, $to);

        return $filter;

    }

    /**
     * @param \DateTime $from
     * @param \DateTime $to
     * @return string
     */
    public static function createDateFromToValue($from, $to){
        return $from->format('U') . '~' . $to->format('U');
    }

}