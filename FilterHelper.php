<?php

namespace uldisn\sharkscope;


class FilterHelper
{

    /**
     * maximal date for SharkScope. In date  filter use as to, if unknown end date.
     * Actula for adding/removing to/from groups.
     */
    const SHARK_SCOPE_END_DATE = '2031-12-23';

    public static function dateActualMonth($filter = [])
    {
        $firstDay = new \DateTime('first day of this month 00:00:00');
        $lastDay = new \DateTime('first day of next month 00:00:00');

        $filter[] = 'Date:' . self::createDateFromToValue($firstDay, $lastDay);

        return $filter;

    }

    public static function dateActualYear($filter = [])
    {
        $yearFirstDay = new \DateTime('now');
        $yearFirstDay->setDate($yearFirstDay->format('Y'), 1, 1);

        $yearLastDay = new \DateTime('now');
        $yearLastDay->setDate((int)$yearLastDay->format('Y') + 1, 1, 1);

        $filter[] = 'Date:' . self::createDateFromToValue($yearFirstDay, $yearLastDay);

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
            $toDate = new \DateTime(self::SHARK_SCOPE_END_DATE);
        }
        return self::dToU($fromDate) . '~' . self::dToU($toDate);
    }

    /**
     * convert DateTime date part to Unix time stamp
     * @param \DateTime $dateTime
     * @return int
     */
    private static function dToU($dateTime){
        list($year, $month, $day ) = explode('-', $dateTime->format('Y-m-d'));
        return gmmktime(0, 0, 0, $month, $day, $year);
    }

}