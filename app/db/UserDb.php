<?php
/**
 * Created by PhpStorm.
 * User: Clement
 * Date: 20/05/2016
 * Time: 23:29
 */
namespace App\Db;
use Astaroth\DbClass;
class UserDb extends DbClass
{
    protected function base()
    {
        $this->setTableName('users');
        $this->add('ID', 'int', 0);
        $this->add('Identifiant', 'string', null);
        $this->add('Mdp', 'string', null);
		$this->add('Admin', 'boolean', false);
        $this->setPrimaryKey('ID');
    }
}