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

use PDOStatement;

final class DbPDOStatement extends PDOStatement {
    public $pdo;

    protected function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * @param null $bound_input_params
     * @return bool
     */
    public function execute($bound_input_params = null) {
        $execute = parent::execute($bound_input_params);
        echo "Input Parameters :<br>";
        var_dump($bound_input_params);
        return $execute;
    }
}