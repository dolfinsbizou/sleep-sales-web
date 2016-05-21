<?php
/**
 * Created by PhpStorm.
 * User: Clement
 * Date: 20/05/2016
 * Time: 23:29
 */
namespace App\Db;
use Astaroth\DbClass;
class EtapeDb extends DbClass
{
    protected function base()
    {
        $this->setTableName('etape');
        $this->add('ID', 'int', 0);
        $this->add('HeureDeb', 'time', null);
        $this->add('HeureFin', 'time', null);
		$this->add('ID_Proposition', 'int', 0);
		$this->add('ID_Soiree', 'int', 0);
		$this->add('ID_Type', 'int', 0);
        $this->setPrimaryKey('ID');
    }
}