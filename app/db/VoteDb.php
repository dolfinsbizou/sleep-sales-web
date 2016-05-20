<?php
/**
 * Created by PhpStorm.
 * User: Clement
 * Date: 20/05/2016
 * Time: 23:29
 */
namespace App\Db;
use Astaroth\DbClass;
class VoteDb extends DbClass
{
    protected function base()
    {
        $this->setTableName('vote');
        $this->add('ID Users', 'int', 0);
        $this->add('ID Soiree', 'int', 0);
        $this->add('ID_Proposition', 'int', 0);
	
        $this->setPrimaryKey('ID Users','ID Soiree');
    }
}