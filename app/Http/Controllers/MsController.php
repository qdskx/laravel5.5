<?php
namespace App\Http\Controllers;
use App\Http\Model\Goods;

class MsController{

    /*
     * 开始秒杀
     */
    public function startms($id = 901){
        $goods_store = $this->getStore($id);
        var_dump($goods_store);
    }

    public function getStore($id){
        return Goods::select('number')->where('goods_id' , $id)->get();
    }

}