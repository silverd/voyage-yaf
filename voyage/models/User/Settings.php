<?php

/**
 * 我的个人设置 模型
 *
 * @author sunli
 * $Id: Settings.php 6985 2013-11-21 03:41:21Z jiangjian $
 */

class Model_User_Settings extends Model_User_Trait
{
    /**
     * 子类构函
     *
     * @return void
     */
    protected function _initTrait()
    {
        $this->_prop = $this->DaoDs('UserSettings')->get($this->_uid);

        unset($this->_prop['id'], $this->_prop['uid']);
    }

    /**
     * 修改我的设置
     *
     * @param array $setArr
     * @return bool
     */
    public function update(array $setArr)
    {
        if (! $setArr) {
            return false;
        }

        // 断言 setArr 中的 value 不能为数组
        $this->assertValueNotArray($setArr);

        // 执行更新
        if ($result = $this->DaoDs('UserSettings')->updateByPk($setArr, $this->_uid)) {
            // 更新 prop 数组
            $this->set($setArr);
        }

        return $result;
    }
}