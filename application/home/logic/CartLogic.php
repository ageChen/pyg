<?php


namespace app\home\logic;

use app\common\model\Cart;
use think\Collection;

class CartLogic
{
    /**
     * 购物车添加商品信息
     * @param $goods_id
     * @param $spec_goods_id
     * @param $number
     * @param int $is_selected
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function addCart($goods_id, $spec_goods_id, $number, $is_selected = 1)
    {
        //判断登录状态
        if (session('?user_info')) {
            //用户已登录，添加到数据表
                //取出用户id
            $user_id = session('user_info.id');
            //查询用户的购物记录
                //拼接查询条件
            $where = [
              'user_id' => $user_id,
              'goods_id' => $goods_id,
              'spec_goods_id' => $spec_goods_id
            ];
            $info = Cart::where($where)->find();
            if ($info) {
                //存在相同的购物记录，只进行数量和选中状态更新
                $info->number += $number;
                $info->is_selected = $is_selected;
                $info->save();
            } else {
                //没有相同的购物记录，进行数据添加
                $where['number'] = $number;
                $where['is_selected'] = $is_selected;
                Cart::create($where,true);
            }
        } else {
            //用户未登录，添加到cookie中
                //取出cookie中的数据
            $data = cookie('cart') ?: [];
            //以购买商品规格值id组合作为数组下标
            $key = $goods_id . '_' . $spec_goods_id;
            if (isset($data[$key])) {
                //有相同的购买记录，进行数量累加
                $data[$key]['number'] += $number;
                $data[$key]['is_selected'] = $is_selected;
            } else {
                //没有相同的购买记录，添加数据
                $data[$key] = [
                  'id' => $key,
                  'user_id' => '',
                  'goods_id' => $goods_id,
                  'spec_goods_id' => $spec_goods_id,
                  'number' => $number,
                  'is_selected' => $is_selected
                ];
            }
            //重新添加商品信息到cookie，有效期是7天
            cookie('cart',$data,60*60*24*7);
        }
    }

    /**
     * 获取所有购物信息
     */
    public static function getAllCart()
    {
        //判断登录状态
        if (session('?user_info')) {
            //已登录，取数据库信息
            $user_id = session('user_info.id');
            $data = Cart::field('id,user_id,goods_id,spec_goods_id,number,is_selected')
                ->where('user_id',$user_id)
                ->select();
            //将数据转化为标准的二维数组
            $data = (new Collection($data))->toArray();
        } else {
            //未登录，取cookie中的信息
            $data = cookie('cart') ?: [];
            //转化为标准的二维数组  取出数组中的值，将字符串下标重新设置为0，1……
            $data = array_values($data);
        }
        return $data;
    }

    /**
     * 登录后将cookie数据迁移到数据库
     */
    public static function cookieToDb()
    {
        //从cookie中取出购物车数据
        $data = cookie('cart') ?: [];
        //遍历调用方法添加数据到数据库
        foreach ($data as $v) {
            self::addCart($v['goods_id'],$v['spec_goods_id'],$v['number']);
        }
        //删除cookie中的购物车数据
        cookie('cart',null);
    }

    /**
     * 修改购物车商品数量
     * @param $id
     * @param $number
     */
    public static function changeNum($id,$number)
    {
        //判断登录状态
        if (session('?user_info')) {
            //已登录，以用户id未条件，修改到数据库
                //修改时通过用户id确保只修改当前用户的数据
            $user_id = session('user_info.id');
            Cart::update(['number'=>$number],['id'=>$id,'user_id'=>$user_id]);
        } else {
            //未登录，以商品规格id字符串为数组下标为条件修改到cookie中
                //取出cookie中的数据
            $data = cookie('cart') ?: [];
                //修改商品数量
            $data[$id]['number'] = $number;
                //重新保存到cookie
            cookie('cart',$data,60*60*24*7);
        }
    }

    /**
     * 删除购物车商品记录
     * @param $id
     */
    public static function delCart($id)
    {
        //判断登录状态
        if (session('?user_info')) {
            //已登录，删除数据库中的数据
            $user_id = session('user_info.id');
            Cart::destroy(['id'=>$id, 'user_id'=>$user_id]);
        } else {
            //未登录，删除cookie中的数据
            $data = cookie('cart') ?: [];
            unset($data[$id]);
            cookie('cart',$data,60*60*24*7);
        }
    }

    /**
     * 修改商品选中状态
     * @param $id
     * @param $status
     */
    public static function changeStatus($id, $status)
    {
        //判断登录状态
        if (session('?user_info')) {
            //已登录，修改到数据库
            $user_id = session('user_info.id');
            $where['user_id'] = $user_id;
            //判断是否勾了全选
            if ($id !== 'all') {
                //修改全部
                $where['id'] = $id;
            }
            //只修改一个
            Cart::where($where)->Update(['is_selected'=>$status]);
        } else {
            //未登录，修改到cookie
            $data = cookie('cart') ?: [];
            //判断是否勾了全选
            if ($id == 'all') {
                //修改全部
                foreach ($data as &$v) {
                    $v['is_selected'] = $status;
                }
            } else {
                //修改一个
                $data[$id]['is_selected'] = $status;
            }
            cookie('cart',$data,60*60*24*7);
        }
    }
}