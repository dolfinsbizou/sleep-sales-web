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
use ReflectionException;
use ReflectionMethod;
use AstarothException;
use AstarothHttpException;

/**
 * @package Astaroth
 * @subpackage Controller
 */
class Controller
{
    /** @var string */
    protected $_action;
    
    /** @var array */
    protected $_params;
    
    /** @var array */
    protected $_data;
    
    /** @var string */
    protected $_httpMethod;
    
    /** @var Astaroth */
    protected $_helpers;
    
    public function __construct()
    {
        $this->_helpers = Astaroth::instance();
        $this->init();
    }
    
    /**
     * Called after __construct()
     */
    protected function init() {}
    
    /**
     * Called before an action
     */
    protected function preDispatch() {}

    /**
     * Called after an action
     * @param $result
     */
    protected function postDispatch($result) {}

    /**
     * Dispatches a request to a controller action
     *
     * @param string $action
     * @param string $httpMethod
     * @param array $vars
     * @return bool
     */
    public function _dispatch($action, $httpMethod, $vars = array())
    {
        $this->_action = $action;
        $this->_data = $_POST;
        $this->_params = array_merge(Astaroth::get('request'), $vars);
        $this->_httpMethod = $httpMethod;
        
        $this->preDispatch();
        $result = $this->_execute($action, $this->_params);
        $this->postDispatch($result);
        return $result;
    }

    /**
     * Forward the current action to another action from the same controller
     *
     * @param string $action
     * @param array $params
     * @return bool
     * @throws AstarothException
     */
    protected function _execute($action, $params = array())
    {
        $methodName = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $action))));
        $args = array();

        try {
            $method = new ReflectionMethod($this, $methodName);
            if (!$method->isPublic()) {
                return false;
            }
            
            // building method parameters using request params
            foreach ($method->getParameters() as $param) {
                if (array_key_exists($param->getName(), $params)) {
                    $args[] = $params[$param->getName()];
                } else if (!$param->isOptional()) {
                    throw new AstarothException("Missing parameter '" . $param->getName() . "' in '" .
                        get_class($this) . "::$methodName()'");
                } else {
                    $args[] = $param->getDefaultValue();
                }
            }
            
        } catch (ReflectionException $e) {
            // do not stop if __call() exist, so it allows us to trap method calls
            if (!method_exists($this, '__call')) {
                return false;
            }
        }
        
        $className = substr(get_class($this), 0, -10);
        $className = trim(substr($className, strlen(trim(Plugin::$config['namespace'], '\\'))), '\\');
        $view = trim(str_replace('\\', '/', strtolower($className)) . "/$action", '/');
        $this->_setView($view);

        return call_user_func_array(array($this, $methodName), $args);
    }
    
    // ------------------------------------------------------------------------------
    // Shortcut methods
    
    protected function _setView($view)
    {
        Astaroth::setView($view);
    }
    
    protected function _trigger404($message = 'Not found')
    {
        Astaroth::trigger404($message);
    }
    
    protected function _noRender()
    {
        Astaroth::noRender();
    }
    
    protected function _hasParam($name)
    {
        return Astaroth::has($name, $this->_params);
    }
    
    protected function _getParam($name, $default = null)
    {
        return Astaroth::get($name, $default, $this->_params);
    }
    
    protected function _setLayout($layout)
    {
        Astaroth::set('app.layout', $layout);
    }

    protected function _disableLayout()
    {
        Astaroth::disableLayout();
    }
    
    protected function _isPost()
    {
        return Astaroth::get('app.http_method') == 'POST';
    }
    
    protected function _setHeader($name, $value)
    {
        header("$name: $value");
    }

    protected function _get($key, $default = null)
    {
        return Astaroth::get($key, $default);
    }

    protected function _flash($message, $label = 'default')
    {
        if (!Astaroth::isPluginLoaded('Flash')) {
            throw new AstarothException("Controller::_flash() needs the 'Flash' plugin");
        }
        return Astaroth::flash($message, $label);
    }

    protected function _redirect($url, $useUrl = true, $code = 302)
    {
        return Astaroth::redirect($url, $useUrl, $code);
    }
}
