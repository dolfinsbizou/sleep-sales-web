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

use Exception;

final class DbEndQueryException extends Exception {}

abstract class DbEndQuery {

    /**
     * @var DbAttributes
     */
    protected $attrs;

    /**
     * DbEndQuery constructor.
     * @param $p_attrs
     * @throws DbEndQueryException
     */
    public final function __construct(&$p_attrs) {
        if (!($p_attrs instanceof DbAttributes))
            throw new DbEndQueryException("p_attrs must be an instance of DbAttributes");
        $this->attrs = &$p_attrs;
    }
}