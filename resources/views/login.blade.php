<html>
<head>
    <title>{{$title}}</title>
    <meta charset="UTF-8">
</head>

<body>
<form action="dologin" method="post">
    <input type= "hidden"  name="_token" value="{{csrf_token()}}">
    用户id: <input type="text" name="uid">
    <input type="submit" value="登录">
</form>
</body>
</html>