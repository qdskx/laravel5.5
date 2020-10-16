<?php

namespace App\Http\Controllers\Common;

class Redis
{

    public static $redisObj = '';

    /**
     * 链接redis库
     * @param str $connect 链接哪个库
     * @param bool $public redis对象创建后是否共享 true:是 false:否
     */
    public static function redisConnect($connect,$public=false){
        if($public && self::$redisObj) return self::$redisObj;
        $connect = $connect ? $connect : 'default';
        $redis = app('redis')->connection($connect);
        if($public) self::$redisObj = $redis;
        return $redis ;
    }

    /**
     * 永久或某段时间内计数
     * @param str $connect 链接哪个库
     * @param str $key redis计数的key
     * @param int $expire 过期时间，单位秒，-1：为永久
     * @param array $params 其它元素
     *              expireReset:是否重设时间, true:是 false:否
     * @return int 返回计数数量
     */
    public static function setRedisMinuteNum($connect,$key,$expire=60,$params=[]){
        $ret = 0 ;
        if(!$key){
            return $ret;
        }
        $expireReset = $params['expireReset'] ?? false ;
        $redis = self::redisConnect($connect);
        $num = $redis->incr($key);
        if($num == 1 || $redis->ttl($key)>$expire || $expireReset){
            if($expire == -1){      //永久
                $redis->persist($key);
            }else{
                $redis->expire($key,$expire);
            }
        }
        $ret = $num;
        return $ret;
    }

    /**
     * 永久或某段时间内计数并且根据此键对应出值
     * @param $connect $key $expire $params 同setRedisMinuteNum函数
     * @param array $dataNow 要赋值的新数据，必须是数组
     * @param array $paramsMy 此函数的其它值
     *              resetVaule:是否重新赋值 true:是 false:否，默认（$dataNow数据加入到已存的里面）
     *              noSetValue:不用管值 true:不用将此次的值赋值 false:赋值（默认）
     *
     * @return int 返回计数数量
     */
    public static function setRedisMinuteNumVaule($connect,$key,$dataNow,$expire=60,$params=[],$paramsMy=[]){
        if(!$dataNow || !is_array($dataNow)){
            return false;
        }
        $noSetValue = $params['noSetValue'] ?? false ;
        $resetVaule = $params['resetVaule'] ?? false;
        $num = self::setRedisMinuteNum($connect, $key, $expire, $params);
        $keyValue = $key."__value__";
        $data = [];
        if($num == 1){
            self::deleteKey($connect, $keyValue);       //删除重新赋值
            $dataAll = [$dataNow];
        }else if($num>0){
            //获取数据
            $dataAll = self::getRedisSetValue($connect, $keyValue, true);
            if(!$noSetValue){       //需要赋值
                $dataAll[] = $dataNow;
            }
            if($resetVaule){
                $dataAll = $dataNow;
            }
        }
        self::setRedisSetValue($connect, $keyValue, $dataAll, $expire+2);
        $data = $dataAll ;
        $ret = ['num'=>$num,'data'=>$data];
        return $ret;
    }

    /**
     * 设置set值永久有效或某段时间内有效
     * @param str $connect 链接哪个库
     * @param str $key redis的key
     * @param str $val 要存的值，为空则不用修改之前的值，不为空则覆盖原来的值
     * @param int $expire 过期时间，单位秒，-1：为永久
     * @param array $params 其它元素
     *              expireReset:是否重设时间, true:是 false:否（默认）
     */
    public static function setRedisSetValue($connect,$key,$val,$expire=60,$params=[]){
        $ret = false;
        if(!$key){
            return $ret;
        }
        $expireOri = $expire ;
        $val = is_array($val) ? json_encode($val) : $val;
        $expireReset = $params['expireReset'] ?? false ;
        $redis = self::redisConnect($connect);
        $timeRemain = $redis->ttl($key);
        $setExpire = true ;        //是否要设置时间 true:是 false:否
        if($timeRemain == -2){      //不存在，添加
            $redis->set($key,$val);
        }else if($timeRemain == -1){        //永久存在，修改时间
            $val!=='' && $redis->set($key,$val);
        }else if($timeRemain>-1 && $timeRemain<=$expire){       //在过期时间内
            $val!=='' && $redis->mset([$key=>$val]);
//            $setExpire = false ;
            $expire = $timeRemain ;
        }else if($timeRemain>$expire){       //剩余时间和要求的过期时间不符，重新设置时间
            $val!=='' && $redis->set($key,$val);
        }else{
            $setExpire = false ;
        }
        if($expireReset && $expire) $expire = $expireOri;
        if($expireReset || $setExpire){       //设置时间
            if($expire == -1){      //永久
                $redis->persist($key);
            }else{
                $redis->expire($key,$expire);
            }
        }
        return true;
    }

