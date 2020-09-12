<?php

namespace app\home\controller;

use app\common\model\Category;
use app\common\model\SpecValue;
use app\home\logic\GoodsLogic;
use think\Controller;

class Goods extends Base
{
    /*
     * 显示分类列表数据
     */
    public function index($id=0)
    {
        //接收参数
        $keywords = input('keywords');
        if(empty($keywords)){
            //获取指定分类下商品列表
            if(!preg_match('/^\d+$/', $id)){
                $this->error('参数错误');
            }
            //查询分类下的商品
            $list = \app\common\model\Goods::where('cate_id', $id)->order('id desc')->paginate(10);
            //查询分类名称
            $category_info = \app\common\model\Category::find($id);
            $cate_name = $category_info['cate_name'];
        }else{
            try{
                //从ES中搜索
                $list = \app\home\logic\GoodsLogic::search();
                $cate_name = $keywords;
            }catch (\Exception $e){
//                $this->error('服务器异常');
                dump($e->getMessage());
            }
        }
//        dump($list);die();
        return view('index', ['list'=>$list, 'cate_name' => $cate_name]);
    }

    /**
     * 商品商品界面
     */
    public function detail($id)
    {
        //查询商品信息、相册信息、规格SKU信息
        $goods = \app\common\model\Goods::with('goods_images,spec_goods')->find($id);
        //将商品第一个规格信息替换到$goods中
        if (!empty($goods['spec_goods'])) {
            if ($goods['spec_goods'][0]['price'] > 0) {
                $goods['goods_price'] = $goods['spec_goods'][0]['price'];
            }
            if ($goods['spec_goods'][0]['cost_price'] > 0) {
                $goods['cost_price'] = $goods['spec_goods'][0]['cost_price'];
            }
            if ($goods['spec_goods'][0]['store_count'] > 0) {
                $goods['store_count'] = $goods['spec_goods'][0]['store_count'];
            } else {
                $goods['store_count'] = 0;
            }
        }
        //转化goods_attr中的json字符串为数据
        $goods['goods_attr'] = json_decode($goods['goods_attr'],true);
//        dump($goods);die();
        //查询商品的规格值数组
            //取出所有相关规格值的id
            //$value_ids [28,29,32,33]
                /*$goods['spec_goods'] = [
                 ['id'=>1, 'value_ids'=>'28_32'],
                 ['id'=>2, 'value_ids'=>'28_33'],
                 ['id'=>3, 'value_ids'=>'29_32'],
                 ['id'=>4, 'value_ids'=>'29_33'],
             ];*/
        $spec_ids = array_unique(explode('_',implode('_',array_column($goods['spec_goods'],'value_ids'))));
            //根据规格值查找spec_vales数据信息
//        dump($spec_ids);die();
        $spec_values = SpecValue::with('spec')->where('id','in',$spec_ids)->select();
//        dump($spec_values);die();
            //进行数组格式转化
                //转化前
                    /*$spec_values = [
                        ['id' => 28, 'spec_id'=>23, 'spec_value'=>'白色', 'type_id'=>21, 'spec_name'=>'颜色'],
                        ['id' => 29, 'spec_id'=>23, 'spec_value'=>'黑色', 'type_id'=>21, 'spec_name'=>'颜色'],
                        ['id' => 32, 'spec_id'=>24, 'spec_value'=>'64G', 'type_id'=>21, 'spec_name'=>'内存'],
                        ['id' => 33, 'spec_id'=>24, 'spec_value'=>'128', 'type_id'=>21, 'spec_name'=>'内存'],
                    ];*/
                //转化后
                    /*$res = [
                        23 => [ 'spec_id'=>23, 'spec_name'=>'颜色', 'spec_values'=>[]],
                        24 => [ 'spec_id'=>24, 'spec_name'=>'内存', 'spec_values'=>[]],
                     ];*/
        $res = [];
        foreach ($spec_values as $v) {
            $res[$v['spec_id']] = [
                'spec_id' => $v['spec_id'],
                'spec_name' => $v['spec_name'],
                'spec_values' => []
            ];
        }
                //再次转化后
                    /*$spec_values = [
                          23 => [ 'spec_id'=>23, 'spec_name'=>'颜色', 'spec_values'=>[
                              ['id' => 28, 'spec_id'=>23, 'spec_value'=>'白色', 'type_id'=>21, 'spec_name'=>'颜色'],
                              ['id' => 29, 'spec_id'=>23, 'spec_value'=>'黑色', 'type_id'=>21, 'spec_name'=>'颜色'],
                          ]],
                          24 => [ 'spec_id'=>24, 'spec_name'=>'内存', 'spec_values'=>[
                              ['id' => 32, 'spec_id'=>24, 'spec_value'=>'64G', 'type_id'=>21, 'spec_name'=>'内存'],
                              ['id' => 33, 'spec_id'=>24, 'spec_value'=>'128', 'type_id'=>21, 'spec_name'=>'内存'],
                          ]],
                      ];*/
        foreach ($spec_values as $v) {
            $res[$v['spec_id']]['spec_values'][] = $v;
        }
//        dump($res);die();
        //获取规格值ids和规格商品的SKU的映射关系
            //转化数组结构
            /*$goods['spec_goods'] = [                          $value_ids_map = [
            ['id'=>1, 'value_ids'=>'28_32'],                        '28_32' => ['id'=>1, 'price'=>'1000'],
            ['id'=>2, 'value_ids'=>'28_33'],     => => =>           '28_33' => ['id'=>2, 'price'=>'1000'],
            ['id'=>3, 'value_ids'=>'29_32'],                        '29_32' => ['id'=>3, 'price'=>'1000'],
            ['id'=>4, 'value_ids'=>'29_33'],                        '29_33' => ['id'=>4, 'price'=>'2000']
        ];                                                       ]; */
            //初始化映射关系数组
        $value_ids_map = [];
        foreach ($goods['spec_goods'] as $v) {
            $row = [
              'id' => $v['id'],
              'price' => $v['price']
            ];
            $value_ids_map[$v['value_ids']] = $row;
        }
            //将数组转化为json格式数据，方便在js中使用
        $value_ids_map = json_encode($value_ids_map);
        //渲染模板
        return view('detail',['goods'=>$goods, 'specs'=>$res, 'value_ids_map'=>$value_ids_map]);
    }
}
