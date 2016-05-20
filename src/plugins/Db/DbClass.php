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
use PDO;
use PDOStatement;

/**
 * Class DbClassException
 * @package Astaroth
 */
final class DbClassException extends Exception {}

/**
 * Class DbQuery
 * @package Astaroth
 */
abstract class DbQuery {
    const INSERT = 0;
    const UPDATE = 1;
    const SELECT = 2;
    const SELECT_ARRAY = 3;
    const SEARCH_ARRAY = 4;
    const COUNT = 5;
    const DELETE = 6;
    const OTHER = 7;
}

/**
 * Class DbClass
 * @package Astaroth
 */
abstract class DbClass implements iDbClass {

    /**
     * ATTRIBUTES
     */

    /**
     * @var Db|null
     */
    private static $instance = null;

    /**
     * @var bool
     */
    private static $noException = false;

    /**
     * @var bool
     */
    private static $debug = false;

    /**
     * @var array|null
     */
    private $data_modify = null;

    /**
     * @var DbJoin|null
     */
    private $dbJoin = null;

    /**
     * @var DbWhere|null
     */
    private $dbWhere = null;

    /**
     * @var DbAfterWhere|null
     */
    private $dbAfterWhere = null;

    /**
     * @var DbAttributes|null
     */
    private $data = null;

    /**
     * @var bool
     */
    private $noExecute = false;

    /**
     * ABSTRACT METHODS
     */

    /**
     * @return mixed
     */
    abstract protected function base();

    /**
     * DbClass constructor.
     * @param null $p_attr
     * @throws DbClassException
     */
    public final function __construct($p_attr = null) {
        $this->data = new DbAttributes;
        $this->base();
        $todo = $this->data->getStrTableName();
        if (empty($todo))
            if (!self::$noException)
                throw new DbClassException("TableName not found");
            else
                return false;
        if ($p_attr !== null) {
            if (is_array($p_attr)) {
                try {
                    if ($this->data->constructAttr($p_attr))
                        $this->data_modify = array_keys($p_attr);
                } catch (DbAttributesException $e) {
                    if (!self::$noException)
                        throw new DbClassException($e->getMessage());
                    else
                        return false;
                }
            } else if (is_string($p_attr)) {
                $v_arr = explode(',', $p_attr);
                foreach ($v_arr as $value) {
                    $value = trim($value);
                    if (class_exists($value) && get_class($this) != $value) {
                        $value = new $value();
                        try {
                            $this->data->setDbAttributes($value->data);
                        } catch (DbAttributesException $e) {
                            if (!self::$noException)
                                throw new DbClassException($e->getMessage());
                            else
                                return false;
                        }
                    }
                }
            } else {
                if (!self::$noException)
                    throw new DbClassException("invalid argument");
                else
                    return false;
            }
        }
        if ($this->data === null)
            if (!self::$noException)
                throw new DbClassException("Attribute not found");
            else
                return false;
        try {
            $this->dbJoin = new DbJoin($this->data);
            $this->dbWhere = new DbWhere($this->data);
            $this->dbAfterWhere = new DbAfterWhere($this->data);
        } catch (DbEndQueryException $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }
        return true;
    }

    /**
     * PUBLIC STATIC METHODS
     */

    /**
     * @return PDOStatement
     */
    public static function getInstance() {
        return self::$instance;
    }

    /**
     * PROTECTED METHODS
     */

    /**
     * @param $p_type
     * @param string $p_columns
     * @param bool $p_continueWhere
     * @return array|bool|int
     * @throws DbClassException
     */
    protected final function SQL_CMD_OBJECT($p_type, $p_columns = null, $p_continueWhere = false) {
        if ($p_columns === null)
            $p_columns = '*';
        try {
            if (!$p_columns = $this->checkColumns($p_columns))
                if (!self::$noException)
                    throw new DbClassException("p_columns invalid format.");
                else
                    return false;
            switch ($p_type) {
                case DbQuery::INSERT:
                    return $this->insert();
                    break;
                case DbQuery::UPDATE:
                    return $this->update($p_continueWhere);
                    break;
                case DbQuery::SELECT:
                    return $this->selectOne($p_columns, $p_continueWhere);
                    break;
                case DbQuery::SELECT_ARRAY:
                    return $this->select($p_columns, $p_continueWhere);
                    break;
                case DbQuery::SEARCH_ARRAY:
                    return $this->select($p_columns, $p_continueWhere, true);
                    break;
                case DbQuery::COUNT:
                    return $this->counts($p_continueWhere);
                    break;
                case DbQuery::DELETE;
                    return $this->deletes($p_continueWhere);
                    break;
                default:
                    if (!self::$noException)
                        throw new DbClassException("Invalid operator DbQuery.");
                    else
                        return false;
                    break;
            }
        } catch (DbClassException $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }
    }

