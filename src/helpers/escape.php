<?php
/*
 * This file is part of the Astaroth package.
 *
 * (c) 2016 Victorien POTTIAU ~ Emmanuel LEROUX
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Astaroth\Helpers;

use Astaroth;
use AstarothException;

class EscapeHelper
{
    /**
     * Escapes text so it can be outputted safely
     *
     * Uses escape profiles defined in the escaping configuration key
     *
     * @param string $text The text to escape
     * @param array $profile
     * @return string The escaped string
     * @throws AstarothException
     * @internal param mixed $functions A profile name, a function name, or an array of function
     */
    public function escape($text, $profile = array('htmlspecialchars', 'nl2br'))
    {
        if (!is_array($profile)) {
            if (($functions = Astaroth::get("helpers.escape.$profile", false)) === false) {
                if (function_exists($profile)) {
                    $functions = array($profile);
                } else {
                    throw new AstarothException("No profile or functions named '$profile' in escape()");
                }
            }
        } else {
            $functions = $profile;
        }
        
        foreach ((array) $functions as $function) {
            $text = call_user_func($function, $text);
        }
        return $text;
    }
}

