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

class Cookie implements iPlugin {

    const ONE_HOUR = 3600;
    const ONE_DAY = 86400;
    const ONE_MOUTH = 2592000;

    public static function start(&$config) { }

    /**
     * @param $p_name
     * @param $p_value
     * @param $p_exp
     */
    public static function set($p_name, $p_value, $p_exp) {
        setcookie($p_name, $p_value, time() + $p_exp);
    }

    /**
     * @param $p_name
     */
    public static function del($p_name) {
        if (!isset($_COOKIE[$p_name]))
            return;
        setcookie($p_name, $_COOKIE[$p_name], time()-1);
        unset($_COOKIE[$p_name]);
    }

    /**
     * @param $p_name
     * @return bool
     */
    public static function get($p_name) {
        if (!isset($_COOKIE[$p_name]))
            return false;
        return $_COOKIE[$p_name];
    }
}