    /**
     * PRIVATE METHODS
     */

    /**
     * Insert into a table
     *
     * @return bool
     * @throws DbClassException
     */
    private final function insert() {
        $v_data = $this->data->getData()[0];
        $v_insert = [];

        foreach ($v_data as $key => $item) {
            if (count($v_primaryKey = $this->data->getPrimaryKey()) == 1)
                if (in_array($key, $v_primaryKey))
                    continue;
            $v_insert[$key] = $item['value'];
        }

        $v_query = sprintf("INSERT INTO " . $this->data->getStrTableName() . " (%s) VALUES (%s)",
            implode(', ', array_keys($v_insert)),
            implode(', ', array_fill(0, count($v_insert), '?'))
        );

        try {
            $v_stmt = self::$instance->prepare($v_query);
            if (!$this->noExecute)
                $v_stmt->execute(array_values($v_insert));
            else
                if (Db::$config['debug'])
                    var_dump(array_values($v_insert));
        } catch (Exception $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }

        $v_primaryKey = $this->data->getPrimaryKey();
        if (count($v_primaryKey) == 1)
            $this->data->$v_primaryKey[0] = self::$instance->lastInsertId();

        if ($v_stmt->rowCount() == 0)
            return false;
        return true;
    }

    /**
     * Updates row from a table
     *
     * @param bool $p_continueWhere
     * @return bool
     * @throws DbClassException
     */
    private final function update($p_continueWhere = false) {
        if ($this->data_modify === null)
            return false;
        $v_data = [];
        $v_value = [];
        foreach ($this->data_modify as $modified) {
            $v_key = $this->data->getKey($modified);
            if (in_array($modified, $this->data->getPrimaryKey()) || in_array($this->data->getStr($modified, $v_key), $this->data->getPrimaryKey()))
                continue;
            if ($this->data->getDeveloper($modified) && $this->data->isStrColumn($this->data->$modified, false, false, true))
                $v_data[] = $this->data->getStr($modified, $v_key) . " = ". $this->data->$modified;
            else {
                $v_data[] = $this->data->getStr($modified, $v_key) . " = ?";
                $v_value[] = $this->data->$modified;
            }
        }

        if (empty($v_data))
            return false;

        try {
            list($v_where, $v_params) = $this->_buildWhere($p_continueWhere);
        } catch (DbClassException $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }
        try {
            if ($this->setWhere()->getSyntax($v_data, true) == $v_where && $v_params == $v_value) {
                $this->unsetModifier();
                return true;
            }
        } catch (DbWhereException $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }

        $v_params = array_merge($v_value, $v_params);

        $v_query = "UPDATE " . $this->data->getStrTableName() . " SET " . implode(', ', $v_data) . " $v_where";

        try {
            $v_stmt = self::$instance->prepare($v_query);
            if (!$this->noExecute)
                $v_stmt->execute($v_params);
            else
                var_dump($v_params);
        } catch (Exception $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }

        $this->unsetModifier();
        if (Db::$config['debug'] === true)
            echo "Row Count " . $v_stmt->rowCount() . "<br>";
        if ($v_stmt->rowCount() == 0)
            return false;
        return true;
    }

