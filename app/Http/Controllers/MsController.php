<?php
namespace App\Http\Controllers;
use App\Http\Model\Goods;
use App\Http\Model\Order;
use App\Http\Controllers\Common\Redis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class MsController{

    public $goods_store;
    public $succ_user = 'succ_user';
    public $fail_user = 'fail_user';
    public $page_view = 'page_view';


    /*
     * 开始秒杀
     */
    public function startms(Request $request){

        //        记录页面访问量
        Redis::incr($this->page_view);
        $uid = session('uid');
        $goods_id = intval(trim($request->input('goods_id')));
        if(!$this->checkGoodsId($goods_id)){
            $this->writeLog($goods_id . '_不存在');
            return view('end');
        }
        //        下单
        return $this->payment($uid , $goods_id);

    }

    /*
     * 核验库存
     */
    public function checkStore($goods_id){

        if(!empty(Redis::checkKeyExists('' , $this->goods_store))){
            $goods_store = Redis::getRedisSetValue('' , $this->goods_store);
            if(empty($goods_store))return view('end');
        }else{
            $goods_number = Goods::select('goods_number')->where('goods_id' , $goods_id)->get()->toArray();
            if(!empty($goods_number) && empty($goods_number[0]['goods_number']))return view('end');
            $goods_store = $goods_number[0]['goods_number'];
            Redis::setRedisSetValue('' , $this->goods_store , $goods_store , -1);
        }

    }

    /*
     * 商品是否存在
     * param $goods_id
     * return boolean
     */
    public function checkGoodsId($goods_id){
        if(isset($goods_id) && !empty($goods_id)){
            $goods_id_arr = Goods::select('goods_id')->where('goods_id' , $goods_id)->get()->toArray();
            if(!empty($goods_id_arr) && isset($goods_id_arr[0]['goods_id'])){
                return true;
            }
        }
        return false;
    }

    /*
     * 写入日志
     * param $content
     */
    public function writeLog($content){
        $path = __DIR__."/log/";
        if (!is_dir($path)){
            mkdir($path,0777);  // 创建文件夹,并给777的权限（所有权限）
        }
        $file = $path.date("Ymd").".log";    // 写入的文件
        file_put_contents($file,date('Ymd H:i:s').'==>'.$content.PHP_EOL,FILE_APPEND);
    }

    /*
     * 下单
     * param $uid
     * param $goods_id
     */
    public function payment($uid , $goods_id){

        //            防止重复抢购
        $succ_user = Redis::lrange($this->succ_user , 0 , -1);
        if(!empty($succ_user)){
            if(in_array($uid , $succ_user))return view('msed', ['title' => '消息提示']);
        }

        //        核验库存
        $this->checkStore($goods_id);

        Redis::watch($this->goods_store);
        DB::beginTransaction();
        try{

            Goods::where('goods_id' , $goods_id)->update('goods_number = goods_number- 1');

            $data['order_sn'] = $this->build_order_no();
            $data['uid']      = $uid;
            $data['goods_id'] = $goods_id;
            $data['addtime']  = time();

            Order::insertGetId($data);
            DB::commit();
            //            redis减库存
            Redis::decr($this->goods_store);
            //            存储已抢购成功的用户
            Redis::pushAdd('' , $this->succ_user , $uid);
            return view('pay');

        }catch (\Exception $e) {
            $this->writeLog($e->getMessage());
            DB::rollBack();
            Redis::pushAdd('' , $this->fail_user , $uid);
            return view('end');
        }


    }

    /*
     * 生成订单号
     */
    public function build_order_no(){
        return date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
    }

}