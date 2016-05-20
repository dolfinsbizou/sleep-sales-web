<?php
/**
 * Created by PhpStorm.
 * User: Clement
 * Date: 20/05/2016
 * Time: 23:29
 */
namespace App\Db;
use Astaroth\DbClass;
class LieuDb extends DbClass
{
    protected function base()
    {
        $this->setTableName('lieu');
        $this->add('ID', 'int', 0);
        $this->add('amnety', 'string', null);
        $this->add('name', 'string', null);
		$this->add('website', 'string', null);
		$this->add('addr:street', 'string', null);
		$this->add('addr:housenumber', 'int', null);
		$this->add('addr:postcode', 'int', null);
		$this->add('ouverture', 'time', null);
		$this->add('fermeture', 'time', null);
		$this->add('cuisine', 'string', null);
        $this->setPrimaryKey('ID');
    }
}