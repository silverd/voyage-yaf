<?php

/**
 * 表单验证类
 *
 * @author ColaPHP Framework
 * @modifier JianJian <silverd@sohu.com>
 * $Id: Validate.php 9134 2014-02-24 09:21:52Z sunli $
 */

class Com_Validate
{
    protected $_error = array();

    /**
     * 缺省报错信息
     *
     * @var array
     */
    protected static $_message = array(
        'required' => '必填字段',
        'max'      => '超过最大值',
        'min'      => '小于最小值',
        'range'    => '不在范围内',
        'ip'       => 'IP格式不正确',
        'number'   => '不是一个数字',
        'int'      => '不是整型',
        'digit'    => '不是十进制数字',
        'string'   => '不是字符串',
        'email'    => '邮箱格式不正确',
        'mobile'   => '手机号格式不正确',
        'phone'    => '电话格式不正确',
        'qq'       => 'QQ号格式不正确',
        'fax'      => '传真号格式不正确',
        'zip'      => '邮编格式不正确',
        'date'     => '日期格式不正确',
    );

    /**
     * Check if is not empty
     *
     * @param string $str
     * @return bool
     */
    public static function notEmpty($str, $trim = true)
    {
        if (is_array($str)) {
            return 0 < count($str);
        }

        return strlen($trim ? trim($str) : $str) ? true : false;
    }

    /**
     * Match regex
     *
     * @param string $value
     * @param string $regex
     * @return bool
     */
    public static function match($value, $regex)
    {
        return preg_match($regex, $value) ? true : false;
    }

    /**
     * Max
     *
     * @param mixed $value numbernic|string
     * @param number $max
     * @return bool
     */
    public static function max($value, $max)
    {
        if (is_string($value)) $value = strlen($value);
        return $value <= $max;
    }

    /**
     * Min
     *
     * @param mixed $value numbernic|string
     * @param number $min
     * @return bool
     */
    public static function min($value, $min)
    {
        if (is_string($value)) {
            $value = strlen($value);
        }

        return $value >= $min;
    }

    /**
     * Range
     *
     * @param mixed $value numbernic|string
     * @param array $max
     * @return bool
     */
    public static function range($value, $range)
    {
        if (is_string($value)) {
            $value = strlen($value);
        }

        return $value >= $range[0] && $value <= $range[1];
    }

    /**
     * Check if in array
     *
     * @param mixed $value
     * @param array $list
     * @return bool
     */
    public static function in($value, $list)
    {
        return in_array($value, $list);
    }

    /**
     * Check if is email
     *
     * @param string $email
     * @return bool
     */
    public static function email($email)
    {
        // $patterns = '/^[a-z0-9_\-]+(\.[_a-z0-9\-]+)*@([_a-z0-9\-]+\.)+([a-z]{2}|aero|arpa|biz|com|coop|edu|gov|info|int|jobs|mil|museum|name|nato|net|org|pro|travel)$/';
        $patterns = '/^\w+([-.]?\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/';

        return preg_match($patterns, $email) ? true : false;
    }

    /**
     * Check if is mobile
     *
     * @param string $string
     * @return bool
     */
    public static function mobile($string)
    {
        return preg_match('/^(((1[3|4|5|8]{1}[0-9]{1}))[0-9]{8})$/', $string);
    }

    /**
     * Check if is telephone
     *
     * @param string $string
     * @return bool
     */
    public static function phone($string)
    {
        return preg_match('/^((0\d{2,3})-)?(\d{7,8})(-(\d{3,}))?$/', $string);
    }

    /**
     * Check if is QQ number
     *
     * @param string $string
     * @return bool
     */
    public static function qq($string)
    {
        return preg_match('/^\d{4,}$/', $string);
    }

    /**
     * Check if is zip/postcode
     *
     * @param string $string
     * @return bool
     */
    public static function zip($string)
    {
        return preg_match('/^\d{6}$/', $string);
    }

    /**
     * Check if is fax
     *
     * @param string $string
     * @return bool
     */
    public static function fax($string)
    {
        return preg_match('/^((0\d{2,3})-)?(\d{7,8})(-(\d{3,}))?$/', $string);
    }

    /**
     * Check if is url
     *
     * @param string $url
     * @return bool
     */
    public static function url($url)
    {
        return preg_match('/^((https?|ftp|news):\/\/)?([a-z]([a-z0-9\-]*\.)+([a-z]{2}|aero|arpa|biz|com|coop|edu|gov|info|int|jobs|mil|museum|name|nato|net|org|pro|travel)|(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]))(\/[a-z0-9_\-\.~]+)*(\/([a-z0-9_\-\.]*)(\?[a-z0-9+_\-\.%=&amp;]*)?)?(#[a-z][a-z0-9_]*)?$/i', $url) ? true : false;
    }

    /**
     * Check if is ip
     *
     * @param string $ip
     * @return bool
     */
    public static function ip($ip)
    {
        return ((false === ip2long($ip)) || (long2ip(ip2long($ip)) !== $ip)) ? false : true;
    }

    /**
     * Check if is date
     *
     * @param string $date
     * @return bool
     */
    public static function date($date)
    {
        return preg_match('/^\d{4}[\/-]\d{1,2}[\/-]\d{1,2}$/', $date) ? true : false;
    }

    /**
     * Check if is numbers
     *
     * @param mixed $value
     * @return bool
     */
    public static function number($value)
    {
        return is_numeric($value);
    }

    /**
     * Check if is int
     *
     * @param mixed $value
     * @return bool
     */
    public static function int($value)
    {
        return is_int($value);
    }

    /**
     * 是否十进制数
     *
     * @param mixed $value
     * @return bool
     */
    public static function digit($value)
    {
        return is_int($value) || ctype_digit($value);
    }

