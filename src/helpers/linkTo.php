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

class LinkToHelper
{
    public function linkTo($text, $url, $params = array(), $attrs = array()) 
    {
        $attrs['href'] = Astaroth::url($url, $params);
        return sprintf('<a %s>%s</a>',
            Astaroth::htmlAttributes($attrs), $text);
    }
}
