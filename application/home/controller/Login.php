<?php

namespace app\home\controller;

use app\common\model\User;
use app\home\logic\CartLogic;
use think\Controller;

class Login extends Controller
{
    /**
     * 登录界面展示
     */
    public function login()
    {
        //临时关闭模板布局
        $this->view->engine->layout(false);
        return view();
    }

    /**
     *登录功能
     */
    public function dologin()
    {
        //接收参数
        $params = input();
        //参数检测
        $validate = $this->validate($params,[
           'username|用户名'  =>  'require',
           'password|密码'    =>  'require|length:6,16'
        ]);
        if ($validate !== true) {
            $this->error($validate);
        }
        //加密密码
        $password = encrypt_password($params['password']);
        //查询数据
            //使用闭包函数拼接where条件
        $info = User::where(function ($query) use ($params) {
            $query->where('phone',$params['username'])->whereOr('email',$params['username']);
        })->where('password',$password)->find();
        //返回数据
        if ($info) {
            //设置用户登录标识
            session('user_info',$info->toArray());
            //迁移cookie购物车数据到数据库
            CartLogic::cookieToDb();
            //如果session中有地址，往session中的地址跳转，否则跳到商城首页
            $back_url = session('back_url') ?: 'home/index/index';
            $this->redirect($back_url);
        } else {
            $this->error('用户名或密码错误');
        }
    }

    /**
     * qq登录回调处理
     */
    public function qqcallback()
    {
        require_once("./plugins/qq/API/qqConnectAPI.php");
        $qc = new \QC();
        $access_tokne =  $qc->qq_callback();
        $openid = $qc->get_openid();
        //重新实例化QC对象，将授权码和标识号作为参数传入
        $qc = new \QC($access_tokne,$openid);
        //调用get_user_info接口获取用户信息
        $info = $qc->get_user_info();
//        dump($info);
        //查询用户第三方登录情况
        $user = User::where('open_type','qq')->where('openid',$openid)->find();
        if ($user) {
            //非第一次登录，同步用户昵称
            $user->nickname = $info['nickname'];
            $user->save();
        } else {
            //第一次登录，添加登录信息到数据库
            User::create(['open_type'=>'qq','openid'=>$openid,'nickname'=>$info['nickname']]);
        }
        //设置登录标识
        $user = User::where('open_type','qq')->where('openid',$openid)->find();
        session('user_info',$user->toArray());
        //迁移cookie购物车数据到数据库
        CartLogic::cookieToDb();
        //页面跳转
            //如果session中有地址，往session中的地址跳转，否则跳到商城首页
        $back_url = session('back_url') ?: 'home/index/index';
        $this->redirect($back_url);
    }

    /**
     * 注册界面展示
     */
    public function register()
    {
        //临时关闭模板布局
        $this->view->engine->layout(false);
        return view();
    }

    /**
     * 注册功能
     */
    public function phone()
    {
        //接收参数
        $params = input();
        //参数检测
        $validate = $this->validate($params,[
           'phone|手机号'   =>  'require|regex:1[3-9]\d{9}|unique:user,phone',
           'code|验证码'    =>  'require|length:4',
           'password|密码'  =>  'require|length:6-14|confirm:repassword'
        ]);
        if ($validate !== true) {
            $this->error($validate);
        }
        //注册逻辑
            //从缓存取出验证码进行校验
        $code = cache('register_code'.$params['phone']);
        if ($code != $params['code']) {
            $this->error('验证码错误');
        }
        //验证成功后删除验证码
        cache('register_code'.$params['phone'],null);
            //密码加密
        $params['password'] = encrypt_password($params['password']);
            //手机号作为用户名
        $params['username'] = $params['phone'];
            //手机号作为昵称，隐藏中间四位数
        $params['nickname'] = encrypt_phone($params['phone']);
            //添加数据到数据库
        User::create($params,true);
        //页面跳转
        $this->success('注册成功，正在跳转登录界面','home/login/login');
    }

    /**
     * 发送验证码
     */
    public function sendcode()
    {
        //接收参数
        $params = input();
        //参数检测
        $validate = $this->validate($params,[
            'phone' => 'require|regex:1[3-9]\d{9}'
        ]);
        if ($validate !== true) {
            $res = [
              'code' => 400,
              'msg' => '手机号有误'
            ];
            echo json_encode($res);
            die();
        }
        //验证码一分钟只能发一次
        $last_time = cache('register_time'.$params['phone']);
        if (time() - $last_time < 60) {
            $res = [
              'code' => 500,
              'msg'  => '一分钟只能发送一次'
            ];
            echo json_encode($res);
            die();
        }
        //生成验证码
        $code = mt_rand(1000,9999); //生成随机的四位数
        //生成短信内容
        $content = "【创信】你的验证码是：{$code}，3分钟内有效！";
        //调用函数发送短信
//        $result = sendmsg($params['phone'],$content);
        //测试用，默认发送成功
        $result = true;
        //返回结果
        if ($result === true) {
            //发送成功，将手机号对应的验证码放到缓存里
            cache('register_code'.$params['phone'],$code,180);  //验证码有效期3分钟
            cache('register_time'.$params['phone'],time(),180); //将发送时间存入缓存
            $res = [
              'code' => 200,
              'msg'  => '发送成功',
              'data' => $code
            ];
            echo json_encode($res);
            die();
        } else {
            $res = [
              'code' => 401,
              'msg' => $result
            ];
            echo json_encode($res);
        }
    }

    /**
     * 用户退出功能
     */
    public function logout()
    {
        //清除session
        session(null);
        //跳转登录界面
        $this->redirect('home/login/login');
    }
}

