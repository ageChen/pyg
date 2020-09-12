<?php


namespace app\home\logic;

use think\Collection;

class OrderLogic
{
    /**
     * 获取购物车记录及商品信息，商品总数量，商品总价
     * @return array
     */
    public static function getCartDataWithGoods()
    {
        //获取用户id
        $user_id = session('user_info.id');
        //查询选中的购物车记录信息和商品SKU信息
        $cart_data = \app\common\model\Cart::with('goods,spec_goods')
            ->where('is_selected',1)->where('user_id',$user_id)->select();
        //转化为标准的二维数组
        $cart_data = (new Collection($cart_data))->toArray();
        //循环遍历处理购物车记录信息
        //初始化总金额和总数量
        $total_price = 0;
        $total_number = 0;
        foreach ($cart_data as &$v) {
            //使用sku价格覆盖购物车数据价格
            if (isset($v['price']) && $v['price'] > 0) {
                $v['goods_price'] = $v['price'];
            }
            if (isset($v['cost_price']) && $v['cost_price'] > 0) {
                $v['cost_price'] = $v['cost_price2'];
            }
            //库存处理
            if (isset($v['store_count']) && $v['store_count'] > 0) {
                $v['goods_number'] = $v['store_count'];
            }
            if (isset($v['frozen_count']) && $v['frozen_count'] > 0) {
                $v['frozen_number'] = $v['frozen_count'];
            }
            //总价格和总数量的累加
            $total_number += $v['number'];
            $total_price += $v['goods_price'] * $v['number'];
        }
        //删除多余的$v
        unset($v);
        //返回数据
        return [
            'cart_data' => $cart_data,
            'total_number' => $total_number,
            'total_price' => $total_price
        ];
    }
}