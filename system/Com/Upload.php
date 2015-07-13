<?php

/**
 * 文件上传类
 *
 * @author ColaPHP Framework
 * @modifier JianJian <silverd@sohu.com>
 * $Id: Upload.php 820 2012-12-17 02:33:40Z jiangjian $
 */

class Com_Upload
{
    /**
     * Upload error message
     *
     * @var array
     */
    protected $_message = array(
        1 => 'upload_file_exceeds_limit',
        2 => 'upload_file_exceeds_form_limit',
        3 => 'upload_file_partial',
        4 => 'upload_no_file_selected',
        6 => 'upload_no_temp_directory',
        7 => 'upload_unable_to_write_file',
        8 => 'upload_stopped_by_extension'
    );

    /**
     * Upload config
     *
     * @var array
     */
    protected $_config = array(
        'savePath' => '/tmp',
        'savePathHash' => false,
        'maxSize' => 0,
        'maxWidth' => 0,
        'maxHeight' => 0,
        'allowedExts' => '*',
        'allowedTypes' => '*',
        'override' => false,
    );

    /**
     * The num of successfully uploader files
     *
     * @var int
     */
    protected $_num = 0;

    /**
     * Formated $_FILES
     *
     * @var array
     */
    protected $_files = array();

    /**
     * Error
     *
     * @var array
     */
    protected $_error;

    /**
     * 成功上传了哪些文件名
     *
     * @var array
     */
    protected $_saveNames = array();

    /**
     * Constructor
     *
     * Construct && formate $_FILES
     * @param array $config
     */
    public function __construct($config = array())
    {
        $this->_config = array_merge($this->_config, $config);

        $this->_config['savePath'] = rtrim($this->_config['savePath'], DIRECTORY_SEPARATOR);

        $this->_format();
    }

    /**
     * Config
     *
     * Set or get configration
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    public function config($name = null, $value = null)
    {
        if (null == $name) {
            return $this->_config;
        }

        if (null == $value) {
            return isset($this->_config[$name]) ? $this->_config[$name] : null;
        }

        $this->_config[$name] = $value;

        return $this;
    }

    /**
     * Format $_FILES
     *
     */
    protected function _format()
    {
        foreach ($_FILES as $field => $file) {

            if (empty($file['name'])) {
                continue;
            }

            if (is_array($file['name'])) {
                $cnt = count($file['name']);

                for ($i = 0; $i < $cnt; $i++) {
                    if (empty($file['name'][$i])) {
                        continue;
                    }
                    $this->_files[] = array(
                        'field'    => $field,
                        'name'     => $file['name'][$i],
                        'type'     => $file['type'][$i],
                        'tmp_name' => $file['tmp_name'][$i],
                        'error'    => $file['error'][$i],
                        'size'     => $file['size'][$i],
                        'ext'      => $this->getExt($file['name'][$i], true)
                    );
                }

            } else {
                $this->_files[$field] = $file + array('field' => $field, 'ext' => $this->getExt($file['name'], true));
            }
        }
    }

    /**
     * Save uploaded files
     *
     * @param string $fileInputName
     * @param string $name
     * @return boolean
     */
    public function save($fileInputName, $name = null)
    {
        $file = $this->_files[$fileInputName];
        return $this->_move($file, $name);
    }


    /**
     * Batch save uploaded files
     *
     * @return boolean
     */
    public function batchSave()
    {
        $return = true;

        foreach ($this->_files as $file) {
            $return = $return && $this->_move($file);
        }

        return $return;
    }

    /**
     * Move file
     *
     * @param array $file
     * @param string $name
     * @return boolean
     */
    protected function _move($file, $name = null)
    {
        if (! $this->check($file)) {
            return false;
        }

        // 目标保存文件名和路径
        if (null === $name) {
            $name = $this->setSaveName();
        }

        // 原文件名
        $fileName = $name . '.' . $this->getExt($file['name']);

        // 带哈希子目录的文件名（如果开启）
        if ($this->_config['savePathHash']) {
            $fileName = $this->_getHashSavePath($fileName) . DIRECTORY_SEPARATOR . $fileName;
        }

        // 完整的保存文件路径
        $fileFullName = $this->_config['savePath'] . DIRECTORY_SEPARATOR . $fileName;

        if (is_file($fileFullName) && ! $this->_config['override']) {
            $this->_error[] = 'file_already_exits:' . $fileFullName;
            return false;
        }

        $dir = dirname($fileFullName);
        is_dir($dir) || mkdir($dir, 0755, true);

        if (! is_writable($dir)) {
            $this->_error[] = 'directory: ' . $dir . ' may not be writeable.';
            return false;
        }

        if (! move_uploaded_file($file['tmp_name'], $fileFullName)) {
            $this->_error[] = 'move_uploaded_file_failed: [' . $dir . '] may not be writeable.';
            return false;
        }

        // 成功上传累加数
        $this->_num++;

        // 成功上传的文件名
        $this->_saveNames[] = str_replace(DIRECTORY_SEPARATOR, '/', $fileName);

        return true;
    }

