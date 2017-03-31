<?php


namespace uldisn\sharkscope\components;


class Helper
{
    /**
     * @param $encodedPassword
     * @param $aplicationKey
     * @return string
     */
    public static function passwordReEncoding($encodedPassword, $aplicationKey)
    {
        return md5(strtolower($encodedPassword).$aplicationKey);
    }


}