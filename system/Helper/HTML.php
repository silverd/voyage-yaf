<?php

/**
 * HTML/模板处理函数
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: HTML.php 3039 2013-05-24 04:24:44Z jiangjian $
 */

class Helper_HTML
{
    /**
     * 根据数组生成下拉框HTML
     *
     * @param array $xArr
     * @param string|array $selected 默认选中的值
     * @param string $vIndex
     * @return html string
     */
    public static function getSelectMenu($xArr, $selected = null, $vIndex = null)
    {
        $html = '';

        if ($xArr) {
            foreach ($xArr as $key => $value) {
                $_selected = $selected !== null ? (is_array($selected) ? in_array($key, $selected) : $key == $selected) : 0;
                $_selectedStr = $_selected ? 'selected="selected"' : '';
                if ($vIndex !== null) {
                    $value = $value[$vIndex];
                }
                $html .= "<option value=\"{$key}\" {$_selectedStr}>{$value}</option>";
            }
        }

        return $html;
    }

    /**
     * 根据数组生成下拉框HTML
     * ThinkPHP 风格下拉选项的数组
     *
     * @param array $configs
     * @param string|array|bool $selected 默认选中的值, false表示一个不选中
     * @return html string
     */
    public static function getSelectMenu2($configs, $selected = null)
    {
        $html = '';

        if ($configs) {
            foreach ($configs as $value) {
                if ($selected === null) {
                    $_selected = (bool) $value['config_default'];
                }
                else {
                    $_selected =  is_array($selected) ? in_array($value['id'], $selected) : ($value['id'] == $selected);
                }
                $_selectedStr = $_selected ? 'selected="selected"' : '';
                $html .= "<option value=\"{$value['id']}\" {$_selectedStr}>{$value['config_value']}</option>";
            }
        }

        return $html;
    }
}