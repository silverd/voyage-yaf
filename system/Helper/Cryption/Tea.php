<?php

// Tea16轮迭代类
class Helper_Cryption_Tea
{
    private $plain = array();
    private $prePlain = array();
    private $out = array();
    private $crypt = 0;
    private $preCrypt = 0;
    private $pos = 0;
    private $padding = 0;
    private $key = array();
    private $header = true;
    private $contextStart = 0;

    /**
     * 把字节数组从offset开始的len个字节转换成一个unsigned int
     * @return
     */
    private static function getUnsignedInt($in, $offset, $len)
    {
        $ret = 0;
        $ret2 = 0;
        $end = 0;
        if ($len > 8) {
            $end = $offset + 8;
        } else {
            $end = $offset + $len;
        }
        for ($j = 0, $i = $offset; $i < $end; $i++, $j++) {
            if ($j < 4) {
                $ret <<= 8;
                $ret |= $in[$i] & 0xff;
            } else {
                $ret2 <<= 8;
                $ret2 |= $in[$i] & 0xff;
            }
        }
        return $ret | $ret2;
    }

    /**
     * 解密
     * @param in 密文
     * @param offset 密文开始的位置
     * @param len 密文长度
     * @param k 密钥
     * @return 明文
     */
    public function decrypt($in, $offset, $len = 0, $k = '')
    {
        if (func_num_args() == 2) {
            $k = $offset;
            $offset = 0;
            $len = strlen($in);
        }
        if (empty($k)) {
            return null;
        }

        $in = $this->stringToByteArray($in);
        $k = $this->stringToByteArray($k);

        $this->crypt = $this->preCrypt = 0;
        $this->key = $k;
        $count = 0;
        $m = $this->newByteArray($offset + 8);

        if (($len % 8 != 0) || ($len < 16))
            return null;
        $this->prePlain = $this->decipher($in, $offset);
        $this->pos = $this->prePlain[0] & 0x7;
        $count = $len - $this->pos - 10;
        if ($count < 0)
            return null;

        for ($i = $offset; $i < count($m); $i++)
            $m[$i] = 0;
        $this->out = $this->newByteArray($count);
        $this->preCrypt = 0;
        $this->crypt = 8;
        $this->contextStart = 8;
        $this->pos++;

        $this->padding = 1;
        while ($this->padding <= 2) {
            if ($this->pos < 8) {
                $this->pos++;
                $this->padding++;
            }
            if ($this->pos == 8) {
                $m = $in;
                if (!$this->decrypt8Bytes($in, $offset, $len))
                    return null;
            }
        }

        $i = 0;
        while ($count != 0) {
            if ($this->pos < 8) {
                $this->out[$i] = $m[$offset + $this->preCrypt + $this->pos] ^ $this->prePlain[$this->pos];
                $i++;
                $count--;
                $this->pos++;
            }
            if ($this->pos == 8) {
                $m = $in;
                $this->preCrypt = $this->crypt - 8;
                if (!$this->decrypt8Bytes($in, $offset, $len))
                    return null;
            }
        }

        for ($this->padding = 1; $this->padding < 8; $this->padding++) {
            if ($this->pos < 8) {
                if (($m[$offset + $this->preCrypt + $this->pos] ^ $this->prePlain[$this->pos]) != 0)
                    return null;
                $this->pos++;
            }
            if ($this->pos == 8) {
                $m = $in;
                $this->preCrypt = $this->crypt;
                if (!$this->decrypt8Bytes($in, $offset, $len))
                    return null;
            }
        }

        $result = '';
        foreach ($this->out as $c) {
            $result .= chr($c);
        }
        return $result;
    }