    /**
     * Check if is string
     *
     * @param mixed $value
     * @return bool
     */
    public static function string($value)
    {
        return is_string($value);
    }

    /**
     * 检测用户名是否合法
     * 规则：如果纯英文/数字则1~9个字符，含汉字则1~6个字符, 含“.”
     *
     * @param string $value
     * @return bool
     */
    public static function userName($value)
    {
        $value = trim($value);

        if (! $value && $value !== '0') {
            return false;
        }

        if (preg_match('/^[A-Za-z0-9·]{1,9}$/', $value)) {
            return true;
        }

        if (preg_match('/^[\x{4E00}-\x{9FA5}A-Za-z0-9·]{1,6}$/u', $value)) {
            return true;
        }

        if (preg_match('/^[\x{4E00}-\x{9FA5}A-Za-z0-9·]{1,5}[A-Za-z0-9·]{0,2}$/u', $value)) {
            return true;
        }

        return false;
    }

    /**
     * 检测密码是否合法
     * 规则：纯英文/数字6~16位组成
     *
     * @param string $value
     * @return bool
     */
    public static function password($value)
    {
        if (! $value = trim($value)) {
            return false;
        }

        return preg_match('/^[A-Za-z0-9]{6,16}$/', $value) ? true : false;
    }

    /**
     * Check
     *
     * $rules = array(
     *     'required' => true if required , false for not
     *     'type'     => var type, should be in ('email', 'mobile', 'phone', 'qq', 'fax', 'zip', 'url', 'ip', 'date', 'number', 'int', 'string')
     *     'regex'    => regex code to match
     *     'func'     => validate function, use the var as arg
     *     'max'      => max number or max length
     *     'min'      => min number or min length
     *     'range'    => range number or range length
     *     'msg'      => error message, can be as an array
     * )
     *
     * @param array $data
     * @param array $rules
     * @param boolean $ignorNotExists
     * @return bool
     */
    public function check($data, $rules, $ignorNotExists = false)
    {
        foreach ($rules as $key => $rule) {
            // 备注：用加号+合并数组的特性，如果key重复，后者不会覆盖前者
            $rule += array('required' => false, 'msg' => self::$_message);

            // deal with not existed
            if (! isset($data[$key])) {
                if (! $rule['required']) continue;
                if ($ignorNotExists) continue;
                $this->_error[$key] = $this->_msg($rule, 'required');
                continue;
            }

            $value = $data[$key];

            $result = self::_check($value, $rule);

            if (0 !== $result['code']) {
                $this->_error[$key] = $result['msg'];
            }

            if (isset($rule['rules'])) {
                $this->check($value, $rule['rules'], $ignorNotExists);
            }
        }

        return $this->_error ? false : true;
    }

    /**
     * Check value
     *
     * @param mixed $value
     * @param array $rule
     * @return mixed string as error, true for OK
     */
    protected function _check($value, $rule)
    {
        if ($rule['required'] && ! self::notEmpty($value)) {
            return array('code' => -1, 'msg' => $this->_msg($rule, 'required'));
        }

        if (isset($rule['func']) && ! call_user_func($rule['func'], $value)) {
            return array('code' => -1, 'msg' => $this->_msg($rule, 'func'));
        }

        if (isset($rule['regex']) && ! self::match($value, $rule['regex'])) {
            return array('code' => -1, 'msg' => $this->_msg($rule, 'regex'));
        }

        if (isset($rule['type']) && ! self::$rule['type']($value)) {
            return array('code' => -1, 'msg' => $this->_msg($rule, $rule['type']));
            // 备注：上一行传 $rule['type'] 而不传 type 的原因是为了显示 $this->_message 中的默认信息
        }

        $acts = array('max', 'min', 'range', 'in');
        foreach ($acts as $act) {
            if (isset($rule[$act]) && ! self::$act($value, $rule[$act])) {
                return array('code' => -1, 'msg' => $this->_msg($rule, $act));
            }
        }

        if (isset($rule['each'])) {
            $rule['each'] += array('required' => false, 'msg' => self::$_message);
            if (isset($rule['msg'])) {
                $rule['each'] += array('msg' => $rule['msg']);
            }
            foreach ($value as $item) {
                $result = $this->_check($item, $rule['each']);
                if (0 !== $result['code']) {
                    return $result;
                }
            }
        }

        return array('code' => 0);
    }

    /**
     * Get error message
     *
     * @param array $rule
     * @param string $name
     * @return string
     */
    protected function _msg($rule, $name)
    {
        if (empty($rule['msg'])) {
            return 'INVALID';
        }

        if (is_string($rule['msg'])) {
            return $rule['msg'];
        }

        return isset($rule['msg'][$name]) ? $rule['msg'][$name] : 'INVALID';
    }

    /**
     * Get error
     *
     * @return array
     */
    public function error()
    {
        return $this->_error;
    }
}

/*
Example:

$postData = array(
    'title' => 'a',
    'email' => '123@456.com',
    'mobile' => '13524524520',
);

// 表单验证
$rules = array(
    'title' => array(
        'required' => true,
        'msg' => '请填写活动标题',
    ),
    'mobile' => array(
        'required' => true,
        'type' => 'mobile',
        'msg' => array(
            'required' => '请填写手机',
            'mobile' => '手机格式不合法',
        ),
    ),
    'email' => array(
        'required' => true,
        'type' => 'email',
        'msg' => array(
            'required' => '请填写邮箱',
            'email' => '邮箱格式不合法',
        ),
    ),
);

$validateObj = new Com_Validate();
if (! $validateObj->check($postData, $rules)) {
    print_r($validateObj->error());
    exit;
}
 */