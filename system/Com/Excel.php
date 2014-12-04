<?php

/**
 * 导入、导出 Excel-CSV 格式
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: Excel.php 9273 2014-02-26 13:53:46Z jiangjian $
 */

class Com_Excel
{
    /**
     * 构建 CSV 字符串
     *
     * @param array $title 标题名
     * @param array $datas 内容
     * @return string
     */
    public static function buildCsv(array $title, array $datas)
    {
        $title = '"' . implode('","', $title) . '"' . "\n";

        $content = '';
        foreach ($datas as $val) {
            $content .= '"' . implode('","', $val) . '"' . "\n";
        }

        return $title . $content;
    }

    /**
     * 导出数据为 CSV
     *
     * @param string $fileName 文件名
     * @param array $title 标题名
     * @param array $datas 内容
     * @return void
     */
    public static function exportCsv($fileName, array $title, array $datas)
    {
        // 构建 CSV 字符串
        $content = self::buildCsv($title, $datas);

        $fileName = iconv('UTF-8', 'GBK', $fileName);

        header('Content-Disposition: attachment; filename=' . $fileName . '.csv');
        header('Content-Type:application/octet-stream');
        echo iconv('UTF-8', 'GBK', $content);
        exit;
    }

    /**
     * 上传并导入 csv 格式
     *
     * @param string $fileInputName 例如 $_FILES['upload_file'] 中的 upload_file
     *
     * @return array|int
     */
    public static function importCsv($fileInputName)
    {
        $data = $_FILES[$fileInputName];
        $fileInfo = pathinfo($data['name']);

        if ($fileInfo['extension'] != 'csv') {
            throw new Core_Exception_Fatal('上传文件格式不正确，必须为CSV格式');
        }

        // 文件上传失败
        if (! $data['tmp_name']) {
            throw new Core_Exception_Fatal('文件上传失败，tmp_name 读取失败');
        }

        $fileName = $data['tmp_name'];
        @chmod($fileName, 0777);

        $excelData = array();
        $handle = fopen($fileName, 'r');
        if (! $handle) {
            throw new Core_Exception_Fatal('临时文件打开失败');
        }

        while (! feof($handle)) {
            $data = mb_convert_encoding(trim(strip_tags(fgets($handle))), 'utf-8', 'gbk');
            if ($data) {
                $excelData[] = explode(',', $data);
            }
        }

        unset($excelData[0]);

        fclose($handle);
        @unlink($fileName);

        return $excelData;
    }
}