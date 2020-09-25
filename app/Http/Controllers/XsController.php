<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use XS;
use App\Http\Model\Goods;

class XsController{
    public function index(){
        var_dump(__METHOD__);
        $xs = new XS('liu');

        $search = $xs->search;
        $doc = $search->setQuery('万得福')->search();
        echo "<pre>";var_dump('doc' , $doc);echo "<pre>";
        $count = $search->count('万得福');
        echo "<pre>";var_dump('count' , $count);echo "<pre>";
    }

    /*
     * 向xunsearch添加数据
     */
    public function addDoc(){
//        $goods = DB::table('ecs_goods')->select('goods_id','goods_name')->limit(3)->get()->toArray();
        $goods = Goods::select('goods_id','goods_name')->limit(3)->get()->toArray();
//        var_dump($goods);
//        die;
        $xs = new XS('liu');
        $index = $xs->index;

        $index->clean();

        foreach($goods as $val){
            $add = new \XSDocument($val);
            $index->add($add);
        }
    }
}