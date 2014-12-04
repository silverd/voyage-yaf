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
     * @param string $xArr
     * @param string|array $selected 默认选中的值
     * @param string $vIndex
     * @return html string
     */
    public static function getSelectMenu($xArr, $selected = false, $vIndex = null)
    {
        $html = '';

        if ($xArr) {
            foreach ($xArr as $key => $value) {
                $_selected = $selected !== false ? (is_array($selected) ? in_array($key, $selected) : $key == $selected) : 0;
                $_selectedStr = $_selected ? 'selected="selected"' : '';
                if ($vIndex !== null) {
                    $value = $value[$vIndex];
                }
                $html .= "<option value=\"{$key}\" {$_selectedStr}>{$value}</option>";
            }
        }

        return $html;
    }
}