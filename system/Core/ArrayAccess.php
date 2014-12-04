<?php

/**
 * 对象属性访问方法抽象
 *
 * @author JiangJian <silverd@sohu.com>
 * $Id: ArrayAccess.php 3086 2013-05-24 14:39:22Z jiangjian $
 */

abstract class Core_ArrayAccess implements ArrayAccess
{
    /**
     * 当前实体属性数组
     *
     * @var array
     */
    protected $_prop = array();

    public function setArrayAccess(&$prop)
    {
        $this->_prop = &$prop;
    }

    public function offsetSet($name, $value)
    {
        return $this->set($name, $value);
    }

    public function offsetGet($name)
    {
        if (! isset($this->_prop[$name])) {
            $this->_prop[$name] = $this->$name;
        }

        return $this->_prop[$name];
    }

    public function offsetExists($name)
    {
        return $this->has($name);
    }

    public function offsetUnset($name)
    {
        return $this->del($name);
    }

    public function __set($name, $value)
    {
        return $this->set($name, $value);
    }

    public function __get($name)
    {
        return $this->{$name} = $this->get($name);
    }

    public function __isset($name)
    {
        return $this->has($name);
    }

    public function __unset($name)
    {
        return $this->del($name);
    }

    public function set($name, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $key => $val) {
                $this->{$key}      = $val;
                $this->_prop[$key] = $val;
            }
        } else {
            $this->{$name}      = $value;
            $this->_prop[$name] = $value;
        }
    }

    public function get($name)
    {
        return isset($this->_prop[$name]) ? $this->_prop[$name] : null;
    }

    public function has($name)
    {
        return isset($this->_prop[$name]);
    }

    public function del($name)
    {
        $this->set($name, null);
        unset($this->_prop[$name]);
    }

    public function __toString()
    {
        return print_r($this->_prop, true);
    }

    public function __toArray()
    {
        return $this->_prop ?: array();
    }

    public function isEmpty()
    {
        return empty($this->_prop);
    }

    public function mergeValue(array $data)
    {
        return $this->set($data);
    }

    public function assertValueNotArray($setArr)
    {
        foreach ($setArr as $key => $value) {
            if (is_array($value) || is_object($value)) {
                throw new Core_Exception_Fatal('已断言 setArr 中的 value 不能为数组' . print_r($setArr, true));
            }
        }
    }
}