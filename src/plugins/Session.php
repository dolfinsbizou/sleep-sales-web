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

class Session implements iPlugin
{
    /** @var array */
    public static $config = [];
    
    /**
     * Starts this class as a plugin
     *
     * @param array $config
     */
    public static function start(&$config) {
        $config = array_merge(array(
        
            /* @var bool */
            'autostart' => true,

            /* @var string */
            'namespace' => null
            
        ), $config);
        self::$config = &$config;
    }

    public static function onAstarothStart() {
        if (self::$config['autostart']) {
            if (($ns = self::$config['namespace']) !== null) {
                if (!isset($_SESSION[$ns])) {
                    $_SESSION[$ns] = [];
                }
                Astaroth::$store['session'] = &$_SESSION[$ns];
            } else {
                Astaroth::$store['session'] = &$_SESSION;
            }
            Astaroth::fireEvent('Session::Start', [$ns]);
        }
    }
}
