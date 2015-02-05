<?php

/**
 * Api 服务端控制器 抽象父类
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Api.php 7057 2013-11-25 08:41:34Z jiangjian $
 */

abstract class Core_Controller_Api extends Core_Controller_Abstract
{
    /**
     * 不加载视图（请勿修改）
     *
     * @var bool
     */
    public $yafAutoRender = false;

    /**
     * 对称验证私钥（可选）
     * 如果设置了私钥，则表示本控制器内所有方法都需要验证签名
     *
     * @var string
     */
    protected $_secretKey;

    public function init()
    {
        parent::init();

        // 如果设置了私钥，则需要验证签名
        if ($this->_secretKey) {
            $this->_verifySign();
        }
    }

    /**
     * 统一响应输出
     *
     * @param string $message 响应文字
     * @param string $code 响应代码
     * @param mixed  $data 响应的数据
     * @return json
     */
    public function output($message, $code = 0, $data = null)
    {
        $resp = array(
            'status_no'  => $code,
            'status_msg' => $message,
        );

        if ($data !== null) {
            $resp['data'] = $data;
        }

        $this->json($resp);
    }

    // 验证请求签名（防止篡改请求参数）
    protected function _verifySign()
    {
        $postData = $this->getPostx();

        if (! Helper_Api::verifySign($postData, $this->_secretKey)) {
            $this->output('签名验证失败', -9999);
        }
    }
}