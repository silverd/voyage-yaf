<?php

/**
 * 图像缩放处理(GD库)
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Image.php 12 2012-11-19 01:29:52Z jiangjian $
 */

class Com_Image
{
    /**
     * 生成缩略图
     *
     * @param string $orgImgPath  原图路径
     * @param int $maxWidth
     * @param int $maxHeight
     * @param bool $resize true:强制缩放到固定高宽,不足部分黑色填充；false:不足部分自动裁剪
     * @param int $quality 缩略图质量
     * @return void
     */
    public static function cut($orgImgPath, $maxWidth = 400, $maxHeight = 400, $resize = true, $quality = 100)
    {
        $imageInfo = @getimagesize($orgImgPath);
        if (! $imageInfo) {
            return false;
        }

        $imgType = $imageInfo['mime'];

        $allType = array(
            'image/jpeg' => array('create' => 'imagecreatefromjpeg', 'output' => 'imagejpeg'),
            'image/gif'  => array('create' => 'imagecreatefromgif', 'output' => 'imagegif'),
            'image/png'  => array('create' => 'imagecreatefrompng', 'output' => 'imagepng'),
            'image/wbmp' => array('create' => 'imagecreatefromwbmp', 'output' => 'image2wbmp')
        );

        $funcCreate = $allType[$imgType]['create'];
        if (empty($funcCreate) || ! function_exists($funcCreate)) {
            exit('Damaged image or invalid image type.');
        }

        $funcOutput = $allType[$imgType]['output'];

        $bigImage = @$funcCreate($orgImgPath);
        $bigWidth = imagesx($bigImage);
        $bigHeight = imagesy($bigImage);

        // 缩略图文件名
        $newImgPath = self::thumbName($orgImgPath, $maxWidth, $maxHeight);

        if ($bigWidth <= $maxWidth && $bigHeight <= $maxHeight) {
            $funcOutput($bigImage, $newImgPath, $quality);
            return true;
        }

        $ratiow = $maxWidth / $bigWidth;
        $ratioh = $maxHeight / $bigHeight;
        if ($resize == 1) {
            if ($bigWidth >= $maxWidth && $bigHeight >= $maxHeight) {
                if ($bigWidth > $bigHeight) {
                    $tempx = $maxWidth / $ratioh;
                    $tempy = $bigHeight;
                    $srcX = ($bigWidth - $tempx) / 2;
                    $srcY = 0;
                } else {
                    $tempy = $maxHeight / $ratiow;
                    $tempx = $bigWidth;
                    $srcY = ($bigHeight - $tempy) / 2;
                    $srcX = 0;
                }
            } else {
                if ($bigWidth > $bigHeight) {
                    $tempx = $maxWidth;
                    $tempy = $bigHeight;
                    $srcX = ($bigWidth - $tempx) / 2;
                    $srcY = 0;
                } else {
                    $tempy = $maxHeight;
                    $tempx = $bigWidth;
                    $srcY = ($bigHeight - $tempy) / 2;
                    $srcX = 0;
                }
            }
        } else {
            $srcX = 0;
            $srcY = 0;
            $tempx = $bigWidth;
            $tempy = $bigHeight;
        }

        $newWidth = ($ratiow > 1) ? $bigWidth : $maxWidth;
        $newHeight = ($ratioh > 1) ? $bigHeight : $maxHeight;

        if (function_exists('imagecopyresampled')) {
            $tmpImage = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($tmpImage, $bigImage, 0, 0, $srcX, $srcY, $newWidth, $newHeight, $tempx, $tempy);
        } else {
            $tmpImage = imagecreate($newWidth, $newHeight);
            imagecopyresized($tmpImage, $bigImage, 0, 0, $srcX, $srcY, $newWidth, $newHeight, $tempx, $tempy);
        }

        $funcOutput($tmpImage, $newImgPath, $quality);
        imagedestroy($bigImage);
        imagedestroy($tmpImage);

        return true;
    }

    /**
     * 返回缩略图文件名
     *
     * @param string $orgName 原名
     * @param int $w 宽
     * @param int $h 高
     * @param bool $ext 是否保留扩展名
     * @return string
     */
    public static function thumbName($orgName, $w, $h, $withExt = true)
    {
        $newName = $orgName . '_' . $w . 'x' . $h;
        if ($withExt) {
            return $newName . self::_getExt($orgName, true);
        }

        return $newName;
    }

    /**
     * 获取扩展名
     *
     * @param string $name
     * @param bool $withDot
     * @return string
     */
    private static function _getExt($name, $withDot = false)
    {
        $pathinfo = pathinfo($name);
        if (isset($pathinfo['extension'])) {
            return strtolower(($withDot ? '.' : '' ) . $pathinfo['extension']);
        }

        return '';
    }
}