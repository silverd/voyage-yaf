<?php

class Helper_Time
{
    /**
     * 获取时间
     * 全站时间显示规则
     * 1.在1小时内的时间，按分钟进行显示（9分钟前）
     * 2.在1天内的时间，按文字加时间显示（今天 05:12）
     * 3.在1天以外的时间，按日期加时间显示（9月7日 13:46）
     *
     * @param int $time
     * @return string 处理后的时间
     */
    public static function getTime($time)
    {
        $nowTime        = time();
        $todayBeginTime = strtotime('today');
        $beginToNow     = $nowTime - $todayBeginTime;
        $val            = max($nowTime - $time, 1);

        if ($val < 60) {
            return __('{n}秒前', array('n' => $val));
        }
        elseif ($val >= 60 && $val < (60 * 60)) {
            return __('{n}分钟前', array('n' => intval($val / 60)));
        }
        elseif ($val >= (60 * 60) && $val < (60 * 60 *24) && $beginToNow > $val) {
            return date(_('今天') . ' H:i ', $time);
        }
        else {
            return date(_('m月d日') . ' H:i ', $time);
        }
    }


    /**
     * 将秒级时间转化为x小时x分x秒的中文格式
     *
     * @param int $time
     * @return string 处理后的时间
     */
    public static function getChineseTime($time)
    {
        // 获取小时数
        $hour   = floor($time / 3600);
        $min    = floor(($time - $hour * 3600) / 60);
        $second = ceil($time - $hour * 3600 - $min * 60);

        if ($second == 60) {
            $second = 59;
        }

        $cTime = '';

        // 小时数大于0
        if ($hour > 0) {
            $cTime = $hour . _('小时');
        }

        // 如果分和秒都是0，则表示为整数小时，返回 xx小时整
        if ($min == 0 && $second == 0) {
            return $cTime;
        }

        // 如果分钟数大于0
        if ($min > 0) {
            $cTime = $cTime . $min . _('分钟');
        }

        // 如果秒数为0，则返回 xx小时（零）xx分
        if ($second == 0) {
            return $cTime;
        }

        $cTime = $cTime . $second . _('秒');

        return $cTime;
    }
}