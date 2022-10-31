<?php

namespace uldisn\sharkscope;


use DateTime;

class FilterHelper
{

    /**
     * maximal date for SharkScope. In date  filter use as to, if unknown end date.
     * Actula for adding/removing to/from groups.
     */
    const SHARK_SCOPE_ENDDATE = '2031-12-23';

    public static function dateActualMonth($filter = [])
    {
        $firstDay = new DateTime('first day of this month 00:00:00');
        $lastDay = new DateTime('first day of next month 00:00:00');

        $filter[] = 'Date:' . self::createDateFromToValue($firstDay, $lastDay);

        return $filter;

    }

    public static function dateActualQuarter($filter = [])
    {
        $thisQuarter = self::getQuarterStartAndEnd(date('m'), date('Y'));

        $firstDay = new DateTime(date("c", $thisQuarter['startDate']));
        $lastDay = new DateTime(date("c", $thisQuarter['endDate']));

        $filter[] = 'Date:' . self::createDateFromToValue($firstDay, $lastDay);

        return $filter;

    }

    public static function dateActualYear($filter = [])
    {
        $yearFirstDay = new DateTime('now');
        $yearFirstDay->setDate($yearFirstDay->format('Y'), 1, 1);

        $yearLastDay = new DateTime('now');
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
    public static function createDateFromToValue(DateTime $fromDate, DateTime $toDate = null): string
    {
        if(!$toDate){
            $toDate = new DateTime(self::SHARK_SCOPE_ENDDATE);
        }
        return self::dToU($fromDate) . '~' . self::dToU($toDate);
    }

    /**
     * convert DateTime date part to Unix time stamp
     * @param \DateTime $dateTime
     * @return int
     */
    private static function dToU(DateTime $dateTime): int
    {
        [$year, $month, $day ] = explode('-', $dateTime->format('Y-m-d'));
        return gmmktime(0, 0, 0, $month, $day, $year);
    }

    /**
     * get start and end timestamps for the given month
     * @param int $month
     * @param int $year
     * @return array
     */
    private static function getQuarterStartAndEnd($month, $year){
        if ($month >= 1 && $month <= 3 ){
            $startDate = strtotime('1-January-'.$year);  // 1-Januray 12:00:00 AM
            $endDate   = strtotime('1-April-'.$year);  // 1-April 12:00:00 AM means end of 31 March
        } else if ($month >= 4 && $month <= 6) {
            $startDate = strtotime('1-April-'.$year);  // 1-April 12:00:00 AM
            $endDate   = strtotime('1-July-'.$year);  // 1-July 12:00:00 AM means end of 30 June
        } else if ($month >= 7 && $month <= 9) {
            $startDate = strtotime('1-July-'.$year);  // 1-July 12:00:00 AM
            $endDate   = strtotime('1-October-'.$year);  // 1-October 12:00:00 AM means end of 30 September
        } else if ($month >= 10 && $month <= 12 ) {
            $startDate = strtotime('1-October-'.$year);  // 1-October 12:00:00 AM
            $endDate   = strtotime('1-January-'.($year+1));  // 1-January Next year 12:00:00 AM means end of 31 December this year
        }

        return compact('startDate', 'endDate');
    }

}
