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
use Astaroth\DebugBar\TraceableDb;
use DebugBar\DataCollector\ConfigCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\PDO\PDOCollector;
use DebugBar\DebugBarException;
use DebugBar\StandardDebugBar;
use Psr\Log\LogLevel;

class DebugBar implements iPlugin
{
    public static $config = array();

    public static $instance;

    public static $renderer;

    private static $astarothMessages;

    /**
     * Starts this class as a plugin
     *
     * @param array $config
     */
    public static function start(&$config)
    {
        self::$config = &$config;
        self::$instance = new StandardDebugBar();
        self::$renderer = self::$instance->getJavascriptRenderer();
        self::$renderer->setOptions(self::$config);

        self::$astarothMessages = new MessagesCollector('astaroth');
        self::$instance['messages']->aggregate(self::$astarothMessages);

        Astaroth::set('debugbar', self::$instance);
        Astaroth::registerHelper('renderDebugBar', array(self::$renderer, 'render'));
        Astaroth::registerHelper('renderDebugBarHead', array(self::$renderer, 'renderHead'));
    }

    public static function log($message, $level = LogLevel::DEBUG)
    {
        self::$instance['mesages']->addMessage($message, $level);
    }

    public static function onAstarothBootstrap()
    {
        self::$instance->addCollector(new ConfigCollector(Astaroth::get()));
    }

    public static function onAstarothStart(&$cancel)
    {
        if (Astaroth::isPluginLoaded('Db')) {
            Astaroth::set('db', new TraceableDb(Astaroth::get('db')));
            self::$instance->addCollector(new PDOCollector(Astaroth::get('db'), self::$instance['time']));
        }

        if (Astaroth::isPluginLoaded('Logger')) {
            Astaroth::listenEvent('Logger::log', array(self::$instance['messages'], 'addMessage'));
        }
    }

    public static function onAstarothDispatchUri(&$uri, &$request, &$cancel)
    {
        if (!isset(self::$config['base_url'])) {
            self::$renderer->setBaseUrl(Astaroth::get('astaroth.base_url') . 'vendor/maximebf/debugbar/src/DebugBar/Resources');
        }
        self::$astarothMessages->addMessage("Dispatching '$uri' to '{$request['action']}'", LogLevel::DEBUG);
    }

    public static function onAstarothExecuteBefore(&$action, &$context, &$vars)
    {
        self::$astarothMessages->addMessage("Executing action '$action'", LogLevel::DEBUG);
        self::$instance['time']->startMeasure("execute $action", "Execute '$action'");
    }

    public static function onAstarothExecuteAfter($action, &$context, &$vars)
    {
        self::$instance['time']->stopMeasure("execute $action");
    }

    public static function onAstarothRenderBefore(&$view, &$vars, &$filename)
    {
        self::$astarothMessages->addMessage("Rendering view '$view'", LogLevel::DEBUG);
        self::$instance['time']->startMeasure("render $view", "Render '$view'");
    }

    public static function onAstarothRenderAfter($view, &$output, $vars, $filename)
    {
        try {
            self::$instance['time']->stopMeasure("render $view");
        } catch (DebugBarException $e) {
            // the last layout triggers an exception because the collectors are collected 
            // while it is rendered
        }
    }

    public static function onAstarothLoadhelperAfter($helperName, $dirs)
    {
        self::$astarothMessages->addMessage("Loaded helper '$helperName'", LogLevel::DEBUG);
    }

    public static function onAstarothPluginAfter($plugin)
    {
        self::$astarothMessages->addMessage("Loaded plugin '$plugin'", LogLevel::DEBUG);
    }

    public static function onAstarothEnd($success, &$writeSession)
    {
        self::$astarothMessages->addMessage("Ending (success=$success)", LogLevel::DEBUG);
    }

    public static function onSessionStart($ns)
    {
        self::$astarothMessages->addMessage("Session started", LogLevel::DEBUG);
    }

    public static function onAstarothHttperror($e, &$cancel)
    {
        self::$instance['exceptions']->addException($e);
    }

    public static function onAstarothError($e, &$cancel)
    {
        self::$instance['exceptions']->addException($e);
    }
}
