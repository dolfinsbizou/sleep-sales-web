<?php
/*
* This file is part of the Astaroth package.
 *
 * (c) 2016 Victorien POTTIAU ~ Emmanuel LEROUX
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Astaroth;

use Exception;

/**
 * Class DbAttributesException
 * @package Astaroth
 */
final class DbAttributesException extends Exception {}

/**
 * Class DbAttributes
 * @package Astaroth
 */
final class DbAttributes implements iDbClass {

    /**
     * @var array
     */
    private $tableName = [];

    /**
     * @var array
     */
    private $data = [[]];

    /**
     * @var array
     */
    private $dataNoSQL = [[]];

    /**
     * @var array
     */
    private $primaryKey = [];

    /**
     * @var array
     */
    private static $dictionary = ['bool', 'int', 'long', 'double', 'float', 'real', 'string', 'datetime'];

    /**
     * @var array
     */
    private static $dictionaryNoSQL = ['bool', 'int', 'long', 'double', 'float', 'real', 'string', 'datetime', 'array'];

    /**
     * @var array
     */
    private static $function = ['MAX', 'MIN', 'AVG', 'SUM', 'COUNT'];

    /**
     * PRIVATE METHODS
     */

    /**
     * @param $p_name
     * @throws DbAttributesException
     */
    private function not_isset($p_name) {
        if (!isset($this->$p_name))
            throw new DbAttributesException("attribute do not exist ($p_name)");
    }

    /**
     * PUBLIC METHODS
     */

    /**
     * @param $p_arr
     * @throws DbAttributesException
     */
    public function add($p_arr) {
        if (!is_array($p_arr))
            throw new DbAttributesException("p_arr value must be an array");
        try {
            $this->_add($p_arr[0], $p_arr[1], (isset($p_arr[2]) ? $p_arr[2] : null));
        } catch (DbAttributesException $e) {
            throw new DbAttributesException($e->getMessage());
        }
    }

    /**
     * @param $p_arr
     * @throws DbAttributesException
     */
    public function addNoSQL($p_arr) {
        if (!is_array($p_arr))
            throw new DbAttributesException("p_arr value must be an array");
        try {
            $this->_addNoSQL($p_arr[0], $p_arr[1], (isset($p_arr[2]) ? $p_arr[2] : null));
        } catch (DbAttributesException $e) {
            throw new DbAttributesException($e->getMessage());
        }
    }

    /**
     * @param $p_name
     * @param $p_type
     * @param null $p_default_value
     * @throws DbAttributesException
     */
    public function _add($p_name, $p_type, $p_default_value = null) {
        if (isset($this->$p_name) || array_key_exists($p_name, $this->dataNoSQL[0]))
            throw new DbAttributesException("attribute already exist ($p_name)");
        if (!in_array($p_type, self::$dictionary))
            throw new DbAttributesException("incorrect type ($p_type)");
        if ($p_default_value !== null) {
            try {
                $this->isGoodType($p_default_value, $p_type);
            } catch (DbAttributesException $e) {
                throw new DbAttributesException($e->getMessage());
            }
        }
        $this->data[0][$p_name] = ['type' => $p_type, 'value' => $p_default_value, 'default.value' => $p_default_value, 'developer' => false];
    }

    /**
     * @param $p_name
     * @param $p_type
     * @param null $p_default_value
     * @throws DbAttributesException
     */
    public function _addNoSQL($p_name, $p_type, $p_default_value = null) {
        if (isset($this->$p_name) || array_key_exists($p_name, $this->dataNoSQL[0]))
            throw new DbAttributesException("attribute already exist ($p_name)");
        if (!in_array($p_type, self::$dictionaryNoSQL))
            throw new DbAttributesException("incorrect type ($p_type)");
        if ($p_default_value !== null) {
            try {
                $this->isGoodType($p_default_value, $p_type);
            } catch (DbAttributesException $e) {
                throw new DbAttributesException($e->getMessage());
            }
        }
        $this->dataNoSQL[0][$p_name] = ['type' => $p_type, 'value' => $p_default_value, 'default.value' => $p_default_value, 'developer' => false];
    }

