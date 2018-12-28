<?php
namespace app\index\controller;

class Time{
    public static function now(){
        return time();
    }

    public static function format($time,$case=1){
        switch ($case){
            case '1':
                $format='Y-m-d H:i';
                break;
            case '2':
                $format='Y-m-d H:i:s';
                break;
        }
        return Date($format,$time);
    }

    public static function today_start(){
        $time = self::now();
        return mktime(0,0,0,date("m",$time),date("d",$time),date("Y",$time));
    }

    public static function today_end(){
        $time = self::now();
        return mktime(23,59,59,date("m",$time),date("d",$time),date("Y",$time));
    }

    //本月第一天
    public static function month_start($date=false){
        if($date==false)$date=date('Y-m');
        return date('Y-m-01', strtotime("$date"));
    }

    //某月最后一天
    public static function month_end($date=false){
        if($date==false)$date=date('Y-m');
        return date('Y-m-d', strtotime("$date +1 month -1 day"));
    }

    /**
     * 返回两个日期相差多少天
     */
    public static function countDays($start,$end){
        $d1 = strtotime($start);
        $d2 = strtotime($end);
        $days= round(($d2-$d1)/3600/24);
        return $days-1;
    }

    /**
     * 返回昨日开始和结束的时间戳
     */
    public static function yesterday()
    {
        $yesterday = date('d') - 1;
        return [
            mktime(0, 0, 0, date('m'), $yesterday, date('Y')),
            mktime(23, 59, 59, date('m'), $yesterday, date('Y'))
        ];
    }

    /**
     * 返回前几日开始和结束的时间戳
     */
    public static function yesterdayNum($num = 1)
    {
        $yesterday = date('d') - $num;
        return [
            mktime(0, 0, 0, date('m'), $yesterday, date('Y')),
            mktime(23, 59, 59, date('m'), $yesterday, date('Y'))
        ];
    }

    /**
     * 返回本周开始和结束的时间戳
     */
    public static function week()
    {
        $timestamp = time();
        return [
            strtotime(date('Y-m-d', strtotime("this week Monday", $timestamp))),
            strtotime(date('Y-m-d', strtotime("this week Sunday", $timestamp))) + 24 * 3600 - 1
        ];
    }

    /**
     * 返回上周开始和结束的时间戳
     */
    public static function lastWeek()
    {
        $timestamp = time();
        return [
            strtotime(date('Y-m-d', strtotime("last week Monday", $timestamp))),
            strtotime(date('Y-m-d', strtotime("last week Sunday", $timestamp))) + 24 * 3600 - 1
        ];
    }

    /**
     * 返回本月开始和结束的时间戳
     */
    public static function month($everyDay = false)
    {
        return [
            mktime(0, 0, 0, date('m'), 1, date('Y')),
            mktime(23, 59, 59, date('m'), date('t'), date('Y'))
        ];
    }

    /**
     * 返回上个月开始和结束的时间戳
     */
    public static function lastMonth()
    {
        $begin = mktime(0, 0, 0, date('m') - 1, 1, date('Y'));
        $end = mktime(23, 59, 59, date('m') - 1, date('t', $begin), date('Y'));

        return [$begin, $end];
    }

    /**
     * 返回今年开始和结束的时间戳
     */
    public static function year()
    {
        return [
            mktime(0, 0, 0, 1, 1, date('Y')),
            mktime(23, 59, 59, 12, 31, date('Y'))
        ];
    }

    /**
     * 获取几天前零点到现在/昨日结束的时间戳
     *
     * @param int $day 天数
     * @param bool $now 返回现在或者昨天结束时间戳
     * @return array
     */
    public static function dayToNow($day = 1, $now = true)
    {
        $end = time();
        if (!$now) {
            list($foo, $end) = self::yesterday();
        }

        return [
            mktime(0, 0, 0, date('m'), date('d') - $day, date('Y')),
            $end
        ];
    }

    /**
     * 返回几天前的时间戳
     *
     * @param int $day
     * @return int
     */
    public static function daysAgo($day = 1)
    {
        $nowTime = time();
        return $nowTime - self::daysToSecond($day);
    }

    /**
     * 返回几天后的时间戳
     *
     * @param int $day
     * @return int
     */
    public static function daysAfter($day = 1)
    {
        $nowTime = time();
        return $nowTime + self::daysToSecond($day);
    }

    /**
     * 判断某年的某月有多少天
     * @return [type] [description]
     */
    public static function getDaysInMonth($year = '', $month = '')
    {
        if (empty($year)) $year = date('Y');
        if (empty($month)) $month = date('m');
        if (in_array($month, array(1, 3, 5, 7, 8, '01', '03', '05', '07', '08', 10, 12))) {
            $text = '31';//月大
        } elseif ($month == 2 || $month == '02') {
            if (($year % 400 == 0) || (($year % 4 == 0) && ($year % 100 !== 0))) {//判断是否是闰年
                $text = '29';//闰年2月
            } else {
                $text = '28';//平年2月
            }
        } else {
            $text = '30';//月小
        }
        return $text;
    }

}