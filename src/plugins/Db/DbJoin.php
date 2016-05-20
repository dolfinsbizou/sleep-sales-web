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

final class DbJoinException extends Exception {}

abstract class DbJoinOperator {
    const INNER = 'INNER JOIN';
    const CROSS = 'CROSS JOIN';
    const LEFT = 'LEFT JOIN';
    const RIGHT = 'RIGHT JOIN';
    const FULL = 'FULL JOIN';
    const NATURAL = 'NATURAL JOIN';
}

/**
 * Class DbJoin
 * @package Astaroth
 */
final class DbJoin extends DbEndQuery {
    // TODO Exception try catch
    /**
     * @var array
     */
    private $key = [];

    /**
     * @var array
     */
    private $sup = [];

    /**
     * @var array
     */
    private $class = [];

    /**
     * @param $p_operator
     * @param $p_class
     * @param string $p_value1
     * @param string $p_value2
     * @param null $p_logic
     * @throws DbAttributesException
     * @throws DbJoinException
     */
    public function add($p_operator, $p_class, $p_value1 = "", $p_value2 = "", $p_logic = null) {
        $v_sup = -1;
        if (!empty($p_class) && in_array($p_class, $this->getClassName()))
            foreach ($this->class as $key => $class)
                if ($p_class == $class['name'] && $p_operator != $class['operator'])
                    throw new DbJoinException();
                else
                    $v_sup = $key;
        if (empty($p_class))
            foreach ($this->attrs->getAllTableName() as $value)
                if ($p_class == $value['class'])
                    throw new DbJoinException();
        if ($v_sup == -1) {
            $v_class = new $p_class();
            $this->attrs->setDbAttributes($v_class->setJoin()->attrs);
            $this->class[] = ['name' => $p_class, 'operator' => $p_operator];
        }
        switch ($p_operator) {
            case DbJoinOperator::INNER:
            case DbJoinOperator::LEFT:
            case DbJoinOperator::RIGHT:
            case DbJoinOperator::FULL:
                if (empty($p_value1) || empty($p_value2))
                    throw new DbJoinException();
                $v_tableName = $this->attrs->getTableNameByClass($p_class);
                $v_key = $this->attrs->getKeyByClass($p_class);
                if ($v_tableName == $this->attrs->getTableName(0))
                    throw new DbJoinException();
                if (($v_key1 = $this->attrs->getKey($p_value1)) == ($v_key2 = $this->attrs->getKey($p_value2)))
                    throw new DbJoinException();
                if ((!in_array($v_key1, $this->attrs->getAllKeyWithoutClass($p_class)) && !in_array($v_key2, $this->attrs->getAllKeyWithoutClass($p_class))) || ($v_key1 != $v_key && $v_key2 != $v_key))
                    throw new DbJoinException();
                $p_value1 = $this->attrs->getStr($this->attrs->getAttr($p_value1), $v_key1);
                $p_value2 = $this->attrs->getStr($this->attrs->getAttr($p_value2), $v_key2);
                if ($v_sup == -1)
                    $this->key[] = "$p_operator $v_tableName ON $p_value1 = $p_value2";
                else {
                    if ($p_logic == null)
                        $v_ope = 'AND';
                    else if ($p_logic == 'AND' || $p_logic == 'OR')
                        $v_ope = $p_logic;
                    else
                        throw new DbJoinException();
                    $this->sup[$v_sup][] = " $v_ope $p_value1 = $p_value2";
                }
                break;
            case DbJoinOperator::CROSS:
            case DbJoinOperator::NATURAL:
                $v_tableName = $this->attrs->getTableNameByClass($p_class);
                if ($v_tableName == $this->attrs->getTableName(0))
                    throw new DbJoinException();
                $this->key[] = "$p_operator $v_tableName";
                break;
            default:
                throw new DbJoinException();
                break;
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getClass() {
        return $this->class;
    }

    public function getClassName() {
        $v_rsl = [];
        foreach ($this->class as $class)
            $v_rsl[] = $class['name'];
        return $v_rsl;
    }

    /**
     * @return bool
     */
    public function isEmpty() {
        return empty($this->key);
    }

    /**
     * @return string
     */
    public function build() {
        $v_rsl = [];
        foreach ($this->key as $key => $value) {
            if (isset($this->sup[$key]))
                $v_rsl[] = $value . implode(' ', $this->sup[$key]);
            else
                $v_rsl[] = $value;
        }
        return implode(' ', $v_rsl);
    }

    /**
     * @param bool $p_build
     * @return string
     */
    public function reset($p_build = false) {
        if ($p_build)
            $v_build = $this->build();
        $this->key = [];
        $this->sup = [];
        $this->class = [];
        if ($p_build)
            return $v_build;
    }
}