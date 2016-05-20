<?php
/**
 * Created by PhpStorm.
 * User: Clement
 * Date: 20/05/2016
 * Time: 23:29
 */
namespace App\Db;
use Astaroth\DbClass;
class PropositionDb extends DbClass
{
    protected function base()
    {
        $this->setTableName('proposition');
        $this->add('ID', 'int', 0);
        $this->add('Date', 'datetime', null);
        $this->add('Descriptif', 'string', null);
		$this->add('NbVote', 'int', 0);
	
        $this->setPrimaryKey('ID');
    }
}