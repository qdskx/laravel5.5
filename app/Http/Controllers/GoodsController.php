<?php
namespace App\Http\Controllers;

class GoodsController{

    public function detail(){

        var_dump(session('uid'));
        return view('detail' , ['title' => '秒杀页面']);

    }


}