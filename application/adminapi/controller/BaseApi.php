<?php

namespace app\adminapi\controller;

use app\adminapi\logic\AuthLogic;
use Exception;
use think\Controller;
use tools\jwt\Token;

class BaseApi extends Controller
{
    //不需要进行登录检测的接口名称
    protected $no_login = ['login/captcha','login/login'];
    //初始化方法
    protected function _initialize()
    {
        //调用父类方法
        parent::_initialize();
        //处理跨域请求
            //允许的源域名
        header("Access-Control-Allow-Origin: *");
            //允许的请求头信息
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
            //允许的请求类型
        header('Access-Control-Allow-Methods: GET, POST, PUT,DELETE,OPTIONS,PATCH');

        //登录检测
        try {
            //获取并拼接请求的控制器和方法
                //将控制器名转化为小写
            $path = strtolower($this->request->controller()).'/'.$this->request->action();
            //请求路径不在无需登录检测数组里就需要进行登录检测
            if (!in_array($path,$this->no_login)) {
                //获取请求对象的id
//                $user_id = Token::getUserId();
                $user_id = 1;       //测试功能使用
                    //查询不到id，登录检测失败
                if (empty($user_id)) {
                    $this->fail('用户未登录或token令牌失效',403);
                }
                //登录检测成功，保存请求对象id到请求中
                $this->request->get('user_id',$user_id);
                $this->request->post('user_id',$user_id);
                //权限检测
                if (!AuthLogic::check()) {
                    $this->fail('没有访问权限',402);
                }
            }
        }catch (Exception $e) {
            //捕获到登录检测异常，抛出异常
            $this->fail('登录异常，请检查token令牌',404);
        }
    }

    /**
     * 通用响应
     * @param int $code     响应码
     * @param string $msg   响应信息
     * @param array $data   返回的数据
     */
    protected function response($code=200,$msg='success',$data=[])
    {
        $res = [
            'code' => $code,
            'msg'  => $msg,
            'data' => $data
        ];
        //原生PHP写法,第二个参数阻止中文自动转化为unicode编码
        //echo json_encode($res,JSON_UNESCAPED_UNICODE);die;

        //框架写法，数据转化为json格式后调用框架封装的响应类中的send方法将数据发送到客户端
        json($res)->send();die;
    }

    /**
     * 成功响应
     * @param array $data  返回的数据
     * @param int $code    成功响应码
     * @param string $msg  成功信息
     */
    protected function ok($data=[],$code=200,$msg='success')
    {
        $this->response($code,$msg,$data);
    }

    /**
     * 失败响应
     * @param string $msg   错误信息
     * @param string $code  错误响应码
     * @param array $data   返回的数据
     */
    protected function fail($msg,$code='500',$data=[])
    {
        $this->response($code,$msg,$data);
    }
}