    /**
     * 加密
     * @param in 明文字节数组
     * @param offset 开始加密的偏移
     * @param len 加密长度
     * @param k 密钥
     * @return 密文字节数组
     */
    public function encrypt($in, $offset, $len = 0, $k = '')
    {
        if (func_num_args() == 2) {
            $k = $offset;
            $offset = 0;
            $len = strlen($in);
        }
        if (empty($k))
            return $in;

        $in = $this->stringToByteArray($in);
        $k = $this->stringToByteArray($k);

        $this->plain = $this->newByteArray(8); //new byte[8];
        $this->prePlain = $this->newByteArray(8); //new byte[8];
        $this->pos = 1;
        $this->padding = 0;
        $this->crypt = $this->preCrypt = 0;
        $this->key = $k;
        $this->header = true;

        $this->pos = ($len + 0x0A) % 8;
        if ($this->pos != 0)
            $this->pos = 8 - $this->pos;
        $this->out = $this->newByteArray($len + $this->pos + 10);
        $this->plain[0] = ((rand() & 0xF8) | $this->pos) & 0xFF;

        for ($i = 1; $i <= $this->pos; $i++)
            $this->plain[$i] = rand() & 0xFF;
        $this->pos++;
        for ($i = 0; $i < 8; $i++)
            $this->prePlain[$i] = 0x0;

        $this->padding = 1;
        while ($this->padding <= 2) {
            if ($this->pos < 8) {
                $this->plain[$this->pos++] = rand() & 0xFF;
                $this->padding++;
            }
            if ($this->pos == 8)
                $this->encrypt8Bytes();
        }

        $i = $offset;
        while ($len > 0) {
            if ($this->pos < 8) {
                $this->plain[$this->pos++] = $in[$i++];
                $len--;
            }
            if ($this->pos == 8)
                $this->encrypt8Bytes();
        }

        $this->padding = 1;
        while ($this->padding <= 7) {
            if ($this->pos < 8) {
                $this->plain[$this->pos++] = 0x0;
                $this->padding++;
            }
            if ($this->pos == 8)
                $this->encrypt8Bytes();
        }

        $result = '';
        foreach ($this->out as $c) {
            $result .= chr($c);
        }
        return $result;
    }

    /**
     * 加密一个8字节块
     *
     * @param in
     * 明文字节数组
     * @return
     * 密文字节数组
     */
    private function encipher($in)
    {
        $loop = 0x10;
        $y = self::getUnsignedInt($in, 0, 4);
        $z = self::getUnsignedInt($in, 4, 4);
        $a = self::getUnsignedInt($this->key, 0, 4);
        $b = self::getUnsignedInt($this->key, 4, 4);
        $c = self::getUnsignedInt($this->key, 8, 4);
        $d = self::getUnsignedInt($this->key, 12, 4);
        $sum = 0;
        $delta = 0x9E3779B9;

        while ($loop-- > 0) {
            $sum = $this->_add($sum, $delta);
            $y = $this->_add($y, $this->_add(($z << 4), $a) ^ $this->_add($z, $sum) ^ $this->_add($this->_rshift($z, 5), $b));
            $z = $this->_add($z, $this->_add(($y << 4), $c) ^ $this->_add($y, $sum) ^ $this->_add($this->_rshift($y, 5), $d));
        }

        return array_merge($this->writeInt($y), $this->writeInt($z));
    }

    /**
     * 解密从offset开始的8字节密文
     *
     * @param in
     * 密文字节数组
     * @param offset
     * 密文开始位置
     * @return
     * 明文
     */
//    private byte[] decipher(byte[] in, int offset) {
    private function decipher($in, $offset = 0)
    {
        $loop = 0x10;
        $y = self::getUnsignedInt($in, $offset, 4);
        $z = self::getUnsignedInt($in, $offset + 4, 4);
        $a = self::getUnsignedInt($this->key, 0, 4);
        $b = self::getUnsignedInt($this->key, 4, 4);
        $c = self::getUnsignedInt($this->key, 8, 4);
        $d = self::getUnsignedInt($this->key, 12, 4);
        $sum = 0xE3779B90;
        $delta = 0x9E3779B9;

        while ($loop-- > 0) {
            $z = $this->_add($z, - ($this->_add(($y << 4), $c) ^ $this->_add($y, $sum) ^ $this->_add($this->_rshift($y, 5), $d)));
            $y = $this->_add($y, - ($this->_add(($z << 4), $a) ^ $this->_add($z, $sum) ^ $this->_add($this->_rshift($z, 5), $b)));
            $sum = $this->_add($sum, - $delta);
        }

        return array_merge($this->writeInt($y), $this->writeInt($z));
    }

