<?php
namespace app\http\controllers;
use Illuminate\Http\Request;

class UserController{

    public function login(){
        $data['title'] = '登录页面';
        return view('login' , $data);
    }

    public function dologin(Request $request){
        $uid = intval(trim($request->input('uid')));
        session(['uid' => $uid]);
        return view('detail' , ['title' => '秒杀页面']);
    }

    public function loginout(Request $request){
        $request->session()->forget('uid');
        return view('detail');
    }


}