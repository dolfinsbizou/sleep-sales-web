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

final class DbAfterWhereException extends Exception {}

abstract class DbAfterWhereOperator {
    const ORDER_BY = 'ORDER BY';
    const HAVING = 'HAVING';
    const GROUP_BY = 'GROUP BY';
    const LIMIT = 'LIMIT';
}

final class DbAfterWhere extends DbEndQuery {

    private $key = [];

    /**
     * PUBLIC METHODS
     */

    /**
     * Add rules
     *
     * @param $p_operator
     * @param array|string|null $p_attrs
     * @param null $p_operatorPrime
     * @param null $p_value
     * @param null $p_logic
     * @throws DbAfterWhereException
     */
    public function add($p_operator, $p_attrs = null, $p_operatorPrime = null, $p_value = null, $p_logic = null) {
        switch ($p_operator) {
            case DbAfterWhereOperator::ORDER_BY:
                if ($p_operatorPrime === null)
                    $p_operatorPrime = 'ASC';
                else if ($p_operatorPrime != 'ASC' && $p_operatorPrime != 'DESC')
                    throw new DbAfterWhereException("p_operatorPrime must be 'ASC' OR 'DESC'");
                if (is_string($p_attrs))
                    $p_attrs = explode(',', $p_attrs);
                foreach ($p_attrs as $key => $value) {
                    try {
                        $p_attrs[$key] = $this->attrs->isStrColumn(trim($value), true);
                    } catch (DbAttributesException $e) {
                        throw new DbAfterWhereException($e->getMessage());
                    }
                }
                $p_attrs = implode(', ', $p_attrs);
                $this->key[2][] = (empty($this->key[2]) ? $p_operator . ' ' : '') . "$p_attrs $p_operatorPrime";
                break;
            case DbAfterWhereOperator::HAVING:
                try {
                    if (empty($this->key[1])) {
                        $this->key[1] = new DbWhere($this->attrs);
                        $this->key[1]->setHavingMode(true);
                    }
                    $this->key[1]->add($p_operatorPrime, $p_attrs, $p_value, $p_logic);
                } catch (DbWhereException $e) {
                    throw new DbAfterWhereException($e->getMessage());
                }
                break;
            case DbAfterWhereOperator::GROUP_BY:
                $this->key[0] = "$p_operator " . $this->attrs->isStrColumn($p_attrs, true);
                break;
            case DbAfterWhereOperator::LIMIT:
                if ($p_attrs !== null)
                    throw new DbAfterWhereException("p_attrs must be null");
                if (!is_array($p_value) && !is_int($p_value))
                    throw new DbAfterWhereException("p_attrs must be an array OR an integer");
                if (isset($p_value[0]) && is_int($p_value[0]) && !isset($p_value[1]))
                    $this->key[3] = "$p_operator $p_value[0]";
                else if (is_int($p_value))
                    $this->key[3] = "$p_operator $p_value";
                else if (isset($p_value[0]) && is_int($p_value[0]) && isset($p_value[1]) && is_int($p_value[1]))
                    $this->key[3] = "$p_operator $p_value[0]" . ($p_operatorPrime === true ? ' OFFSET ' : ', ') . $p_value[1];
                else
                    throw new DbAfterWhereException("incorrect type/format of p_value");
                break;
            default:
                throw new DbAfterWhereException("incorrect operator");
                break;
        }
    }

    /**
     * @return array
     */
    public function build() {
        $v_build = '';
        $v_params = [];
        for ($i = 0; $i < 4; $i++) {
            if (empty($this->key[$i]))
                continue;
            $v_build .= " ";
            if (is_array($this->key[$i]))
                $v_build .= implode(', ', $this->key[$i]);
            else if ($i == 1 && $this->key[$i] instanceof DbWhere) {
                list($v_where, $v_params) = $this->key[$i]->build();
                $v_build .= $v_where;
            } else
                $v_build .= $this->key[$i];
        }
        return array(
            $v_build,
            $v_params
        );
    }

    /**
     * @return bool
     */
    public function isEmpty() {
        return empty($this->key);
    }

    /**
     * @param bool $p_build
     * @return array
     */
    public function reset($p_build = false) {
        if ($p_build)
            $v_build = $this->build();
        if (!empty($this->key[1]) && $this->key[1] instanceof DbWhere)
            $this->key[1]->reset();
        $this->key = [];
        if ($p_build)
            return $v_build;
    }
}

?>