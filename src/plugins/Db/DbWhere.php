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

final class DbWhereException extends Exception {}

/**
 * Class DbWhereOperator
 * @package Astaroth
 */
abstract class DbWhereOperator {
    const EQUAL = '=';
    const NOT_EQUAL = '!=';
    const SUP = '>';
    const INF = '<';
    const SUP_EQUAL = '>=';
    const INF_EQUAL = '<=';
    const SEARCH = 'LIKE';
    const BETWEEN = 'BETWEEN';
    const NOT_BETWEEN = 'NOT BETWEEN';
    const IS_NULL = 'IS NULL';
    const IS_NOT_NULL = 'IS NOT NULL';
    const IN = 'IN';
}

/**
 * Class DbWhere
 * @package Astaroth
 */
final class DbWhere extends DbEndQuery {

    private $key;

    private $value = [];

    private $logic;

    private $having_mode = false;

    private $join = false;

    private $attr = [];

    /**
     * @param $p_operator
     * @param $p_attr
     * @param $p_value
     * @param null $p_logic
     * @param bool $p_developer
     * @return $this
     * @throws DbWhereException
     */
    public function add($p_operator, $p_attr, $p_value, $p_logic = null, $p_developer = false) {
        try {
            $p_attr = $this->attrs->isStrColumn($p_attr, $this->having_mode ? null : true, false, false, true);
        } catch (DbAttributesException $e) {
            throw new DbWhereException($e->getMessage());
        }
        switch ($p_operator) {
            case DbWhereOperator::EQUAL:
            case DbWhereOperator::NOT_EQUAL:
            case DbWhereOperator::SUP:
            case DbWhereOperator::SUP_EQUAL:
            case DbWhereOperator::INF:
            case DbWhereOperator::INF_EQUAL:
                try {
                    if (!$this->attrs->isStrColumn($p_value, false) || (!$this->attrs->getDeveloper($p_attr) && !$p_developer)) {
                        $this->attrs->isGoodType($p_value, $this->attrs->getType($p_attr));
                        $this->key[] = $this->attrs->getStr($this->attrs->getAttr($p_attr), $this->attrs->getKey($p_attr)) . " $p_operator ?";
                        $this->value[] = $p_value;
                    } else {
                        $p_value = $this->attrs->getStr($this->attrs->getAttr($p_value), $this->attrs->getKey($p_value));
                        $p_attr = $this->attrs->getStr($this->attrs->getAttr($p_attr), $this->attrs->getKey($p_attr));
                        if ($p_value == $p_attr)
                            return false;
                        $this->join = true;
                        $this->key[] = "$p_attr $p_operator $p_value";
                    }
                } catch (DbAttributesException $e) {
                    throw new DbWhereException($e->getMessage());
                }
                break;
            case DbWhereOperator::SEARCH:
                // TODO a refaire
                if (!isset($this->attrs->$p_attr))
                    throw new DbWhereException("attribute not valid");
                if (!is_string($p_value))
                    throw new DbWhereException("Invalid string format");
                $this->key[] = "$p_attr $p_operator $p_value";
                break;
            case DbWhereOperator::BETWEEN:
            case DbWhereOperator::NOT_BETWEEN:
                if (!is_array($p_value))
                    throw new DbWhereException("p_value must be an array");
                try {
                    $this->attrs->isGoodType($p_value[0], $this->attrs->getType($p_attr));
                    $this->attrs->isGoodType($p_value[1], $this->attrs->getType($p_attr));
                } catch (DbAttributesException $e) {
                    throw new DbWhereException($e->getMessage());
                }
                $this->key[] = "$p_attr $p_operator ? AND ?";
                $this->value[] = $p_value[0];
                $this->value[] = $p_value[1];
                break;
            case DbWhereOperator::IS_NULL:
            case DbWhereOperator::IS_NOT_NULL:
                if (!isset($this->attrs->$p_attr))
                    throw new DbWhereException("attribute not valid");
                $this->key[] = "$p_attr $p_operator";
                break;
            case DbWhereOperator::IN:
                if (!is_array($p_value))
                    throw new DbWhereException("attribute not valid");
                try {
                    foreach ($p_value as $value)
                        $this->attrs->isGoodType($value, $this->attrs->getType($p_attr));
                } catch (DbAttributesException $e) {
                    throw new DbWhereException($e->getMessage());
                }
                $this->key[] = "$p_attr $p_operator ( " . implode(', ', array_fill(0, count($p_value), '?')) ." )";
                $this->value = array_merge($this->value, $p_value);
                break;
            default:
                throw new DbWhereException("incorrect operator");
                break;
        }
        if ($p_logic !== null && is_string($p_logic) && ($p_logic == 'AND' || $p_logic == 'OR'))
            $this->logic[] = $p_logic;
        else
            $this->logic[] = 'AND';
        $this->attr[] = $p_attr;
        return $this;
    }

    /**
     * @param $p_key
     * @param bool $p_virtual
     * @return string
     * @throws DbWhereException
     */
    public function getSyntax($p_key, $p_virtual = false) {
        if (!is_array($p_key))
            throw new DbWhereException("p_key must be an array");
        $v_rst = "";
        $i = 0;
        foreach ($p_key as $key) {
            if ($i > 0)
                $v_rst .= ' ' . (($p_virtual) ? 'AND' : $this->logic[$i-1]) . ' ';
            $v_rst .= $key;
            $i++;
        }
        return ($this->having_mode ? 'HAVING' : 'WHERE') . ' ' . $v_rst;
    }

    public function getAttr() {
        return $this->attr;
    }

    /**
     * @param $p_mode
     * @throws DbWhereException
     */
    public function setHavingMode($p_mode) {
        if (!is_bool($p_mode))
            throw new DbWhereException("p_mode must be a boolean");
        $this->having_mode = $p_mode;
    }

    /**
     * @return array
     * @throws DbWhereException
     */
    public function build() {
        if ($this->isEmpty())
            return ['', []];
        try {
            return [
                $this->getSyntax($this->key),
                $this->value
            ];
        } catch (DbWhereException $e) {
            throw new DbWhereException($e->getMessage());
        }
    }

    public function isJoin() {
        return $this->join;
    }

    /**
     * @return bool
     */
    public function isEmpty() {
        if (empty($this->key))
            return true;
        return false;
    }

    /**
     * @param bool $p_build
     * @return array
     * @throws DbWhereException
     */
    public function reset($p_build = false) {
        if ($p_build) {
            try {
                $v_build = $this->build();
            } catch (DbWhereException $e) {
                throw new DbWhereException($e->getMessage());
            }
        }
        $this->key = null;
        $this->attr = [];
        $this->value = [];
        $this->joint = false;
        if ($p_build)
            return $v_build;
    }
}