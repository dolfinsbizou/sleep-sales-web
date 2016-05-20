<?php
use App\Db\SoireeDb;

/**
 * Created by PhpStorm.
 * User: Emmanuel
 * Date: 20/05/2016
 * Time: 23:38
 */

class PageController extends \AppController{

    public function index(){
        $soireeDb = new SoireeDb();
        debug($soireeDb->LGet()[0]->Nom);
        return ['soiree' => $soireeDb->LGet()];
    }

    public function soiree($id){
        
    }

    public function nouveaucompte(){
        
    }
    
    public function connection(){
        
    }
    
    public function deconnection(){
        
    }
    
    public function e404(){
    
    }
    
}