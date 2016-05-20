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

use Astaroth;
use Exception;
use PDO;
use PDOException;

final class DbException extends Exception {}

/**
 * Class Db
 * @package Astaroth
 */
final class Db extends PDO implements iPlugin {
    /**
     * @var array
     */
    public static $config = [];

    /**
     * @var Db
     */
    private static $instance;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @param $config
     * @throws DbClassException
     * @throws DbException
     */
    public static function start(&$config) {
        $config = array_merge([
                'debug'             => false,

                'dsn'               => false,

                'username'          => 'root',

                'passwd'            => '',

                'noException'       => false,

                'driver_options'    => array(
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
                ),

                'dir'               => 'app/db'
            ], $config
        );
        $loadDependence = [
            'DbEndQuery' => false,
            'DbAfterWhere' => false,
            'DbClass' => true,
            'DbDateTime' => false,
            'DbAttributes' => false,
            'DbPDOStatement' => false,
            'DbRemote' => false,
            'DbWhere' => false,
            'DbJoin' => false
        ];
        self::$config = &$config;
        try {
            Astaroth::set(['astaroth' => array('dirs' => array('Db' => self::$config['dir']))]);
            Astaroth::set(['astaroth' => array('dirs' => array('db' => self::$config['dir']))]);
            self::loadDependence($loadDependence);
            self::$instance = self::connect();
            DbClass::setInstance(self::$instance);
            DbClass::setDebug(self::$config['debug']);
            DbClass::setNoException(self::$config['noException']);
        } catch (Exception $e) {
            throw new DbException($e->getMessage());
        }
    }

    /**
     * Db constructor.
     * @param $dsn
     * @param $username
     * @param $passwd
     * @param $options
     * @param bool $debug
     */
    public function __construct($dsn, $username, $passwd, $options, $debug = false) {
        parent::__construct($dsn, $username, $passwd, $options);
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->debug = $debug;
        if ($this->debug) {
            $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('Astaroth\DbPDOStatement', [$this]));
        }
    }

    /**
     * @param $p_loadDependence
     * @throws DbException
     */
    private static function loadDependence(&$p_loadDependence) {
        if (!is_array($p_loadDependence))
            throw new DbException("p_loadDependence must be an array");
        foreach ($p_loadDependence as $class => $interface) {
            if (!class_exists($class)) {
                if ($interface)
                    if (($include = Astaroth::findFile("Db/i$class.php", Astaroth::get('astaroth.dirs.plugins'), true)) !== false)
                        include($include[0]);
                if (($include = Astaroth::findFile("Db/$class.php", Astaroth::get('astaroth.dirs.plugins'), true)) !== false)
                    include($include[0]);
            }
        }
        $path =  Astaroth::path(Astaroth::get('astaroth.dirs.db'), null, false);
        $dir = opendir($path);
        while($file = readdir($dir)){
            if(is_file($path . '/'. $file) && $file != '/' && $file != '.' && $file != '..'){
                include $path . '/' . $file;
            }
        }
        closedir($dir);
    }

    /**
     * @return Db
     * @throws DbException
     */
    private static function connect() {
        try {
            return new Db(self::$config['dsn'], self::$config['username'], self::$config['passwd'], self::$config['driver_options'], self::$config['debug']);
        } catch (PDOException $e) {
            throw new DbException("SQL connection failed.");
        }
    }

    /**
     * @param string $statement
     * @param array $driver_options
     * @return \PDOStatement
     */
    public function prepare($statement, $driver_options = array()) {
        if ($this->debug)
            echo "Statement : $statement<br>";
        return parent::prepare($statement, $driver_options);
    }
}

?>