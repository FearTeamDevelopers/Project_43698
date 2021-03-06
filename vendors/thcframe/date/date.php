<?php

namespace THCFrame\Date;

/**
 * 
 */
class Date
{

    const CZ_BASE_DATETIME_FORMAT = 'j.n. Y H:i';
    const CZ_BASE_DATE_FORMAT = 'j.n. Y';
    const CZ_BASE_TIME_FORMAT = 'H:i';
    const SYSTEM_BASE_DATETIME_FORMAT = 'Y-m-d H:i:s';
    const SYSTEM_BASE_DATE_FORMAT = 'Y-m-d';
    const SYSTEM_BASE_TIME_FORMAT = 'H:i:s';
    
    const FULL_MONTHS_NAMES = 1;
    const SHORT_MONTHS_NAMES = 2;

    private static $instance = null;

    private function __construct()
    {
        
    }

    /**
     * @return Date|null
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param $datetime
     * @return int
     * @throws \Exception
     */
    public function getTimestamp($datetime)
    {
        $date = new \DateTime($datetime);

        return $date->getTimestamp();
    }

    /**
     * 
     * @return array
     */
    public function getEnMonths($type = 1)
    {
        if($type == self::FULL_MONTHS_NAMES){
            return [1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        }else{
            return [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        }
    }
    
    /**
     * 
     * @return array
     */
    public function getCzMonths($type = 1)
    {
        if($type == self::FULL_MONTHS_NAMES){
            return [1 => 'Leden', 'Únor', 'Březen', 'Duben', 'Květen', 'Červen', 'Červenec', 'Srpen', 'Září', 'Říjen', 'Listopad', 'Prosinec'];
        }else{
            return [1 => 'Led', 'Úno', 'Bře', 'Dub', 'Kvě', 'Čer', 'Čec', 'Srp', 'Zář', 'Říj', 'Lis', 'Pro'];
        }
    }

    /**
     * @param $datetime
     * @param string $format
     * @return false|string
     */
    public function format($datetime, $format = 'Y-m-d H:i:s')
    {
        if(empty($datetime)){
            return date($format, time());
        }
        
        return date($format, strtotime($datetime));
    }

    /**
     * @param string $format
     * @return false|string
     * @throws Exception\Argument
     */
    public function getFormatedCurDate($format = 'cz')
    {
        if (strtolower($format) == 'cz') {
            return $this->format(date('Y-m-d'), self::CZ_BASE_DATE_FORMAT);
        } elseif (strtolower($format) == 'system') {
            return $this->format(date('Y-m-d'), self::SYSTEM_BASE_DATE_FORMAT);
        } else {
            throw new \THCFrame\Date\Exception\Argument('Unsupported date format');
        }
    }

    /**
     * @param string $format
     * @return false|string
     * @throws Exception\Argument
     */
    public function getFormatedCurDatetime($format = 'cz')
    {
        if (strtolower($format) == 'cz') {
            return $this->format(date('Y-m-d H:i:s'), self::CZ_BASE_DATETIME_FORMAT);
        } elseif (strtolower($format) == 'system') {
            return $this->format(date('Y-m-d H:i:s'), self::SYSTEM_BASE_DATETIME_FORMAT);
        } else {
            throw new \THCFrame\Date\Exception\Argument('Unsupported datetime format');
        }
    }

    /**
     * @param $date
     * @param string $lang
     * @param int $type
     * @return mixed
     */
    public function getMonthName($date, $lang = 'cz', $type = 1)
    {
        if($lang == 'cz'){
            $months = $this->getCzMonths($type);
        }else{
            $months = $this->getEnMonths($type);
        }

        if (!empty($date)) {
            $month = date('n', strtotime($date));
            return $months[$month];
        } else {
            $month = date('n', time());
            return $months[$month];
        }
    }

    /**
     * @param $datetime
     * @param $part
     * @return false|string
     */
    public function getDatePart($datetime, $part)
    {
        if (!empty($datetime)) {
            if ($part == 'day') {
                return $this->format($datetime, 'j');
            } elseif ($part == 'month') {
                return $this->format($datetime, 'n');
            } elseif ($part == 'year') {
                return $this->format($datetime, 'Y');
            }
        } else {
            if ($part == 'day') {
                return date('j', time());
            } elseif ($part == 'month') {
                return date('n', time());
            } elseif ($part == 'year') {
                return date('Y', time());
            }
        }
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param bool $useSign
     * @return int
     * @throws \Exception
     */
    public function datediff($startDate, $endDate, $useSign = true)
    {
        $datetime1 = new \DateTime($startDate);
        $datetime2 = new \DateTime($endDate);
        $interval = $datetime1->diff($datetime2);

        if ($useSign) {
            return (int) $interval->format('%r%a');
        } else {
            return (int) $interval->format('%a');
        }
    }

    /**
     * @param $originalDate
     * @param string $format
     * @param int $years
     * @param int $months
     * @param int $days
     * @param int $hours
     * @param int $minutes
     * @param int $seconds
     * @return string
     * @throws \Exception
     */
    public function dateAdd($originalDate, $format = 'Y-m-d', $years = 0, $months = 0, $days = 0, $hours = 0, $minutes = 0, $seconds = 0)
    {
        $date = new \DateTime($originalDate);

        $intervalSpec = 'P';

        if ($years > 0) {
            $intervalSpec .= $years . 'Y';
        }

        if ($months > 0) {
            $intervalSpec .= $months . 'M';
        }

        if ($days) {
            $intervalSpec .= $days . 'D';
        }

        if ($hours > 0 || $minutes > 0 || $seconds > 0) {
            $intervalSpec .= 'T';

            if ($hours > 0) {
                $intervalSpec .= $hours . 'H';
            }

            if ($minutes > 0) {
                $intervalSpec .= $minutes . 'M';
            }

            if ($seconds > 0) {
                $intervalSpec .= $seconds . 'S';
            }
        }

        $date->add(new \DateInterval($intervalSpec));
        return $date->format($format);
    }

    /**
     * Return days of month
     * 
     * @param int $month
     * @param int $year
     * @return array
     */
    public function getMonthDays($month = null, $year = null)
    {
        if ($month === null || $month === '') {
            $month = date('m');
        }
        
        if($year === null || $year === ''){
            $year = date('Y');
        }

        $days = [];
        $daysOfMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        for ($i = 1; $i <= $daysOfMonth; $i++) {
            $tm = mktime(0, 0, 0, $month, $i, $year);
            $days[$i] = [
                'day' => date('d', $tm),
                'dayname' => date('D', $tm),
                'weekofyear' => date('W', $tm),
                'month' => date('F', $tm),
                'daysofmonth' => $daysOfMonth
            ];
        }
        
        return $days;
    }
    
    /**
     * Return first day of month
     * 
     * @param int $month
     * @param int $year
     * @return date
     */
    public function getFirstDayOfMonth($month = null, $year = null)
    {
        if ($month === null) {
            $month = date('m');
        }
        
        if($year === null){
            $year = date('Y');
        }
        
        $firstDayUTS = mktime(0, 0, 0, $month, 1, $year);
        $firstDay = date('Y-m-d', $firstDayUTS);
        
        return $firstDay;
    }
    
    /**
     * Return last day of month
     * 
     * @param int $month
     * @param int $year
     * @return date
     */
    public function getLastDayOfMonth($month = null, $year = null)
    {
        if ($month === null) {
            $month = date('m');
        }
        
        if($year === null){
            $year = date('Y');
        }
        
        $lastDayUTS = mktime(0, 0, 0, $month, date('t'), $year);
        $lastDay = date('Y-m-d', $lastDayUTS);
        
        return $lastDay;
    }
}