    private function _rshift($integer, $n)
    {
        // convert to 32 bits
        if (0xffffffff < $integer || - 0xffffffff > $integer) {
            $integer = fmod($integer, 0xffffffff + 1);
        }

        // convert to unsigned integer
        if (0x7fffffff < $integer) {
            $integer -= 0xffffffff + 1.0;
        } elseif (- 0x80000000 > $integer) {
            $integer += 0xffffffff + 1.0;
        }

        // do right shift
        if (0 > $integer) {
            $integer &= 0x7fffffff; // remove sign bit before shift
            $integer >>= $n; // right shift
            $integer |= 1 << (31 - $n); // set shifted sign bit
        } else {
            $integer >>= $n; // use normal right shift
        }

        return $integer;
    }

    public function _add($i1, $i2)
    {
        $result = 0.0;

        foreach (func_get_args() as $value) {
            if (0.0 > $value) {
                $value -= 1.0 + 0xffffffff;
            }
            $result += $value;
        }

        if (0xffffffff < $result || - 0xffffffff > $result) {
            $result = fmod($result, 0xffffffff + 1);
        }

        if (0x7fffffff < $result) {
            $result -= 0xffffffff + 1.0;
        } elseif (- 0x80000000 > $result) {
            $result += 0xffffffff + 1.0;
        }

        return $result;
    }

    /**
     * 写入一个整型到输出流，高字节优先
     *
     * @param t
     */
    private function writeInt($t)
    {
        $a = ($t >> 24) & 0xFF;
        $b = ($t >> 16) & 0xFF;
        $c = ($t >> 8) & 0xFF;
        $d = $t & 0xFF;
        return array($a, $b, $c, $d);
    }

    /**
     * 加密8字节
     */
    private function encrypt8Bytes()
    {
        for ($this->pos = 0; $this->pos < 8; $this->pos++) {
            if ($this->header)
                $this->plain[$this->pos] ^= $this->prePlain[$this->pos];
            else
                $this->plain[$this->pos] ^= $this->out[$this->preCrypt + $this->pos];
        }
        $crypted = $this->encipher($this->plain);
        array_splice($this->out, $this->crypt, 8, $crypted);

        for ($this->pos = 0; $this->pos < 8; $this->pos++) {
            $this->out[$this->crypt + $this->pos] ^= $this->prePlain[$this->pos];
        }
        array_splice($this->prePlain, 0, 8, $this->plain);

        $this->preCrypt = $this->crypt;
        $this->crypt += 8;
        $this->pos = 0;
        $this->header = false;
    }

    /**
     * 解密8个字节
     *
     * @param in
     * 密文字节数组
     * @param offset
     * 从何处开始解密
     * @param len
     * 密文的长度
     * @return
     *  true表示解密成功
     */
    private function decrypt8Bytes($in, $offset, $len)
    {
        for ($this->pos = 0; $this->pos < 8; $this->pos++) {
            if ($this->contextStart + $this->pos >= $len)
                return true;
            $this->prePlain[$this->pos] ^= $in[$offset + $this->crypt + $this->pos];
        }

        $this->prePlain = $this->decipher($this->prePlain);
        if ($this->prePlain == null)
            return false;

        $this->contextStart += 8;
        $this->crypt += 8;
        $this->pos = 0;
        return true;
    }

    private function newByteArray($len, $default = 0)
    {
        $result = array();
        for ($i = 0; $i < $len; $i++) {
            $result[$i] = $default;
        }
        return $result;
    }

    private function stringToByteArray($s)
    {
        $result = array();
        $len = strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $result[$i] = ord($s[$i]);
        }
        return $result;
    }
}