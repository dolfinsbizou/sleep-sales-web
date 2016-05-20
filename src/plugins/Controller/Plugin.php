<?php
/*
 * This file is part of the Astaroth package.
 *
 * (c) 2016 Victorien POTTIAU ~ Emmanuel LEROUX
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Astaroth\Controller;

use Astaroth;
use AstarothException;
use AstarothHttpException;
use Astaroth\iPlugin;

/**
 * Controller plugin
 * 
 * @package Astaroth
 * @subpackage Plugins
 */
class Plugin implements iPlugin
{
    /** @var array */
    public static $config = array();
    
    /**
     * Plugin starts
     *
     * @param array $config
     */
    public static function start(&$config)
    {
        $config = array_merge(array(
        
            // default action name
            'default_action' => 'index',
        
            // directories where to find controllers
            'dirs' => array('app/actions', 'app/controllers'),

            // default controller namespaces
            'namespace' => ''
            
        ), $config);
        
        self::$config = &$config;
        Astaroth::set('app.executor', 'Astaroth\Controller\Plugin::execute');
        Astaroth::add('astaroth.dirs.includes', array_filter(Astaroth::path((array) self::$config['dirs'])));
    }

    /**
     * Executor which defines controllers and actions MVC-style
     *
     * @param string $action
     * @param string $method
     * @param $vars
     * @param array $context
     * @return array
     * @throws AstarothException
     * @throws AstarothHttpException
     */
    public static function execute($action, $method, $vars, &$context)
    {
        $controller = trim(dirname($action), './');
        $action = basename($action);
        if (empty($controller)) {
            $controller = $action;
            $action = self::$config['default_action'];
        }

        $className = trim(self::$config['namespace'] . '\\' 
                   . str_replace(' ', '\\', ucwords(str_replace('/', ' ', $controller))) 
                   . 'Controller', '\\');

        Astaroth::fireEvent('Controller::Execute', array(&$className));

        if (!class_exists($className)) {
            throw new AstarothHttpException("Class '$className' not found", 404);
        } else if (!is_subclass_of($className, 'Astaroth\Controller\Controller')) {
            throw new AstarothException("Class '$className' must subclass 'Astaroth\Controller\Controller'");
        }
        
        $instance = new $className();
        if (($vars = $instance->_dispatch($action, $method, $vars)) === false) {
            return false;
        }
        
        if (!is_array($vars)) {
            $vars = array();
        }
        return array_merge(get_object_vars($instance), $vars);
    }
}

