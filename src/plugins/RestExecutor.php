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
use AstarothException;

class RestExecutor implements iPlugin
{
    /** @var array */
    public static $config = array();
    
    /**
     * Starts this class as a plugin
     *
     * @param array $config
     */
    public static function start(&$config)
    {
        $config = array_merge(array(

            /* @var string */
            'namespace_separator' => '\\'
            
        ), $config);

        Astaroth::set('app/executor', 'Astaroth\RestExecutor::execute');
    }

    /**
     * Executor which uses classes to define actions
     *
     * Searches for a file called after the action (with the php extension) inside
     * directories set under astaroth/dirs/actions
     *
     * Each action file must have a class named after the action in camel case
     * and suffixed with "Action". If the action is in a sub directory, the class
     * name should follow the PEAR naming concention (ie. slashes => underscores).
     *
     * The class should have methods for each of the http method it wants to support.
     * The method should be lower cased. (eg: the GET method should be get() )
     *
     * The view variables are fetched from the return value of the method if its an
     * array and using the class instance properties.
     *
     * @param string $action
     * @param string $method
     * @param $vars
     * @param array $context
     * @return array
     * @throws AstarothException
     */
    public static function execute($action, $method, $vars, $context)
    {
        $className = str_replace(' ', self::$config['namespace_separator'], ucwords(str_replace('/', ' ', trim($action))));
        $filename = str_replace(self::$config['namespace_separator'], DIRECTORY_SEPARATOR, $className) . '.php';
        $className .= 'Action';

        Astaroth::fireEvent('RestExecutor::Execute', array(&$className, &$filename, &$context));

        if (!($include = Astaroth::actionFilename($filename, null, true))) {
            return false;
        }

        list($filename, $ns) = $include;
        $className = trim("$ns\\$className", '\\');
        /** @noinspection PhpIncludeInspection */
        include($filename);
        
        if (!class_exists($className)) {
            throw new AstarothException("Class '$className' not found in '$filename'");
        }
        
        $instance = new $className($vars);
        $vars = array();
        
        if (method_exists($instance, 'execute')) {
            if (!is_array($vars = $instance->execute(Astaroth::get('request')))) {
                $vars = array();
            }
        }
        
        if (method_exists($instance, $method)) {
            if (is_array($return = call_user_func(array($instance, $method), Astaroth::get('request')))) {
                $vars = array_merge($vars, $return);
            }
        }
        
        $vars = array_merge(get_object_vars($instance), $vars);
        return $vars;
    }
}