    /**
     * 获取set值
     * @param str $connect 链接哪个库
     * @param str $key redis的key
     */
    public static function getRedisSetValue($connect,$key,$jsonDecode=false){
        $ret = false;
        if(!$key){
            return $ret;
        }
        $redis = self::redisConnect($connect);
        $data = $redis->get($key);
        if($jsonDecode && $data){
            $data = json_decode($data,true);
        }
        return $data;
    }

    /**
     * 获取某个键的剩余时间
     */
    public static function getKeyTtl($connect,$key){
        $ret = false;
        if(!$key){
            return -2;
        }
        $redis = self::redisConnect($connect);
        $ttl = $redis->ttl($key);
        return $ttl;
    }

    /**
     * 删除redis某个key
     */
    public static function deleteKey($connect,$key){
        $redis = self::redisConnect($connect);
        $ret = $redis->del($key);
        return $ret;
    }

    /**
     * 读取set的值，如果不存在是否循环执行
     * 注：此函数不支持结果为 false 值的
     * @param int $loopNum 如果redis没值，查询次数
     * @param int $sleep 每次等待时间
     * @return  false:没有值 其它：取出来的值
     */
    public static function getRedisSetValueLoop($connect,$key,$loopNum=6,$sleep=1){
        $sleep = $sleep>0 ? intval($sleep) : 1 ;
        $loopNum = $loopNum>0 ? intval($loopNum) : 1 ;
        for($i=1;$i<=$loopNum;$i++){
            $data = self::getRedisSetValue($connect,$key);
            if($data !== null && $data !== false){
                return $data ;
            }
            sleep($sleep);
        }
        return false;
    }

    /**
     * 设置redis缓存锁
     * @return bool true:设置成功 false:设置失败，已经存在
     */
    public static function setRedisLock($connect,$key,$expire=60,$value=1){
        $ret = true ;
        $value = $value ? $value : 1 ;
        $expire = $expire ? $expire : 60 ;
        $redis = self::redisConnect($connect);
        $ret = $redis->set($key,$value,'nx','ex',$expire);
        return $ret ;
    }

    /**
     * 检测某个key是否存在
     */
    public static function checkKeyExists($connect, $key){
        $redis = self::redisConnect($connect);
        return $redis->exists($key);
    }

    /**
     * 检测某个key是在hash中是否存在
     */
    public static function checkHashKeyExists($connect,$key,$keySet=''){
        $ret = false;
        $redis = self::redisConnect($connect);
        if(!$redis->exists($key)) return $ret;
        if(!$redis->hexists($key,$keySet)) return $ret;
        return true ;
    }

    /**
     * 检测某个value在hash中是否存在
     * @param str $valType hash值存的原形式 array:数组 string:字符串
     */
    public static function checkHashValExists($connect,$key,$keySet,$val,$valType='array'){
        $ret = false;
        $checkExists = self::checkHashKeyExists($connect, $key, $keySet) ;
        if(!$checkExists) return $ret;
        $redis = self::redisConnect($connect);
        $data = $redis->hget($key, $keySet);
        if($valType == 'string'){
            if($data === $val) $ret = true ;
        }else if($valType == 'array'){
            $dataArr = $data ? json_decode($data, true) : [];
            !is_array($dataArr) && $dataArr = [] ;
            if(in_array($val,$dataArr)) $ret = true ;
        }
        return $ret ;
    }

