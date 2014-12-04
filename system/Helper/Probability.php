<?php

/**
 * 概率计算类
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Probability.php 10376 2014-04-09 17:42:44Z jiangjian $
 */

class Helper_Probability
{
    /**
     * 全概率计算
     *
     * @param array $probArr = array(
     *            'a' => 5000,
     *            'b' => 2000,
     *            'c' => 3000,
     *        )
     * @return string 返回命中的 key
     */
    public static function hitByWeight($probArr)
    {
        if (count($probArr) == 1) {
            return key($probArr);
        }

        // 概率数组的总概率精度
        $proSum = array_sum($probArr);

        // 概率数组循环
        foreach ($probArr as $key => $weight) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $weight) {
                return $key;
            }
            $proSum -= $weight;
        }

        return false;
    }

    /**
     * @author sanguomobile
     */
    public static function hitByWeight2($probArr, $base = 10000)
    {
        if (count($probArr) == 1) {
            return key($probArr);
        }

        static $arr = array();

        $key = md5(serialize($probArr));

        if (!isset($arr[$key])) {
            $max = array_sum($probArr);
            foreach ($probArr as $k => $weight) {
                $weight = $weight / $max * $base;
                for ($i = 0; $i < $weight; $i++) {
                    $arr[$key][] = $k;
                }
            }
        }

        return $arr[$key][mt_rand(0, count($arr[$key]) - 1)];
    }

    /**
     * @author zhengjiang
     */
    public static function hitByWeight3($probArr)
    {
        if (count($probArr) == 1) {
            return key($probArr);
        }

        // 概率数组的总概率精度
        $proSum = array_sum($probArr);
        $randNum = mt_rand(1, $proSum);

        $endWeight = 0;

        // 概率数组循环
        foreach ($probArr as $key => $weight) {
            $endWeight += $weight;
            if ($randNum <= $endWeight) {
                return $key;
            }
        }

        return false;
    }

    /**
     * @author sanguomobile
     *
     * 注意：传入的 weight 必须为小数
     */
    public static function hitByWeight4($probArr)
    {
        if (count($probArr) == 1) {
            return key($probArr);
        }

        // 概率数组的总概率精度
        $proSum = array_sum($probArr);

        $p = $proSum * lcg_value();

        foreach ($probArr as $key => $weight){
            if ($p <= $weight) {
                return $key;
            }
            $p -= $weight;
        }

        return false;
    }

    public static function hitByWeightField($probArr, $weightField = 'weight')
    {
        if (count($probArr) == 1) {
            return key($probArr);
        }

        // 概率数组的总概率精度
        $proSum = Helper_Array::sum($probArr, $weightField);

        // 概率数组循环
        foreach ($probArr as $key => $value) {
            $weight = $value[$weightField];
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $weight) {
                return $key;
            }
            $proSum -= $weight;
        }

        return false;
    }

    /**
     * 是否命中（万分之几）
     *
     * @param int $prob
     * @param int $base 基数
     * @return bool
     */
    public static function isHit($prob, $base = 10000)
    {
        if ($prob >= $base) {
            return true;
        }

        return self::isLuckyDog($prob / $base);
    }

    /**
     * 是否命中（传入小数）
     * 参数以小数表示，例如 0.03 表示 3%，0.003 表示千分之3，0.0003 表示万分之三
     *
     * @param decimal $prob 小数
     * @return bool
     *
     * @author zhangkai
     */
    public static function isLuckyDog($prob)
    {
        if ($prob <= 0) {
            return false;
        }

        // 如果命中率大于等于1，则肯定中奖，直接返回true
        if ($prob >= 1) {
            return true;
        }

        // 判断小数点后有几位，即这个概率的精度是多少
        $digits = strlen($prob) - 2;

        // 求出为满足这个精度，需要投入的总种子数
        $totalSeeds = pow(10, $digits);

        // 求出在当前概率下，幸运种子的数量是多少
        $luckyRangeMax = $totalSeeds * $prob;

        // 从总种子中随机抽取一个种子
        $luckySeed = mt_rand(1, $totalSeeds);

        // 如果抽取出的种子，是幸运种子中的一颗，则返回true
        if ($luckySeed <= $luckyRangeMax) {
            return true;
        }
        // 如果取到的不是幸运种子而是普通种子，返回false
        else {
            return false;
        }
    }
}