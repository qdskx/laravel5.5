<html>
<head>
    <title>{{$title}}</title>
    <meta charset="UTF-8">
    <style>
        div{
            text-align: center;
            height: 300px;
            line-height: 300px;
        }
    </style>
</head>

<body>
<div>
    <h3>大白兔原味奶糖227g/包 原味奶糖奶香浓郁糖果休闲食品零食小吃</h3>
    <h5>20￥</h5>
</div>

<form action="startms" method="post">
    <input type="hidden" name="goods_id" value="1">
    <input type="submit" disabled value="秒杀结束">
</form>

</body>

<script>
    var showtime = function () {
        var nowtime = new Date(),  //获取当前时间
                endtime = new Date("2020/11/11");  //定义结束时间
        var lefttime = endtime.getTime() - nowtime.getTime(),  //距离结束时间的毫秒数
                leftd = Math.floor(lefttime/(1000*60*60*24)),  //计算天数
                lefth = Math.floor(lefttime/(1000*60*60)%24),  //计算小时数
                leftm = Math.floor(lefttime/(1000*60)%60),  //计算分钟数
                lefts = Math.floor(lefttime/1000%60);  //计算秒数
        return leftd + "天" + lefth + ":" + leftm + ":" + lefts;  //返回倒计时的字符串
    }

    var div = document.getElementById("showtime");
    setInterval (function () {
        div.innerHTML = showtime();
    }, 1000);  //反复执行函数本身
</script>

</html>