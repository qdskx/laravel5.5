<?php
namespace App\Http\Controllers;
use XS;
class XsController{
    public function index(){
        var_dump(__METHOD__);
        $xs = new XS('skx');
        $search = $xs->search;
        $count = $search->count('万得福');
        var_dump('count' , $count);
    }
}