    public function setDbAttributes($p_dbAttributes) {
        if (!$p_dbAttributes instanceof DbAttributes)
            throw new DbAttributesException("p_dbAttributes must be an DbAttributes");
        $this->tableName = array_merge($this->tableName, $p_dbAttributes->tableName);
        $this->data = array_merge($this->data, $p_dbAttributes->data);
        $this->dataNoSQL = array_merge($this->dataNoSQL, $p_dbAttributes->dataNoSQL);
        foreach ($p_dbAttributes->primaryKey as $value)
            $this->primaryKey[] = '{' . $p_dbAttributes->getTableName(0, true) . '}.' . $value;
    }

    /**
     * @param $p_name
     * @return mixed
     * @throws DbAttributesException
     */
    public function getValue($p_name) {
        if (array_key_exists($this->getAttr($p_name), $this->dataNoSQL[$this->getKey($p_name)]))
            return $this->dataNoSQL[$this->getKey($p_name)][$this->getAttr($p_name)]['value'];
        try {
            $this->not_isset($this->getAttr($p_name));
        } catch (DbAttributesException $e) {
            throw new DbAttributesException($e->getMessage());
        }
        return $this->data[$this->getKey($p_name)][$this->getAttr($p_name)]['value'];
    }

    /**
     * @param $p_name
     * @return mixed
     * @throws DbAttributesException
     */
    public function getDefaultValue($p_name) {
        if (array_key_exists($p_name, $this->dataNoSQL[$this->getKey($p_name)]))
            return $this->dataNoSQL[$this->getKey($p_name)][$this->getAttr($p_name)]['default.value'];
        try {
            $this->not_isset($p_name);
        } catch (DbAttributesException $e) {
            throw new DbAttributesException($e->getMessage());
        }
        return $this->data[$this->getKey($p_name)][$this->getAttr($p_name)]['default.value'];
    }

    /**
     * @param $p_name
     * @return string
     * @throws DbAttributesException
     */
    public function getType($p_name) {
        if (array_key_exists($p_name, $this->dataNoSQL[$this->getKey($p_name)]))
            return $this->dataNoSQL[$this->getKey($p_name)][$this->getAttr($p_name)]['type'];
        if ($this->isFnct($p_name))
            return 'int';
        try {
            $this->not_isset($p_name);
        } catch (DbAttributesException $e) {
            throw new DbAttributesException($e->getMessage());
        }
        return $this->data[$this->getKey($p_name)][$this->getAttr($p_name)]['type'];
    }

    /**
     * @param int $p_key
     * @return array
     * @throws DbAttributesException
     */
    public function getProperties($p_key = 0) {
        if (!isset($this->data[$p_key]))
            throw new DbAttributesException("p_key not valid");
        return array_keys($this->data[$p_key]);
    }

