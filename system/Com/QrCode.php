<?php

/**
 * 二维码生成器
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Com_QrCode
{
    public static function make($string)
    {
        Yaf_Loader::import(SYS_PATH . 'Third/PHPQrCode/phpqrcode.php');

        // 直接输出二进制流
        QRcode::png($string, false, 'M', 5);
    }

    /*
     * @param   string   string     二维码内容(不超300字符)
     * @param   integer  $width 　  二维码宽度(高度和宽度一样，默认150px)
     * @param   string   $bgc       背景色(十六进制表示，例如：FFFFFF默认白色)
     * @param   string   $fgc       前景颜色(十六进制表示，默认黑色)
     * @param   string   $logo      logo url地址(logo只能是:jpg,png,gif三种且大小不超过200KB)
     * @param   float    $logosize  logo占二维码图片的大小(不能大于1,默认LOGO大小为0.4即40%,如果太大则影响识别)
     * @param   integer  $el        纠错等级(0:L,1:M,2:Q,3:H,默认为3)
     */
    public static function make2($string, $width = 150, $bgc = 'FFFFFF', $fgc = '000000', $logo = null, $logosize = null, $el = 3)
    {
        $params = [
            'string'   => $string,
            'width'    => $width,
            'bgc'      => $bgc,
            'fgc'      => $fgc,
            'logo'     => $logo,
            'logosize' => $logosize,
            'el'       => $el,
            'format'   => 'json',
        ];

        $url = 'http://api.uihoo.com/qrcode/qrcode.http.php?' . http_build_query($params);

        $content = json_decode(file_get_contents($url));

        return isset($content['base64']) ? $content['base64'] : false;
    }
}