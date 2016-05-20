<?php
/**
 * Created by PhpStorm.
 * User: Clement
 * Date: 20/05/2016
 * Time: 23:29
 */
namespace App\Db;
use Astaroth\DbClass;
class SoireeDb extends DbClass
{
    protected function base()
    {
        $this->setTableName('soiree');
        $this->add('ID', 'int', 0);
        $this->add('Nom', 'string', null);

        $this->setPrimaryKey('ID');
    }
}