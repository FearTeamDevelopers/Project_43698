<?php

namespace App\Helper;

class DateFormater
{

    const BASE_DATETIME_FORMAT = 'j.m. Y H:i';
    const BASE_DATE_FORMAT = 'j.m. Y';
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
            return date(self::BASE_DATE_FORMAT, time());
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
            return date(self::BASE_TIME_FORMAT, time());
        }
    }

    /**
     * 
     * @param type $date
     */
    public static function g2dn($date)
    {
        if (!empty($date)) {
            return date('j', strtotime($date));
        } else {
            return date('j', time());
        }
    }
    
    /**
     * 
     * @param type $date
     */
    public static function g2mn($date)
    {
        if (!empty($date)) {
            return date('F', strtotime($date));
        } else {
            return date('F', time());
        }
    }
}
