<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use XS;
use App\Http\Model\Goods;
use App\Http\Model\Category;

class XsController{
    public function index(){
        var_dump(__METHOD__);
//        $xs = new XS('liu');
        $xs = new XS('fang');

        $search = $xs->search;
        $docs = $search->setQuery('开发')->search();
        //每个元素对象的形式
        echo "<pre>";var_dump('docs' , $docs);echo "<pre>";
        foreach($docs as $doc){
            var_dump($doc['cat_id']);
            var_dump($doc['cat_name']);
        }
        $count = $search->count('开发');
        echo "<pre>";var_dump('count' , $count);echo "<pre>";
    }

    /*
     * 向xunsearch添加数据
     */
    public function addDoc(){
//        $goods = DB::table('goods')->select('goods_id','goods_name')->limit(3)->get();
//        $goods = DB::table('goods')->select('goods_id','goods_name')->limit(3)->get()->toArray();
//        $goods = Goods::select('goods_id','goods_name')->limit(3)->get()->toArray();
        $goods = Category::select('cat_id','cat_name')->limit(10)->get()->toArray();
//        var_dump($goods);
//        die;
//        $xs = new XS('liu');
        $xs = new XS('fang');
        $index = $xs->index;

        $index->clean();

        foreach($goods as $val){
            $add = new \XSDocument($val);
            $index->add($add);
        }
    }
}