<?php
/**
 * Created by PhpStorm.
 * User: Emmanuel
 * Date: 20/05/2016
 * Time: 23:29
 */

namespace App\Db;

use Astaroth\DbClass;

class UserDb extends DbClass
{

    protected function base()
    {
        $this->setTableName('members');
        $this->add('id', 'int', 0);
        $this->add('username', 'string', null);
        $this->add('password', 'string', null);
        $this->setPrimaryKey('id');
    }
}