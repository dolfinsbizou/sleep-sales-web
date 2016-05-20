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

class Logger implements iPlugin
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
        
            /* @var bool */
            'register_default'   => false,
            
            /* @var string */
            'filename'           => 'log.txt',
            
            /* From which level to start logging messages
             * @var int */
            'level'              => LOG_WARNING,
            
            /* Message template for the default logger
             * @see logToFile()
             * @var string */
            'message_template'   => '[%date%] [%level%] %message%'
            
        ), $config);
        self::$config = &$config;

        Astaroth::registerHelper('log', 'Astaroth\Logger::log');
        if ($config['register_default']) {
            Astaroth::listenEvent('Logger::Log', 'Astaroth\Logger::logToFile');
        }
    }
    
    /**
     * Fire an Astaroth::Log event to which logger can listen
     * 
     * @param string $message
     * @param int $level
     */
    public static function log($message, $level = 3)
    {
        Astaroth::fireEvent('Logger::Log', array($message, $level));
    }
    
    /**
     * Default logger: log the message to the file defined in astaroth/files/log
     * The message template can be define in astaroth/log/message_template
     * 
     * @see Astaroth::log()
     * @param string $message
     * @param int $level
     */
    public static function logToFile($message, $level)
    {
        if ($level > self::$config['level']) {
            return;
        }
        
        $filename = self::$config['filename'];
        $template = self::$config['message_template'];
        $tags = array(
            '%date%' => @date('Y-m-d H:i:s'), 
            '%level%' => $level,
            '%message%' => $message
        );
        
        $file = fopen($filename, 'a');
        fwrite($file, str_replace(array_keys($tags), array_values($tags), $template) . "\n");
        fclose($file);
        $file = null;
    }
}
