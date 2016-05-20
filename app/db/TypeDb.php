<?php
/**
 * Created by PhpStorm.
 * User: Clement
 * Date: 20/05/2016
 * Time: 23:29
 */
namespace App\Db;
use Astaroth\DbClass;
class TypeDb extends DbClass
{
    protected function base()
    {
        $this->setTableName('type');
        $this->add('ID', 'int', 0);
        $this->add('typeEtape', 'string', null);
		
        $this->setPrimaryKey('ID');
    }
}