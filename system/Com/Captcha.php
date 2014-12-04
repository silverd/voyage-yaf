<?php

/**
 * 验证码生成、检测
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Captcha.php 12 2012-11-19 01:29:52Z jiangjian $
 */

class Com_Captcha
{
    /**
     * 默认图片宽度
     */
    private $_width = 87;

    /**
     * 默认图片高度
     */
    private $_height = 23;

    /**
     * 默认命名空间
     */
    private $_space = 'default';

    /**
     * 存储本地的 key 前缀
     */
    private $_prefix = '__vcode_';

    /**
     * 验证码字体
     */
    private $_font;

    /**
     * 验证码几位
     */
    private $_length = 5;

    /**
     * 图片实例
     */
    private $_image;

    /**
     * 初始化配置
     *
     * @param array $config
     */
    public function __construct($config = array())
    {
        if ($config) {
            foreach (array('width', 'height', 'space', 'font', 'prefix', 'length') as $key) {
                if (isset($config[$key]) && $config[$key]) {
                    $this->{'_' . $key} = $config[$key];
                }
            }
        }

        // 缺省字体
        if (! $this->_font) {
            $this->_font = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Captcha' . DIRECTORY_SEPARATOR . 'svenings.ttf';
        }
    }

    /**
     * 验证字符串是否正确
     *
     * @param   string  $word
     * @return  bool
     */
    public function check($word)
    {
        $word = strtoupper($word);
        $storedCode = $this->_getStoreCode();
        if (! $storedCode) {
            return false;
        }

        $given = md5(base64_encode($this->_space . '_' . $word));

        // 对比
        $result = (bool)($given === $storedCode);

        // 暂不清除；因为当验证码通过，其它信息未通过，页面或验证码区域未刷新时，验证码第二次提交过来就找不到匹配 -- by LuJun
        // $result && $this->_deleteStoreCode();

        return $result;
    }

    /**
     * 创建并输出图片
     */
    public function create()
    {
        // 生成随机字母
        $word = $this->_random($this->_length);

        // 记录字符串
        $this->_setStoreCode($word);
        $this->_image = imagecreate($this->_width, $this->_height);
        imagecolorallocate($this->_image, 220, 220, 220);

        // 在图片上添加扰乱元素
        $this->_disturbPixel();

        // 在图片上添加字符串
        $this->_drawCode($word);

        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Content-type: image/png');

        imagepng($this->_image);
        imagedestroy($this->_image);
    }

    /**
     * 创建扰乱元素
     *
     * @param    void
     * @return   void
     */
    private function _disturbPixel()
    {
        for ($i = 1; $i <= 100; $i++) {
            $disturbColor = imagecolorallocate ($this->_image, rand(0,255), rand(0,255), rand(0,255));
            imagesetpixel($this->_image, rand(2,128), rand(2,38), $disturbColor);
        }

        for ($i = 0; $i < 5; $i++) {
            imageline($this->_image,rand(0,20),rand(0,25),rand(90,100),rand(20,60),$disturbColor);
        }
    }

    /**
     * 在图片上添加字符串
     *
     * @param    string    $word
     * @return   void
     */
    private function _drawCode($word)
    {
        for ($i = 0; $i<strlen($word); $i++){
            $color = imagecolorallocate($this->_image, rand(0,255), rand(0,128), rand(0,255));
            $x = floor($this->_width / strlen($word)) * $i;
            $y = rand(0, $this->_height-15);
         // imageChar($this->_image, rand(3,6), $x, $y, $word[$i], $color);
            imagettftext($this->_image,14,0, $x, $y+15, $color, $this->_font, $word[$i]);
        }
    }

    /**
     * 随机字符串
     *
     * @param int $length
     * @return string
     */
    private function _random($length = 5)
    {
        $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

        for ($i = 0, $count = strlen($chars); $i < $count; $i++) {
            $arr[$i] = $chars[$i];
        }

        mt_srand((double) microtime() * 1000000);
        shuffle($arr);
        return substr(implode('', $arr), 5, $length);
    }

    /**
     * 将生成的验证码存到临时存储区中
     * 可以存 $_SESSION，这里用 $_COOKIE
     *
     * @param string $code
     */
    private function _setStoreCode($code)
    {
        $code = md5(base64_encode($this->_space . '_' . $code));
        setcookie($this->_prefix . $this->_space, $code, time() + 86400, '/');
        $_COOKIE[$this->_prefix . $this->_space] = $code;
    }

    /**
     * 从临时存储区获取生成的验证码
     *
     * @return string
     */
    private function _getStoreCode()
    {
        return isset($_COOKIE[$this->_prefix . $this->_space]) ? $_COOKIE[$this->_prefix . $this->_space] : '';
    }

    /**
     * 清空临时存储区
     */
    private function _deleteStoreCode()
    {
        setcookie($this->_prefix . $this->_space, '', time() - 86400, '/');
        unset($_COOKIE[$this->_prefix . $this->_space]);
    }
}

/**
    使用说明：
    [in HTML]
    <img src="Show.php?space=test" />

    [in Show.php]
    $config = array(
        'space'  => 'default',
        'width'  => 87,
        'height' => 33,
        'lenght' => 5,
    );
    $captchaObj = new Com_Captcha($config);
    $captchaObj->create();  // 会直接输出二进制流

    [in Sumbit.php]
    $captchaObj = new Com_Captcha($config);
    return $captchaObj->check($_GET['vcode']);
*/