    /**
     * 设置hash值
     * @param str $key redis的key
     * @param str $valKey 某个元素键，只是修改时间时 可为空
     * @param str $val 要存的值，为空则不用修改之前的值，不为空则覆盖原来的值
     * @param int $expire 过期时间，单位秒，-1：为永久
     * @param array $params 其它元素
     *              expireReset:是否重设时间, true:是 false:否（默认）
     */
    public static function setHashValue($connect,$key,$valKey,$val,$expire=60,$params=[]){
        $ret = false;
        if(!$key || !$valKey){
            return $ret;
        }
        $expireOri = $expire ;
        $val = is_array($val) ? json_encode($val) : $val;
        $expireReset = $params['expireReset'] ?? false ;
        $redis = self::redisConnect($connect);
        $timeRemain = $redis->ttl($key);
        $setExpire = true ;        //是否要设置时间 true:是 false:否
        if($timeRemain == -2){      //不存在，添加
            $redis->hset($key,$valKey,$val);
        }else if($timeRemain == -1){        //永久存在，修改时间
            ($valKey && $val!=='') && $redis->hset($key,$valKey,$val);
        }else if($timeRemain>-1 && $timeRemain<=$expire){       //在过期时间内
            ($valKey && $val!=='') && $redis->hset($key,$valKey,$val);
//            $setExpire = false ;
            $expire = $timeRemain ;
        }else if($timeRemain>$expire){       //剩余时间和要求的过期时间不符，重新设置时间
            ($valKey && $val!=='') && $redis->hset($key,$valKey,$val);
        }else{
            $setExpire = false ;
        }
        if($expireReset && $expire) $expire = $expireOri;
        if($expireReset || $setExpire){       //设置时间
            if($expire == -1){      //永久
                $redis->persist($key);
            }else{
                $redis->expire($key,$expire);
            }
        }
        return true;
    }

    /**
     * 获取hash值
     */
    public static function getHashValue($connect,$key,$valKey,$jsonDecode=false){
        $ret = false;
        if(!$key || !$valKey){
            return $ret;
        }
        $redis = self::redisConnect($connect);
        $data = $redis->hget($key,$valKey);
        if($jsonDecode && $data){
            $data = json_decode($data,true);
        }
        return $data;
    }

    /**
     * 删除某个key是在hash中
     */
    public static function deleteHashKeyExists($connect,$key,$keySet){
        $ret = false;
        $redis = self::redisConnect($connect);
        if(!self::checkHashKeyExists($connect, $key, $keySet)) return true;
        $ret = $redis->hdel($key, $keySet);
        return $ret ;
    }

    /**
     * 设置有序集合值
     * @param str $key redis的key
     * @param str $sort 某个元素键，只是修改时间时 可为空   注：此函数sort不能为0
     * @param str $val 要存的值，为空则不用修改之前的值，不为空则覆盖原来的值
     * @param int $expire 过期时间，单位秒，-1：为永久
     * @param array $params 其它元素
     *              expireReset:是否重设时间, true:是 false:否（默认）
     */
    public static function setZaddValue($connect,$key,$sort,$val,$expire=60,$params=[]){
        $ret = false;
        if(!$key || !$sort || !$val){
            return $ret;
        }
        $expireOri = $expire ;
        $val = is_array($val) ? json_encode($val) : $val;
        $expireReset = $params['expireReset'] ?? false ;
        $redis = self::redisConnect($connect);
        $timeRemain = $redis->ttl($key);
        $setExpire = true ;        //是否要设置时间 true:是 false:否
        if($timeRemain == -2){      //不存在，添加
            $redis->zadd($key,$sort,$val);
        }else if($timeRemain == -1){        //永久存在，修改时间
            ($sort && $val!=='') && $redis->zadd($key,$sort,$val);
        }else if($timeRemain>-1 && $timeRemain<=$expire){       //在过期时间内
            ($sort && $val!=='') && $redis->zadd($key,$sort,$val);
            $expire = $timeRemain ;
        }else if($timeRemain>$expire){       //剩余时间和要求的过期时间不符，重新设置时间
            ($sort && $val!=='') && $redis->zadd($key,$sort,$val);
        }else{
            $setExpire = false ;
        }
        if($expireReset && $expire) $expire = $expireOri;
        if($expireReset || $setExpire){       //设置时间
            if($expire == -1){      //永久
                $redis->persist($key);
            }else{
                $redis->expire($key,$expire);
            }
        }
        return true;
    }

    /**
     * 获取有序集合的某段值--从小到大
     */
    public static function getZrangeValue($connect,$key,$startNum,$endNum,$withScores=false){
        $ret = false;
        if(!$key){
            return $ret;
        }
        $redis = self::redisConnect($connect);
        $data = $redis->zrange($key,$startNum,$endNum,['withscores'=>$withScores]);
        return $data;
    }

    /**
     * 获取有序集合的某段值--从大到小
     */
    public static function getZrevrangeValue($connect,$key,$startNum,$endNum,$withScores=false){
        $ret = false;
        if(!$key){
            return $ret;
        }
        $redis = self::redisConnect($connect);
        $data = $redis->zrevrange($key,$startNum,$endNum,['withscores'=>$withScores]);
        return $data;
    }