    /**
     * Executes a SELECT $p_columns statement and returns the PDOStatement object
     *
     * @param string $p_columns
     * @param bool $p_continueWhere
     * @param bool $p_search
     * @return PDOStatement
     * @throws DbClassException
     */
    private final function executeSelect($p_columns = '*', $p_continueWhere = false, $p_search = null) {
        try {
            list($v_where, $v_params) = $this->_buildWhere($p_continueWhere, $p_search, true);
        } catch (DbClassException $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }
        list($v_afterWhere, $v_params1) = $this->_buildAfterWhere($p_continueWhere);
        $v_params = array_merge($v_params, $v_params1);
        $v_table = [];
        if (!$this->setJoin()->isEmpty()) {
            foreach ($this->data->getAllTableName() as $table)
                if (!in_array($table['class'], $this->setJoin()->getClassName()))
                    $v_table[] = $table['table'];
            $v_table = implode(', ', $v_table);
        } else {
            $v_table = $this->data->getStrTableName();
        }
        $v_join = $this->_buildJoin($p_continueWhere);
        $v_query = "SELECT $p_columns FROM $v_table $v_join $v_where $v_afterWhere";
        try {
            $v_stmt = self::$instance->prepare($v_query);
            if (!$this->noExecute || $p_continueWhere == true)
                $v_stmt->execute($v_params);
            else
                var_dump($v_params);
        } catch (Exception $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }
        if (Db::$config['debug'] === true)
            echo "Row Count " . $v_stmt->rowCount() . "<br>";
        if ($v_stmt->rowCount() == 0)
            return false;
        return $v_stmt;
    }

