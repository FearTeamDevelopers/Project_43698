<?php

namespace App\Helper;

class DateFormater
{

    const BASE_DATETIME_FORMAT = 'd.m. Y H:i';
    const BASE_DATE_FORMAT = 'd.m. Y';
    const BASE_TIME_FORMAT = 'H:i';

    /**
     * 
     * @param type $texttime
     * @return type
     */
    public static function t2dt($texttime)
    {
        if (!empty($texttime)) {
            return date(self::BASE_DATETIME_FORMAT, strtotime($texttime));
        } else {
            return date(self::BASE_DATETIME_FORMAT, time());
        }
    }

    /**
     * 
     * @param type $texttime
     * @return type
     */
    public static function t2d($texttime)
    {
        if (!empty($texttime)) {
            return date(self::BASE_DATE_FORMAT, strtotime($texttime));
        } else {
            return date(self::BASE_DATETIME_FORMAT, time());
        }
    }

    /**
     * 
     * @param type $texttime
     * @return type
     */
    public static function t2t($texttime)
    {
        if (!empty($texttime)) {
            return date(self::BASE_TIME_FORMAT, strtotime($texttime));
        } else {
            return date(self::BASE_DATETIME_FORMAT, time());
        }
    }

}
