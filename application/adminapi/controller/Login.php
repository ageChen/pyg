<?php

namespace app\adminapi\controller;

use app\common\model\Admin;
use think\Controller;
use tools\jwt\Token;

class Login extends BaseApi
{
    /**
     * 验证码接口
     */
    public function captcha()
    {
        //随机数生成验证码标识
        $uniqid = uniqid(mt_rand(100000,999999));
        //生成验证码图片地址，加上验证码表示
        $src = captcha_src($uniqid);
        //返回数据
        $data = [
            'src' =>  $src,
            'uniqid' => $uniqid
        ];
        //调用ok方法把数据返回到客户端
        $this->ok($data);
    }

    /**
     * 登录接口
     */
    public function login()
    {
        //获取参数
        $params = input();
        //表单参数检测
        $validata = $this->validate($params,[
            'username|用户名'   => 'require',
            'password|密码'     => 'require',
            'code|验证码'       => 'require',
            'uniqid|验证码标识'  => 'require'
        ]);
            //表单输入参数有误
        if ($validata !== true) {
            $this->fail($validata,401);
        }
        //手动校验验证码
            //取出缓存中的session_id，设置session_id，取得到session_id再设置
        $session_id = cache('session_id_'. $params['uniqid']);
        if ($session_id) {
            session_id($session_id);
        }
            //验证码错误
        if (!captcha_check($params['code'],$params['uniqid'])) {
//            $this->fail('验证码错误',402);
        }
        //查询管理员用户表中的信息
            //使用加密函数加密密码
        $password = encrypt_password($params['password']);
        $info = Admin::where('username',$params['username'])->where('password',$password)->find();
            //用户名或密码错误
        if (empty($info)) {
            $this->fail('用户名或密码错误',402);
        }
        //生成token令牌
        $token = Token::getToken($info['id']);
        //返回登录信息
        $data = [
            'token'     => $token,
            'user_id'   => $info['id'],
            'username'  => $info['username'],
            'nickname'  => $info['nickname'],
            'email'     => $info['email']
        ];
        //登录成功
        $this->ok($data);
    }

    /*
     * 退出接口
     */
    public function logout()
    {
        //获取请求信息的token
        $token = Token::getRequestToken();
        //从缓存中取到注销的token数组     如果注销数组不为空就取注销数组的内容，否则取[]
        $delete_token = cache('delete_token') ?: [];
        //将注销的token加到注销数组中
        $delete_token[] = $token;
        //将注销数组重新返回缓存
        cache('delete_token',$delete_token,3600*24);
        //退出成功
        $this->ok();
    }
}
