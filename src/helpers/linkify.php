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

class LinkifyHelper
{
    public function linkify($string)
    {
        $string = str_replace('-', ' ', $string);
        $string = preg_replace(array('/\s+/', '/[^A-Za-z0-9\-]/'), array('-', ''), $string);
        $string = trim(strtolower($string));
        return $string;
    }
}