    /**
     * Check file
     *
     * @param array $file
     * @return string
     */
    public function check($file)
    {
        if (UPLOAD_ERR_OK != $file['error']) {
            $this->_error[] = $this->_message[$file['error']] . ':' . $file['name'];
            return false;
        }

        if (! is_uploaded_file($file['tmp_name'])) {
            $this->_error[] = 'file_upload_failed:' . $file['name'];
            return false;
        }

        if (! $this->checkType($file, $this->_config['allowedTypes'])) {
            $this->_error[] = 'file_type_not_allowed:' . $file['name'];
            return false;
        }

        if (! $this->checkExt($file, $this->_config['allowedExts'])) {
            $this->_error[] = 'file_ext_not_allowed:' . $file['name'];
            return false;
        }

        if (! $this->checkFileSize($file, $this->_config['maxSize'])) {
            $this->_error[] = 'file_size_not_allowed:' . $file['name'];
            return false;
        }

        if ($this->isImage($file) && ! $this->checkImageSize($file, array($this->_config['maxWidth'], $this->_config['maxHeight']))) {
            $this->_error[] = 'image_size_not_allowed:' . $file['name'];
            return false;
        }

        return true;
    }

    /**
     * Get image size
     *
     * @param string $file
     * @return array like array(x, y),x is width, y is height
     */
    public function getImageSize($name)
    {
        if (function_exists('getimagesize')) {
            $size = getimagesize($name);
            return array($size[0], $size[1]);
        }

        return false;
    }

    /**
     * Get file extension
     *
     * @param string $fileName
     * @param bool $withDot
     * @return string
     */
    public function getExt($name, $withDot = false)
    {
        $pathinfo = pathinfo($name);
        if (isset($pathinfo['extension'])) {
            return strtolower(($withDot ? '.' : '' ) . $pathinfo['extension']);
        }

        return '';
    }

    /**
     * Check if is image
     *
     * @param string $type
     * @param string $imageTypes
     * @return boolean
     */
    public function isImage($file)
    {
        return 'image' == substr($file['type'], 0, 5);
    }

    /**
     * Check file type
     *
     * @param string $type
     * @param string $allowedTypes
     * @return boolean
     */
    public function checkType($file, $allowedTypes)
    {
        return ('*' == $allowedTypes || false !== stripos($allowedTypes, $file['type'])) ? true :false;
    }

    /**
     * Check file ext
     *
     * @param string $ext
     * @param string $allowedExts
     * @return boolean
     */
    public function checkExt($file, $allowedExts)
    {
        return ('*' == $allowedExts || false !== stripos($allowedExts, $this->getExt($file['name']))) ? true :false;
    }

    /**
     * Check file size
     *
     * @param int $size
     * @param int $maxSize
     * @return boolean
     */
    public function checkFileSize($file, $maxSize)
    {
        return 0 === $maxSize || $file['size'] <= $maxSize;
    }

    /**
     * Check image size
     *
     * @param array $size
     * @param array $maxSize
     * @return unknown
     */
    public function checkImageSize($file, $maxSize)
    {
        $size = $this->getImageSize($file['tmp_name']);
        return (0 === $maxSize[0] || $size[0] <= $maxSize[0]) && (0 === $maxSize[1] || $size[1] <= $maxSize[1]);
    }

    /**
     * Get formated files
     *
     * @return array
     */
    public function files()
    {
        return $this->_files;
    }

    /**
     * Get the num of sucessfully uploaded files
     *
     * @return int
     */
    public function num()
    {
        return $this->_num;
    }

    /**
     * Get upload error
     *
     * @return array
     */
    public function error()
    {
        return $this->_error;
    }

    /**
     * 生成随机文件名
     *
     * @return string
     */
    public function setSaveName()
    {
        return md5(uniqid() . mt_rand(1, 10000));
    }

    /**
     * 返回保存的文件名
     *
     * @param bool $get1st 是否返回上传的第一个文件名
     * @return string
     */
    public function getSaveNames($get1stFile = false)
    {
        if ($get1stFile) {
            return current($this->_saveNames);
        }

        return $this->_saveNames;
    }

    /**
     * 获取散列后的保存目录
     *
     * @param string $fileName MD5后的文件名
     * @return string
     */
    protected function _getHashSavePath($fileName)
    {
        $hash = md5($fileName);
        return (substr($hash, 0, 2) . DIRECTORY_SEPARATOR . substr($hash, 2, 2));
    }
}

/*
    Example:

    // 无需上传任何文件
    if (!$_FILES['pic_name']['name']) {
        return false;
    }

    $config = [
        'allowedExts' => 'jpg,jpeg,gif',
        'savePath'    => WEB_PATH . 'upload', // 上传目录
    ];

    $uploadObj = new Com_Upload($config);
    if (!$uploadObj->save($fieldName)) {
        exit($uploadObj->error());
    }

    // 图片文件名
    $picName = $uploadObj->getSaveNames(true);
    echo $picName;
 */