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

class ImgHelper
{
    public function img($src, $alt = '', $attrs = array())
    {
        $attrs['src'] = Astaroth::asset($src);
        $attrs['alt'] = $alt;
        return sprintf('<img %s />', Astaroth::htmlAttributes($attrs));
    }
}
