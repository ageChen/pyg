<?php
namespace app\adminapi\controller;

use app\common\model\Profile;
use think\Route;

class Index extends BaseApi
{
    public function index()
    {
        //测试关联模型查询
            //一对一
//        $info = \app\common\model\Admin::with('profile')->select();
//        $this->ok($info);
            //一对一相对
//        $info = Profile::with('admin')->select();
//        $this->ok($info);
            //一对多
//        $info = \app\common\model\Category::with('brands')->find(72);
//        $this->ok($info);
            //一对多相对
//        $info = \app\common\model\Brand::with('category')->find(1);
//        $this->ok($info);
        //测试加密函数
//        echo encrypt_password(123456);die();

        //测试Token类
//        $token = \tools\jwt\Token::getToken(300);
//        dump($token);
//        $id = \tools\jwt\Token::getUserId($token);
//        dump($id);die();

        //响应测试
//        $this->response();
//        $this->response(200,'success',['id'=>1,'name'=>'张三']);
//        $this->ok(['id'=>2,'name'=>'李四']);
//        $this->fail('参数错误');
//        $this->fail('参数错误',401);
//        dump(\think\Db::table('pyg_goods')->find());die;
    }
}
