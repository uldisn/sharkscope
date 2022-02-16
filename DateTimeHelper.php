<?php

namespace uldisn\sharkscope;


use DateTime;
use DateTimeZone;

class DateTimeHelper
{

    //public const SHARK_SERVER_TIMEZONE = 'America/Los_Angeles';
    //public const SHARK_SERVER_TIMEZONE = 'UTC';
    public const SHARK_SERVER_TIMEZONE = 'EET';

    public static function getDateTime(int $timestamp, string $timezone): DateTime
    {
        $dateTime = DateTime::createFromFormat('U', $timestamp,new DateTimeZone(self::SHARK_SERVER_TIMEZONE));
        $dateTime->setTimezone(new DateTimeZone($timezone));
        return $dateTime;
    }
}
