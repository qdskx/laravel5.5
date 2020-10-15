<?php
namespace App\Http\Controllers;

class GoodsController{

    public function detail(){

        return view('detail' , ['title' => '秒杀页面']);

    }


}