    /**
     * @return array
     */
    public function getData() {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getPrimaryKey() {
        return $this->primaryKey;
    }

    /**
     * @param int $p_key
     * @param bool $p_class
     * @return mixed
     * @throws DbAttributesException
     */
    public function getTableName($p_key = 0, $p_class = false) {
        if (!isset($this->tableName[$p_key]['table']))
            throw new DbAttributesException("tableName with p_key value do not exist");
        if ($p_class)
            return $this->tableName[$p_key]['class'];
        return $this->tableName[$p_key]['table'];
    }

    public function getAllTableName() {
        return $this->tableName;
    }


    /**
     * @return mixed
     */
    public function getStrTableName() {
        if (!$this->isMultiTable())
            return $this->tableName[0]['table'];
        $v_rsl = "";
        $v_cc = count($this->tableName)-1;
        foreach ($this->tableName as $key => $item) {
            $v_rsl .= $item['table'] . ($key < $v_cc ? ', ' : '');
        }
        return $v_rsl;
    }

    /**
     * @param $p_class
     * @return mixed
     * @throws DbAttributesException
     */
    public function getTableNameByClass($p_class) {
        if (!class_exists($p_class))
            throw new DbAttributesException("Class do not exist");
        foreach ($this->tableName as $value)
            if ($value['class'] == $p_class)
                return $value['table'];
        throw new DbAttributesException("Class not in multi table");
    }

    /**
     * @param $p_class
     * @return array
     */
    public function getAllKeyWithoutClass($p_class) {
        $v_rsl = [];
        foreach ($this->tableName as $key => $item) {
            if ($p_class == $item['class'])
                continue;
            $v_rsl[] = $key;
        }
        return $v_rsl;
    }

    /**
     * @param $p_name
     * @param $p_value
     * @param bool $p_developer
     * @throws DbAttributesException
     */
    public function setValue($p_name, $p_value, $p_developer = false) {
        try {
            $this->isGoodType($p_value, $this->getType($p_name), $p_developer, array_key_exists($p_name, $this->dataNoSQL[$this->getKey($p_name)]));
        } catch (DbAttributesException $e) {
            throw new DbAttributesException($e->getMessage());
        }
        if (array_key_exists($p_name, $this->dataNoSQL[$this->getKey($p_name)])) {
            $this->dataNoSQL[$this->getKey($p_name)][$this->getAttr($p_name)]['value'] = $p_value;
            $this->dataNoSQL[$this->getKey($p_name)][$this->getAttr($p_name)]['developer'] = $p_developer;
        } else {
            $this->data[$this->getKey($p_name)][$this->getAttr($p_name)]['value'] = $p_value;
            $this->data[$this->getKey($p_name)][$this->getAttr($p_name)]['developer'] = $p_developer;
        }
    }

    /**
     * @param $p_name
     * @return mixed
     * @throws DbAttributesException
     */
    public function getDeveloper($p_name) {
        if (array_key_exists($p_name, $this->dataNoSQL[$this->getKey($p_name)]))
            return $this->dataNoSQL[$this->getKey($p_name)][$this->getAttr($p_name)]['developer'];
        try {
            $this->not_isset($p_name);
        } catch (DbAttributesException $e) {
            throw new DbAttributesException($e->getMessage());
        }
        return $this->data[$this->getKey($p_name)][$this->getAttr($p_name)]['developer'];
    }

    /**
     * @param $p_name
     * @param $p_default_value
     * @throws DbAttributesException
     */
    public function setDefaultValue($p_name, $p_default_value) {
        try {
            $this->isGoodType($p_default_value, $this->getType($p_name));
        } catch (DbAttributesException $e) {
            throw new DbAttributesException($e->getMessage());
        }
        if (array_key_exists($p_name, $this->dataNoSQL[$this->getKey($p_name)]))
            $this->dataNoSQL[$this->getKey($p_name)][$this->getAttr($p_name)]['default.value'] = $p_default_value;
        else
            $this->data[$this->getKey($p_name)][$this->getAttr($p_name)]['default.value'] = $p_default_value;
    }

    /**
     * @param $p_primaryKey
     * @throws DbAttributesException
     */
    public function setPrimaryKey($p_primaryKey) {
        if (count($p_primaryKey) == 1 && is_string($p_primaryKey[0]))
            $p_primaryKey = explode(',', $p_primaryKey[0]);
        if ($p_primaryKey !== null && !is_array($p_primaryKey))
            throw new DbAttributesException("p_primaryKey value must be an array");
        foreach ($p_primaryKey as $key => $primaryKey) {
            $primaryKey = trim($primaryKey);
            if (in_array($primaryKey, $this->primaryKey))
                throw new DbAttributesException("p_primaryKey value is already added");
            if (!isset($this->$primaryKey))
                throw new DbAttributesException("p_primaryKey value is not a good attribute");
            $p_primaryKey[$key] = $primaryKey;
        }
        $this->primaryKey = array_merge($this->primaryKey, $p_primaryKey);
    }

    /**
     * @param $p_str
     * @param $p_class
     * @throws DbAttributesException
     */
    public function setTableName($p_str, $p_class) {
        if (empty($p_str))
            throw new DbAttributesException("p_str is empty");
        if (!is_string($p_class))
            throw new DbAttributesException("p_class must be a string");
        if (is_array($p_str))
            if (count($p_str) == 1)
                $this->tableName[] = ['table' => implode($p_str), 'class' => $p_class];
    }

    /**
     * @param $p_data
     * @throws DbAttributesException
     */
    public function setNewData($p_data) {
        if (!is_array($p_data))
            throw new DbAttributesException("p_data value must be an array");
        foreach ($p_data as $data)
            $this->data[] = $data;
    }

    /**
     * @param $p_attr
     * @return bool
     * @throws DbAttributesException
     */
    public function constructAttr($p_attr) {
        if (!is_array($p_attr))
            throw new DbAttributesException("p_attr value must be an array");
        foreach ($p_attr as $key => $value) {
            $this->$key = $value;
        }
        return true;
    }

    /**
     * @param $p_value
     * @param $p_type
     * @param bool $p_developer
     * @param bool $p_noSQL
     * @return bool
     * @throws DbAttributesException
     */
    public function isGoodType($p_value, $p_type, $p_developer = false, $p_noSQL = false) {
        if ($p_noSQL && !in_array($p_type, self::$dictionaryNoSQL))
            throw new DbAttributesException("p_type value is not good type ($p_type)");
        if (!$p_noSQL && !in_array($p_type, self::$dictionary))
            throw new DbAttributesException("p_type value is not good type ($p_type)");
        if ($p_type === 'datetime')
            if (is_a($p_value, 'Astaroth\DbDateTime'))
                return true;
            else
                throw new DbAttributesException("p_type value is not good type ($p_type)");
        if ($p_developer && $p_type == 'int' && is_string($p_value))
            return true;
        if ($p_type != 'int' && $p_value === null)
            return true;
        $v_met = "is_" . $p_type;
        if (!$v_met($p_value))
            throw new DbAttributesException("p_value value not good type ($p_type)");
        return true;
    }

    /**
     * @param $p_attr
     * @return bool
     */
    public function isFnct($p_attr) {
        $v_attr = $this->getAttr($p_attr);
        $v_key = $this->getKey($p_attr);
        foreach (array_keys($this->data[$v_key]) as $attr)
            if (preg_match("/($attr)/i", $p_attr)) {
                $v_attr = preg_replace("/\($attr\)/i", "", $v_attr);
                if (preg_match("/./i", $v_attr))
                    $v_attr = preg_replace("/\./i", "", $v_attr);
                if (in_array($v_attr, self::$function))
                        return true;
            }
        return false;
    }

    /**
     * @param $p_attr
     * @param bool $p_strReturn
     * @param bool $p_value
     * @param bool $p_isIn
     * @param bool $p_class
     * @return bool|string
     * @throws DbAttributesException
     */
    public function isStrColumn($p_attr, $p_strReturn = false, $p_value = false, $p_isIn = false, $p_class = false) {
        $v_attr = $this->getAttr($p_attr);
        $v_key = $this->getKey($p_attr);
        if ($p_isIn)
            foreach (array_keys($this->data[$v_key]) as $attr)
                if (preg_match("@^($attr)?[\+\-\/\*]@i", trim($v_attr)))
                    $v_attr = $attr;
        if (is_bool($v_attr))
            $v_attr = (int) $v_attr;
        if (array_key_exists($v_attr, $this->data[$v_key])) {
            if ($p_value)
                if (!$this->data[$v_key][$v_attr]['developer'])
                    return false;
            if ($p_strReturn)
                return $this->getStr($v_attr, $v_key, $p_class);
            else
                return true;
        } else
            foreach (array_keys($this->data[$v_key]) as $attr)
                if (preg_match("/($attr)/i", $p_attr)) {
                    $v_attr = preg_replace("/\($attr\)/i", "", $v_attr);
                    if (preg_match("/\./i", $v_attr))
                        $v_attr = preg_replace("/\./i", "", $v_attr);
                    if (in_array($v_attr, self::$function))
                        if ($p_strReturn === null)
                            return $v_attr . "(" . $this->getStr($attr, $v_key) . ")";
                        else if ($p_strReturn)
                            return $this->getStr($attr, $v_key, $p_class);
                        else
                            return true;
                }
        if ($p_strReturn)
            throw new DbAttributesException("p_attr is not a good column");
        return false;
    }

    /**
     * @param $p_attr
     * @param int $p_key
     * @param bool $p_class
     * @return string
     */
    public function getStr($p_attr, $p_key = 0, $p_class = false) {
        if ($this->isMultiTable() && !preg_match("/" . $this->tableName[$p_key]['table'] . "\./i", $p_attr))
            if ($p_class)
                return '{' . $this->tableName[$p_key]['class'] . '}.' . $p_attr;
            else
                return $this->tableName[$p_key]['table'] . "." . $p_attr;
        else
            return $p_attr;
    }

    /**
     * @param $p_attr
     * @return mixed
     */
    public function getAttr($p_attr) {
        if (preg_match('#^\{[a-zA-Z0-9\\\]+\}\.[a-zA-Z0-9_]+$#', stripcslashes($p_attr)))
            $p_attr = preg_replace("/\./i", "", strstr($p_attr, '.'));
        return $p_attr;
    }

    /**
     * @return bool
     */
    public function isMultiTable() {
        return (bool) (count($this->tableName) -1);
    }

    /**
     * @param $p_str
     * @return bool|int
     */
    public function getKey($p_str) {
        foreach ($this->tableName as $key => $table) {
            if (preg_match("#^\{" . stripcslashes($table['class']) . "\}\.[a-zA-Z0-9_]+$#", stripcslashes($p_str)))
                return $key;
        }
        foreach ($this->tableName as $key => $table) {
            if (in_array($p_str, $this->getProperties($key)))
                return $key;
        }
        return false;
    }

    public function getKeyByClass($p_class) {
        if (!class_exists($p_class))
            throw new DbAttributesException("Class do not exist");
        foreach ($this->tableName as $key => $value)
            if ($value['class'] == $p_class)
                return $key;
        throw new DbAttributesException("Class not in multi table");
    }

    /**
     * @return mixed
     * @throws DbAttributesException
     */
    public function getColumns() {
        $i = 0;
        $v_res = array();
        foreach ($this->data as $key => $value) {
            $p = array_keys($value);
            try {
                foreach ($p as $name)
                    $v_res[] = $this->getTableName($i) . ".$name AS '{" . $this->getTableName($i, true) . "}.$name'";
            } catch (DbAttributesException $e) {
                throw new DbAttributesException($e->getMessage());
            }
            $i++;
        }
        return implode(', ', $v_res);
    }

    /**
     * @param $p_attr
     * @param $p_value
     * @throws DbAttributesException
     */
    public function setStrAttr($p_attr, $p_value) {
        try {
            if ($this->getType($p_attr) == 'int')
                $this->setValue($p_attr, (int) $p_value);
            else if ($this->getType($p_attr) == 'datetime')
                $this->setValue($p_attr, new DbDateTime($p_value));
            else if ($this->getType($p_attr) == 'bool')
                $this->setValue($p_attr, (bool) $p_value);
            else
                $this->setValue($p_attr, $p_value);
        } catch (DbAttributesException $e) {
            throw new DbAttributesException($e->getMessage());
        }
    }

    /**
     * Implement iDbClass
     */

    /**
     * @param $name
     * @param $value
     * @return bool
     * @throws DbAttributesException
     */
    public function __set($name, $value) {
        try {
            if ($value != $this->getValue($name) || !$this->data[$this->getKey($name)][$this->getAttr($name)]['developer'])
                $this->setValue($name, $value, true);
            else
                return false;
        } catch (DbAttributesException $e) {
            throw new DbAttributesException($e->getMessage());
        }
        return true;
    }

    /**
     * @param $name
     * @return mixed
     * @throws DbAttributesException
     */
    public function __get($name) {
        try {
            return $this->getValue($name);
        } catch (DbAttributesException $e) {
            throw new DbAttributesException($e->getMessage());
        }
    }

    /**
     * @param $name
     * @return bool
     * @throws DbAttributesException
     */
    public function __isset($name) {
        if (!is_string($name))
            return false;
        try {
            if (!array_key_exists($this->getAttr($name), $this->data[$this->getKey($name)]))
                return false;
        } catch (DbAttributesException $e) {
            throw new DbAttributesException($e->getMessage());
        }
        return true;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset) {
        return isset($this->$offset);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset) {
        return $this->$offset;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @throws DbAttributesException
     */
    public function offsetSet($offset, $value) {
        try {
            if (is_string($value))
                $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($value != $this->getValue($offset) || $this->data[$this->getKey($offset)][$this->getAttr($offset)]['developer'])
                $this->setValue($offset, $value, false);
        } catch (DbAttributesException $e) {
            throw new DbAttributesException($e->getMessage());
        }
    }

    /**
     * @param mixed $offset
     * @throws DbAttributesException
     */
    public function offsetUnset($offset) {
        try {
            $this->setValue($offset, $this->getDefaultValue($offset));
        } catch (DbAttributesException $e) {
            throw new DbAttributesException($e->getMessage());
        }
    }
}