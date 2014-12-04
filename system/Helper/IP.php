<?php

/**
 * IP读取->城市信息
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: IP.php 12 2012-11-19 01:29:52Z jiangjian $
 */

class Helper_IP
{
    private static $_fp;
    private static $_offset = array();
    private static $_index;

    public static function convertip($ip, $forceFull = false)
    {
        $return = '';

        if (preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/", $ip)) {

            $iparray = explode('.', $ip);

            if ($iparray[0] == 10 || $iparray[0] == 127 || ($iparray[0] == 192 && $iparray[1] == 168) || ($iparray[0] == 172 && ($iparray[1] >= 16 && $iparray[1] <= 31))) {
                $return = 'LAN';
            } elseif ($iparray[0] > 255 || $iparray[1] > 255 || $iparray[2] > 255 || $iparray[3] > 255) {
                $return = 'Invalid IP Address';
            } else {
                $tinyipfile = dirname(__FILE__) . '/IPData/tinyipdata.dat';
                $fullipfile = dirname(__FILE__) . '/IPData/qqwry.dat';
                if (($forceFull || ! file_exists($tinyipfile)) && file_exists($fullipfile)) {
                    $return = self::convertipFull($ip, $fullipfile);
                } elseif (file_exists($tinyipfile)) {
                    $return = self::convertipTiny($ip, $tinyipfile);
                }
            }
        }

        return $return;
    }

    public static function convertipTiny($ip, $ipdatafile)
    {
        $ipdot = explode('.', $ip);
        $ip    = pack('N', ip2long($ip));

        $ipdot[0] = (int)$ipdot[0];
        $ipdot[1] = (int)$ipdot[1];

        if (self::$_fp === null && self::$_fp = @fopen($ipdatafile, 'rb')) {
            self::$_offset = @unpack('Nlen', @fread(self::$_fp, 4));
            self::$_index  = @fread(self::$_fp, self::$_offset['len'] - 4);
        } elseif (self::$_fp == FALSE) {
            return  'Invalid IP data file';
        }

        $length = self::$_offset['len'] - 1028;
        $start  = @unpack('Vlen', self::$_index[$ipdot[0] * 4] . self::$_index[$ipdot[0] * 4 + 1] . self::$_index[$ipdot[0] * 4 + 2] . self::$_index[$ipdot[0] * 4 + 3]);

        for ($start = $start['len'] * 8 + 1024; $start < $length; $start += 8) {

            if (self::$_index{$start} . self::$_index{$start + 1} . self::$_index{$start + 2} . self::$_index{$start + 3} >= $ip) {
                $index_offset = @unpack('Vlen', self::$_index{$start + 4} . self::$_index{$start + 5} . self::$_index{$start + 6} . "\x0");
                $index_length = @unpack('Clen', self::$_index{$start + 7});
                break;
            }
        }

        @fseek(self::$_fp, self::$_offset['len'] + $index_offset['len'] - 1024);
        if ($index_length['len']) {
            return @fread(self::$_fp, $index_length['len']);
        } else {
            return 'Unknown';
        }
    }

