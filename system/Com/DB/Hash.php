<?php

/**
 * 数据库分库、分表哈希算法
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Hash.php 12 2012-11-19 01:29:52Z jiangjian $
 */

class Com_DB_Hash
{
    /**
     * 获取哈希分库名
     * 后缀从 _0 到 _f，即最多分16个库，因为是对16取余数
     *
     * @param string $orgDbName
     * @param string $hashKey
     * @param int $hashNum 分库数量，最多16
     * @return string
     */
    public static function dbName($orgDbName, $hashKey, $hashNum = null)
    {
        if (! $hashKey) {
            return $orgDbName;
        }

        $dbConf = Core_Config::loadEnv('db');

        // 分库数量，缺省1
        if ($hashNum === null) {
            $hashNum = isset($dbConf[$orgDbName]['hash_num']) ? $dbConf[$orgDbName]['hash_num'] : 1;
        }

        if ($hashNum <= 1) {
            return $orgDbName;
        }

        if ($hashNum > 16) {
            throw new Core_Exception_Fatal('分库数量不能超过16个');
        }

        $dbSuffix = dechex(hexdec(substr(md5($hashKey), 0, 1)) % $hashNum);

        return $orgDbName . '_' . $dbSuffix;
    }

    /**
     * 获取哈希分表名（拆分数量无上限）
     * 后缀为十六进制，从 _0~_f, _00~_ff, _000~_fff ... 会补全0前缀
     *
     * @param string $orgTableName
     * @param string $hashKey
     * @param int $hashNum 分表数量，无上限
     * @return string
     */
    public static function tableName($orgTableName, $hashKey, $hashNum = null)
    {
        if (! $hashKey) {
            return $orgTableName;
        }

        $tblConf = Core_Config::load('table');

        // 分库数量，缺省1
        if ($hashNum === null) {
            $hashNum = isset($tblConf[$orgTableName]['hash_num']) ? $tblConf[$orgTableName]['hash_num'] : 1;
        }

        if ($hashNum <= 1) {
            return $orgTableName;
        }

        // 拆表没有上限，分表后缀是十六进制，会补全0前缀
        if (in_array($hashNum, array(16, 256, 4096))) {
            $tblSuffix = substr(md5($hashKey), 0, log($hashNum, 16));
        } else {
            $tblSuffix = str_pad(dechex(hexdec(substr(md5($hashKey), 0, 4)) % $hashNum), ceil(log($hashNum, 16)), '0', STR_PAD_LEFT);
        }

        return $orgTableName . '_' . $tblSuffix;
    }
}