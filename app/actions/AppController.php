<?php

/**
 * Created by PhpStorm.
 * User: Emmanuel
 * Date: 10/05/2016
 * Time: 15:10
 */

use App\Db\LanguageDb;
use App\Db\StringKeyDb;
use App\Db\StringValueDb;
use App\Modules\Actions\Module;
use Astaroth\Controller\Controller;
use Astaroth\DbWhereOperator;

class AppController extends Controller
{

    public $headerForLayout;
    public $menuForLayout;
    public $footerForLayout;
    
    protected function initLayout(){

    }

}