    public static function convertipFull($ip, $ipdatafile)
    {
        if (! $fd = @fopen($ipdatafile, 'rb')) {
            return 'Invalid IP data file';
        }

        $ip = explode('.', $ip);
        $ipNum = $ip[0] * 16777216 + $ip[1] * 65536 + $ip[2] * 256 + $ip[3];

        if (!($DataBegin = fread($fd, 4)) || !($DataEnd = fread($fd, 4)) ) return;
        @$ipbegin = implode('', unpack('L', $DataBegin));
        if ($ipbegin < 0) $ipbegin += pow(2, 32);
        @$ipend = implode('', unpack('L', $DataEnd));
        if ($ipend < 0) $ipend += pow(2, 32);
        $ipAllNum = ($ipend - $ipbegin) / 7 + 1;

        $BeginNum = $ip2num = $ip1num = 0;
        $ipAddr1 = $ipAddr2 = '';
        $EndNum = $ipAllNum;

        while ($ip1num > $ipNum || $ip2num < $ipNum) {
            $Middle= intval(($EndNum + $BeginNum) / 2);

            fseek($fd, $ipbegin + 7 * $Middle);
            $ipData1 = fread($fd, 4);
            if (strlen($ipData1) < 4) {
                fclose($fd);
                return 'System Error';
            }
            $ip1num = implode('', unpack('L', $ipData1));
            if ($ip1num < 0) $ip1num += pow(2, 32);

            if ($ip1num > $ipNum) {
                $EndNum = $Middle;
                continue;
            }

            $DataSeek = fread($fd, 3);
            if (strlen($DataSeek) < 3) {
                fclose($fd);
                return 'System Error';
            }
            $DataSeek = implode('', unpack('L', $DataSeek.chr(0)));
            fseek($fd, $DataSeek);
            $ipData2 = fread($fd, 4);
            if (strlen($ipData2) < 4) {
                fclose($fd);
                return 'System Error';
            }
            $ip2num = implode('', unpack('L', $ipData2));
            if ($ip2num < 0) $ip2num += pow(2, 32);

            if ($ip2num < $ipNum) {
                if ($Middle == $BeginNum) {
                    fclose($fd);
                    return 'Unknown';
                }
                $BeginNum = $Middle;
            }
        }

        $ipFlag = fread($fd, 1);
        if ($ipFlag == chr(1)) {
            $ipSeek = fread($fd, 3);
            if (strlen($ipSeek) < 3) {
                fclose($fd);
                return 'System Error';
            }
            $ipSeek = implode('', unpack('L', $ipSeek.chr(0)));
            fseek($fd, $ipSeek);
            $ipFlag = fread($fd, 1);
        }

        if ($ipFlag == chr(2)) {
            $AddrSeek = fread($fd, 3);
            if (strlen($AddrSeek) < 3) {
                fclose($fd);
                return 'System Error';
            }
            $ipFlag = fread($fd, 1);
            if ($ipFlag == chr(2)) {
                $AddrSeek2 = fread($fd, 3);
                if (strlen($AddrSeek2) < 3) {
                    fclose($fd);
                    return 'System Error';
                }
                $AddrSeek2 = implode('', unpack('L', $AddrSeek2.chr(0)));
                fseek($fd, $AddrSeek2);
            } else {
                fseek($fd, -1, SEEK_CUR);
            }

            while (($char = fread($fd, 1)) != chr(0))
            $ipAddr2 .= $char;

            $AddrSeek = implode('', unpack('L', $AddrSeek.chr(0)));
            fseek($fd, $AddrSeek);

            while (($char = fread($fd, 1)) != chr(0))
            $ipAddr1 .= $char;
        } else {
            fseek($fd, -1, SEEK_CUR);
            while (($char = fread($fd, 1)) != chr(0))
            $ipAddr1 .= $char;

            $ipFlag = fread($fd, 1);
            if ($ipFlag == chr(2)) {
                $AddrSeek2 = fread($fd, 3);
                if (strlen($AddrSeek2) < 3) {
                    fclose($fd);
                    return 'System Error';
                }
                $AddrSeek2 = implode('', unpack('L', $AddrSeek2.chr(0)));
                fseek($fd, $AddrSeek2);
            } else {
                fseek($fd, -1, SEEK_CUR);
            }
            while (($char = fread($fd, 1)) != chr(0))
            $ipAddr2 .= $char;
        }
        fclose($fd);

        if (preg_match('/http/i', $ipAddr2)) {
            $ipAddr2 = '';
        }
        $ipaddr = "$ipAddr1 $ipAddr2";
        $ipaddr = preg_replace('/CZ88\.NET/is', '', $ipaddr);
        $ipaddr = preg_replace('/^\s*/is', '', $ipaddr);
        $ipaddr = preg_replace('/\s*$/is', '', $ipaddr);
        if (preg_match('/http/i', $ipaddr) || $ipaddr == '') {
            $ipaddr = 'Unknown';
        }

        $ipaddr = iconv('GBK', 'UTF-8', $ipaddr);

        return $ipaddr;
    }

}

//example:  $ip = Helper_IP::convertip('202.96.201.77');