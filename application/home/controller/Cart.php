<?php

namespace app\home\controller;

use app\home\logic\CartLogic;
use think\Controller;

class Cart extends Base
{
    /**
     * 购物车添加信息
     */
    public function addcart()
    {
        //如果是get请求，直接跳回首页不触发方法
        if (request()->isGet()) {
            $this->redirect('/');
        }
        //接收参数
        $params = input();
        //参数检测
        $validate = $this->validate($params,[
            'goods_id' => 'require|integer|gt:0',
            'spec_goods_id' => 'integer|gt:0',
            'number' => 'require|integer|gt:0'
        ]);
        if ($validate !== true) {
            $this->error($validate);
        }
        //调用方法处理数据
        CartLogic::addCart($params['goods_id'],$params['spec_goods_id'],$params['number']);
        //查询商品信息
        $goods = \app\common\model\Goods::getGoodWithSpec($params['spec_goods_id'],$params['goods_id']);
        //渲染模板
        return view('addcart',['goods'=>$goods, 'number'=>$params['number']]);
    }

    /**
     * 购物车信息展示
     * @return \think\response\View
     * @throws \think\Exception
     */
    public function index()
    {
        //查询购物记录
        $list = CartLogic::getAllCart();
        //遍历购物记录查询商品信息和商品规格SKU信息
        foreach ($list as &$v) {
            $v['goods'] = \app\common\model\Goods::getGoodWithSpec($v['spec_goods_id'],$v['goods_id'])->toArray();
//            $v['goods']['total'] = $v['goods']['goods_price'] * $v['number'];
        }
        unset($v);
//        foreach ($list as $k=>$v) {
//            $list[$k]['goods'] = \app\common\model\Goods::getGoodWithSpec($v['spec_goods_id'],$v['goods_id'])->toArray();
//        }
//        dump($list);die();
        return view('index',['list'=>$list]);
    }

    /**
     * ajax修改购物车商品数量
     */
    public function changenum()
    {
        //接收参数
        $params = input();
        //参数检测
        $validate = $this->validate($params,[
            'id|用户id' => 'require',
            'number|商品数量' => 'require|integer|gt:0'
        ]);
        if ($validate !== true) {
            $res = [
                'code' => 400,
                'msg' => '参数错误'
            ];
            echo json_encode($res);
            die();
        }
        //处理数据
        CartLogic::changeNum($params['id'],$params['number']);
        //返回数据
        $res = [
            'code' => 200,
            'msg' => 'success'
        ];
        echo json_encode($res);
    }

    /**
     * ajax删除购物车信息
     */
    public function delcart()
    {
        //接收参数
        $params = input();
        //参数检测
        if (!isset($params['id']) || empty($params['id'])) {
            $res = ['code'=>400,'msg'=>'参数错误'];
            echo json_encode($res);
            die();
        }
        //数据处理
        CartLogic::delCart($params['id']);
        //返回结果
        $res = ['code'=>200,'msg'=>'success'];
        echo json_encode($res);
    }

    /**
     * ajax修改选中状态
     */
    public function changestatus()
    {
        //接收参数
        $params = input();
        //参数检测
        $validate = $this->validate($params,[
           'id' => 'require',
           'status' => 'require|in:0,1'
        ]);
        if ($validate !== true) {
            $res = ['code'=>400,'msg'=>$validate];
            echo json_encode($res);
            die();
        }
        //数据处理
        CartLogic::changeStatus($params['id'],$params['status']);
        //返回结果
        $res = ['code'=>200, 'msg'=>'success'];
        echo json_encode($res);
    }
}