    /**
     * Executes a SELECT $p_columns and returns all rows as an array
     *
     * @param string $p_columns
     * @param bool $p_continueWhere
     * @param bool $p_search
     * @return array|bool
     * @throws DbClassException
     */
    private final function select($p_columns = '*', $p_continueWhere = false, $p_search = null) {
        try {
            if (!($v_stmt = $this->executeSelect($p_columns, $p_continueWhere, $p_search)))
                return false;
        } catch (DbClassException $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }
        return $v_stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Executes a SELECT $p_columns and returns the first row
     *
     * @param string $p_columns
     * @param bool $p_continueWhere
     * @return array|bool
     * @throws DbClassException
     */
    private final function selectOne($p_columns = '*', $p_continueWhere = false) {
        try {
            if (!($v_stmt = $this->executeSelect($p_columns, $p_continueWhere)))
                return false;
            $v_row = $v_stmt->fetch(PDO::FETCH_ASSOC);
            $v_stmt->closeCursor();
        } catch (Exception $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }
        return $v_row;
    }

    /**
     * Executes a SELECT $p_columns and returns the first column of the first row
     *
     * @param string $p_columns
     * @param bool $p_continueWhere
     * @return bool|string
     * @throws DbClassException
     */
    private final function selectValue($p_columns = '*', $p_continueWhere = false) {
        try {
            if (!($v_stmt = $this->executeSelect($p_columns, $p_continueWhere)))
                return false;
        } catch (DbClassException $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }
        return $v_stmt->fetchColumn();
    }

    /**
     * Executes a SELECT COUNT(*)
     *
     * @param bool $p_continueWhere
     * @return int
     * @throws DbClassException
     */
    private final function counts($p_continueWhere = false) {
        try {
            return (int)$this->selectValue('COUNT(*)', $p_continueWhere);
        } catch (DbClassException $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }
    }

    /**
     * Deletes row from a table
     *
     * @param bool $p_continueWhere
     * @return bool
     * @throws DbClassException
     */
    private final function deletes($p_continueWhere = false) {
        try {
            list($v_where, $v_params) = $this->_buildWhere($p_continueWhere);
        } catch (DbClassException $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }

        try {
            $v_query = "DELETE FROM " . $this->data->getTableName() . " $v_where";
        } catch (DbAttributesException $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }

        try {
            $v_stmt = self::$instance->prepare($v_query);
            if (!$this->noExecute)
                $v_stmt->execute($v_params);
            else
                var_dump($v_params);
        } catch (Exception $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }
        if ($v_stmt->rowCount() == 0)
            return false;
        return true;
    }

    /**
     * @param $p_prop
     * @return bool
     * @throws DbClassException
     */
    private final function setModifier($p_prop) {
        try {
            if (isset($this->$p_prop))
                $this->data_modify[] = $p_prop;
            else
                return false;
            return true;
        } catch (DbClassException $e) {
            if (!self::$noException)
                throw new DbClassException($e);
            else
                return false;
        }
    }

    /**
     * void
     */
    private final function unsetModifier() {
        $this->data_modify = null;
    }

    /**
     * @param bool $p_continueWhere
     * @return string|void
     */
    private final function _buildJoin($p_continueWhere = false) {
        if ($p_continueWhere === true)
            return $this->dbJoin->build();
        else
            return $this->dbJoin->reset(true);
    }

    /**
     * @param bool $p_continueWhere
     * @param bool $p_search
     * @param bool $p_isSelect
     * @return array
     * @throws DbClassException
     */
    private final function _buildWhere($p_continueWhere = false, $p_search = null, $p_isSelect = false) {
        try {
            if (!$this->dbWhere->isEmpty())
                if ($p_continueWhere === true)
                    return $this->dbWhere->build();
                else
                    return $this->dbWhere->reset(true);
            $v_ope = !$p_search ? DbWhereOperator::EQUAL : DbWhereOperator::SEARCH;
            if (!$p_search && !empty($v_primaryKey = $this->data->getPrimaryKey()))
                foreach ($v_primaryKey as $primaryKey)
                    if ($this->data->$primaryKey != $this->data->getDefaultValue($primaryKey))
                        $this->setWhere()->add(DbWhereOperator::EQUAL, $primaryKey, $this->data->$primaryKey);
            if (($this->setWhere()->isEmpty() || $this->dbWhere->isJoin()) && $this->data_modify !== null) {
                foreach ($this->data->getProperties() as $prop) {
                    if ($p_isSelect && $this->dbWhere->isJoin()) {
                        if (!in_array($prop, $this->data->getPrimaryKey()) && in_array($prop, $this->data_modify) && $this->data->$prop != $this->data->getDefaultValue($prop))
                            $this->setWhere()->add($v_ope, $prop, $this->data->$prop);
                    } else if (!in_array($prop, $this->data_modify) && $this->data->$prop != $this->data->getDefaultValue($prop))
                        $this->setWhere()->add($v_ope, $prop, $this->data->$prop);
                }
            }
            if ($this->setWhere()->isEmpty())
                foreach ($this->data->getProperties() as $prop) {
                    if ($this->data->$prop != $this->data->getDefaultValue($prop))
                        $this->setWhere()->add($v_ope, $prop, $this->data->$prop);
                }
            if ($p_continueWhere === true)
                return $this->dbWhere->build();
            else
                return $this->dbWhere->reset(true);
        } catch (Exception $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }
    }

    /**
     * @param bool $p_continueWhere
     * @return mixed
     */
    private final function _buildAfterWhere($p_continueWhere = false) {
        if ($p_continueWhere === true)
            return $this->dbAfterWhere->build();
        else
            return $this->dbAfterWhere->reset(true);
    }

    /**
     * @param string $p_columns
     * @param bool $p_continueWhere
     * @param bool $p_search
     * @return array|bool
     * @throws DbClassException
     */
    private final function LGS($p_columns = '*', $p_continueWhere = false, $p_search = null) {
        try {
            if ($p_search) {
                if (!($v_stmt = $this->SQL_CMD_OBJECT(DbQuery::SEARCH_ARRAY, $p_columns, $p_continueWhere)))
                    return false;
            } else
                if (!($v_stmt = $this->SQL_CMD_OBJECT(DbQuery::SELECT_ARRAY, $p_columns, $p_continueWhere)))
                    return false;
        } catch (DbClassException $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }
        $v_rslt = [];
        $v_classname = get_class($this);
        try {
            foreach ($v_stmt as $key_array) {
                $v_obj = new $v_classname();
                $v_obj->data->setDbAttributes($this->data);
                foreach ($key_array as $key => $value)
                    $v_obj->data->setStrAttr($key, $value);
                $v_obj->unsetModifier();
                $v_rslt[] = $v_obj;
            }
        } catch (DbClassException $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }
        return $v_rslt;
    }

    /**
     * @param array|string $p_columns
     * @return bool
     * @throws DbClassException
     */
    private final function checkColumns($p_columns) {
        try {
            if ($this->data->isMultiTable() && $p_columns === '*')
                return $this->data->getColumns();
            else if (!$this->data->isMultiTable() && $p_columns === '*')
                return '*';
            if (is_array($p_columns))
                $v_columns = $p_columns;
            else if (is_string($p_columns))
                $v_columns = explode(',', $p_columns);
            else
                if (!self::$noException)
                    throw new DbClassException("Invalid p_columns type");
                else
                    return false;
            foreach ($v_columns as $key => $value) {
                $value = trim($value);
                if ($this->data->isMultiTable())
                    $v_columns[$key] = $this->data->isStrColumn($value, $this->data->isFnct($value) ? null : true) . " AS '" . preg_replace('/\./i', '.', $this->data->isStrColumn($value, true, false, false, true)) . "'";
                else
                    if (!$this->data->isStrColumn($value))
                        return false;
            }
        } catch (DbAttributesException $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }
        return implode(", ", $v_columns);
    }

    /**
     * Public Methods
     */

    /**
     * @return DbJoin|null
     */
    public final function setJoin() {
        return $this->dbJoin;
    }

    /**
     * @return DbWhere|null
     */
    public final function setWhere() {
        return $this->dbWhere;
    }

    /**
     * @return DbAfterWhere|null
     */
    public final function setAfterWhere() {
        return $this->dbAfterWhere;
    }

    /**
     * @param $p_noExecute
     * @return bool
     * @throws DbClassException
     */
    public final function setNoExecute($p_noExecute) {
        if (!is_bool($p_noExecute))
            if (!self::$noException)
                throw new DbClassException("p_noExecute must be a boolean");
            else
                return false;
        $this->noExecute = $p_noExecute;
        return true;
    }

    /**
     * @param $p_noException
     * @return bool
     * @throws DbClassException
     */
    public static final function setNoException($p_noException) {
        if (!is_bool($p_noException))
            if (!self::$noException)
                throw new DbClassException("p_noExecute must be a boolean");
            else
                return false;
        self::$noException = $p_noException;
        return true;
    }

    /**
     * @param $p_debug
     * @return bool
     * @throws DbClassException
     */
    public static final function setDebug($p_debug) {
        if (!is_bool($p_debug))
            if (!self::$noException)
                throw new DbClassException("p_noExecute must be a boolean");
            else
                return false;
        self::$debug = $p_debug;
        return true;
    }

    /**
     * @param bool $p_continueWhere
     * @param bool $p_forceUpdate
     * @return bool
     * @throws DbAttributesException
     * @throws DbClassException
     */
    public final function Save($p_continueWhere = false, $p_forceUpdate = false)
    {
        if ($this->data_modify === null) {
            return true;
        }
        $flag = false;
        if (!$p_forceUpdate && !empty($v_primaryKey = $this->data->getPrimaryKey()))
            foreach ($v_primaryKey as $primaryKey)
                if ($this->data->$primaryKey != $this->data->getDefaultValue($primaryKey))
                    $flag = true;
        if ($p_forceUpdate && !$this->dbWhere->isEmpty() || $flag && $this->Exist(true)) {
            if (!($v_stmt = $this->SQL_CMD_OBJECT(DbQuery::UPDATE, null, $p_continueWhere)))
                return false;
        } else if (!$p_forceUpdate) {
            if (!($v_stmt = $this->SQL_CMD_OBJECT(DbQuery::INSERT)))
                return false;
        } else {
            if (!self::$noException)
                throw new DbClassException("Impossible to update");
            else
                return false;
        }
        return true;
    }

    /**
     * @param bool $p_continueWhere
     * @return array|bool|int
     * @throws DbClassException
     */
    public final function Delete($p_continueWhere = false) {
        try {
            return $this->SQL_CMD_OBJECT(DbQuery::DELETE, null, $p_continueWhere);
        } catch (DbClassException $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }
    }

    /**
     * @param array|string $p_columns
     * @param bool $p_continueWhere
     * @return bool
     * @throws DbClassException
     */
    public final function Get($p_columns = '*', $p_continueWhere = false) {
        if ($this->data_modify === null && $this->setWhere()->isEmpty() && $this->setAfterWhere()->isEmpty() && !$this->data->isFnct($p_columns))
            return false;
        try {
            if (!($v_stmt = $this->SQL_CMD_OBJECT(DbQuery::SELECT, $p_columns, $p_continueWhere)))
                return false;
        } catch (DbClassException $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }
        try {
            foreach ($v_stmt as $key => $value)
                $this->data->setStrAttr($key, $value);
        } catch (DbAttributesException $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }
        $this->unsetModifier();
        return true;
    }

    /**
     * @param array|string $p_columns
     * @param bool $p_continueWhere
     * @return array|bool
     * @throws DbClassException
     */
    public final function LGet($p_columns = '*', $p_continueWhere = false) {
        try {
            return $this->LGS($p_columns, $p_continueWhere, false);
        } catch (DbClassException $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }
    }

    /**
     * @param array|string $p_columns
     * @param bool $p_continueWhere
     * @return array|bool
     * @throws DbClassException
     */
    public final function LSearch($p_columns = '*', $p_continueWhere = false) {
        try {
            return $this->LGS($p_columns, $p_continueWhere, true);
        } catch (DbClassException $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }
    }

    /**
     * @param bool $p_continueWhere
     * @return bool
     * @throws DbClassException
     */
    public function Exist($p_continueWhere = false) {
        try {
            return (bool) $this->SQL_CMD_OBJECT(DbQuery::COUNT, null, $p_continueWhere);
        } catch (DbClassException $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }
    }

    /**
     * @param bool $p_continueWhere
     * @return array|bool|int
     * @throws DbClassException
     */
    public final function Count($p_continueWhere = false) {
        try {
            return $this->SQL_CMD_OBJECT(DbQuery::COUNT, null, $p_continueWhere);
        } catch (DbClassException $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }
    }

    /**
     * Public Static methods
     */

    /**
     * @param Db $p_instance
     * @return bool
     * @throws DbClassException
     */
    public final static function setInstance(Db &$p_instance) {
        if (!($p_instance instanceof Db) || !($p_instance instanceof iPlugin))
            if (!self::$noException)
                throw new DbClassException("p_instance must be an instance of Db and iPlugin");
            else
                return false;
        self::$instance = &$p_instance;
        return true;
    }

    public final static function _Str($p) {
        return '{' . static::class . '}.' . $p;
    }

    /**
     * @param $p
     * @return mixed
     * @throws DbClassException
     */
    public final function _T($p) {
        try {
            return $this->$p;
        } catch (DbClassException $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }
    }

    /**
     * Implement of iDbClass
     */

    /**
     * @param $name
     * @return bool
     * @throws DbClassException
     */
    public final function __isset($name) {
        try {
            return isset($this->data->$name);
        } catch (DbAttributesException $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }
    }

    /**
     * @param $name
     * @param $value
     * @return bool
     * @throws DbClassException
     */
    public final function __set($name, $value) {
        try {
            if ($this->data->$name = $value)
                $this->setModifier($name);
        } catch (Exception $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }
        return true;
    }

    /**
     * @param $name
     * @return mixed
     * @throws DbClassException
     */
    public final function __get($name) {
        try {
            return $this->data->$name;
        } catch (DbAttributesException $e) {
            if (!self::$noException)
                throw new DbClassException($e);
            else
                return false;
        }
    }

    /**
     * @param $name
     * @param $arguments
     * @return bool
     * @throws DbClassException
     */
    public final function __call($name, $arguments) {
        $v_auto = ['setTableName', 'add', 'addNoSQL', 'getProperties', 'setPrimaryKey'];
        if (!in_array($name, $v_auto))
            if (!self::$noException)
                throw new DbClassException("Name must be a good name");
            else
                return false;
        try {
            if ($name == 'setTableName')
                $this->data->$name($arguments, get_class($this));
            else
                $this->data->$name($arguments);
        } catch (DbAttributesException $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }
        return true;
    }

    /**
     * @param mixed $offset
     * @return bool
     * @throws DbClassException
     */
    public final function offsetExists($offset) {
        try {
            return isset($this->$offset);
        } catch (DbClassException $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }
    }

    /**
     * @param mixed $offset
     * @return mixed
     * @throws DbClassException
     */
    public final function offsetGet($offset) {
        try {
            return $this->data[$offset];
        } catch (DbAttributesException $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @return bool
     * @throws DbClassException
     */
    public final function offsetSet($offset, $value) {
        try {
            if ($value != $this->data[$offset]) {
                $this->data[$offset] = $value;
                $this->setModifier($offset);
            }
        } catch (Exception $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }
        return true;
    }

    /**
     * @param mixed $offset
     * @return bool
     * @throws DbClassException
     */
    public final function offsetUnset($offset) {
        try {
            unset($this->data[$offset]);
        } catch (DbAttributesException $e) {
            if (!self::$noException)
                throw new DbClassException($e->getMessage());
            else
                return false;
        }
        return true;
    }
}