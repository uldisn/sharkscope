<?php

namespace uldisn\sharkscope;


class FilterHelper
{

    /**
     * maximal date for SharkScope. In date  filter use as to, if unknown end date.
     * Actula for adding/removing to/from groups.
     */
    const SHARK_SCOPE_END_DATE = '2031-12-23';

    /**
     * @return DateTimeZone
     */
    public static function gmtTimeZone()
    {
        return new \DateTimeZone("GMT");
    }

    public static function dateActualMonth($filter = [])
    {
        $firstDay = new \DateTime('first day of this month 00:00:00', self::gmtTimeZone());
        $lastDay = new \DateTime('first day of next month 00:00:00', self::gmtTimeZone());

        $filter[] = 'Date:' . $firstDay->format('U') . '~' . $lastDay->format('U');

        return $filter;

    }

    /**
     * @param array $filter
     * @param \DateTime $fromDate
     * @param \DateTime|null $toDate
     * @return array
     */
    public static function dateFromTo($filter = [], $fromDate, $toDate = null)
    {

        $filter[] = 'Date:' . self::createDateFromToValue($fromDate, $toDate);

        return $filter;

    }

    /**
     * @param \DateTime $fromDate
     * @param \DateTime|null $toDate
     * @return string
     */
    public static function createDateFromToValue($fromDate, $toDate = null){
        if(!$toDate){
            $toDate = new \DateTime(self::SHARK_SCOPE_END_DATE, self::gmtTimeZone());
        }
        return $fromDate->format('U') . '~' . $toDate->format('U');
    }

}