    /**
     * 有序集合--获取某个成员的键标/索引
     * @param array $params
     *          type minToMax:从小到大排 maxToMin:从大到小排
     *          rank 是否按照第几的名次出来 true:是 falae:否
     */
    public static function zsetGetMemberRank($connect,$key,$member,$params=[]){
        $ret = null;
        if(!$key){
            return $ret;
        }
        $type = $params['type'] ?? 'minToMax' ;
        $rank = $params['rank'] ?? false ;
        $redis = self::redisConnect($connect);
        if($type == 'maxToMin'){
            $data = $redis->zrevrank($key,$member);
        }else{
            $data = $redis->zrank($key,$member);
        }
        if($rank && $data!==null) $data++;
        return $data;
    }

    /**
     * 获取有序集合的总数
     */
    public static function zsetGetCountElement($connect,$key){
        $ret = false;
        if(!$key){
            return $ret;
        }
        $redis = self::redisConnect($connect);
        $data = $redis->zCard($key);
        return $data;
    }

    /**
     * 有序集合--添加分数
     * @param string $member 有序集合的某个成员
     * @param int/float $score 有序集合的分数
     * @param array $parmas
     *          expire:过期时间，单位秒，-1：为永久
     *
     * @return 返回的为该成员的分数
     */
    public static function zsetScoreAdd($connect,$key,$member,$score, $parmas=[]){
        $ret = false;
        if(!$key || $member===''){
            return $ret;
        }
        $expire = $parmas['expire'] ?? -1 ;
        $redis = self::redisConnect($connect);
        $data = $redis->zincrby($key,$score,$member);
        if($expire && $expire!=-1){     //设置了时间且时间不是永久
            $ttl = $redis->ttl($key);
            if($ttl == -1){       //设置的是永久
                $redis->expire($key,$expire);
            }
        }
        return $data;
    }

    /**
     * 有序集合--删除某个元素
     * @param array/string $value 要删除的成员元素，多个可用数组表示
     */
    public static function zsetRemElement($connect,$key,$value){
        $ret = false;
        if(!$key){
            return $ret;
        }
        $redis = self::redisConnect($connect);
        $data = $redis->zRem($key,$value);
        return $data;
    }

    /**
     * 有序集合--删除某范围内的成员元素(通过排名)
     * @param type 从第几个键标开始，第一个元素为0
     * @param type 从第几个键标结束，第10个元素为9
     * @return 成功返回被成功移除的成员的个数，失败则返回false
     */
    public static function zsetRemElementByRank($connect,$key,$startNum,$endNum){
        $ret = false;
        if(!$key){
            return $ret;
        }
        $redis = self::redisConnect($connect);
        $data = $redis->zRemRangeByRank($key,$startNum,$endNum);
        return $data;
    }

    /**
     * 添加队列
     * @param string push类型 right:右添加(默认，rpush) left:左添加(lpush)
     */
    public static function pushAdd($connect,$key, $value, $type='right'){
        $redis = self::redisConnect($connect);
        $value = is_array($value) ? json_encode($value) : $value ;
        if($type == 'left'){
            $redis->lpush($key,$value);
        }else{
            $redis->rpush($key,$value);
        }
        return true ;
    }

    /**
     * 读取队列--bpop模式
     * @param array $params 其它元素
     *              type:  pop类型 right:右读(brpop) left:左读(默认，blpop)
     *              public:是否读取已存在的redis对象，true:是(开启此项对循环有好处，不用多次创建对象，但是注意上下使用冲突) false:否(默认)
     *              time:没有值时默认停留的时间，有值时会马上读取 默认10秒
     *              ret_type:  jsonDecode:结果经过json_decode处理
     */
    public static function pushReadBpop($connect,$key, $params=[]){
        $ret = ['back_data'=>''];
        $type = $params['type'] ?? 'left' ;
        $retType = $params['ret_type'] ?? '';       //jsonDecode:结果经过json_decode处理
        $public = $params['public'] ?? false ;
        $time = $params['time'] ?? 10 ;
        $redis = self::redisConnect($connect,$public);
        $data = $redis->blpop($key,$time);
        $value = is_array($data) ? $data[1] : $data ;
        if($retType == 'jsonDecode'){
            $value = $value ? json_decode($value,true) : $value;
        }
        $ret['back_data'] = $value ;
        return $ret ;
    }

}
