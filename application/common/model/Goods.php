<?php

namespace app\common\model;

use think\Model;

class Goods extends Model
{
    //定义商品-分类的关联 一个商品属于一个分类 绑定分类名
    public function category()
    {
        return $this->belongsTo('Category','cate_id','id')->bind('cate_name');
    }
    public function categoryRow()
    {
        return $this->belongsTo('Category','cate_id','id');
    }
    //定义商品-品牌的关联 一个商品属于一个品牌 绑定品牌名，给数据表的名称起别名
    public function brand()
    {
        return $this->belongsTo('Brand','brand_id','id')->bind(['brand_name'=>'name']);
    }
    public function brandRow()
    {
        return $this->belongsTo('Brand','brand_id','id');
    }
    //定义商品-类型的关联 一个商品属于一个类型 绑定类型名
    public function type()
    {
        return $this->belongsTo('Type','type_id','id')->bind('type_name');
    }
    //定义商品-相册图片的关联 一个商品有多个相册图片
    public function GoodsImages()
    {
        return $this->hasMany('GoodsImages','goods_id','id');
    }
    //定义商品-规格的关联 一个商品有多个规格值
    public function SpecGoods()
    {
        return $this->hasMany('SpecGoods','goods_id','id');
    }

    //封装商品和商品规格SKU的连表查询
    public static function getGoodWithSpec($spec_goods_id,$goods_id)
    {
        //有sku的id，作为查询条件
        if ($spec_goods_id) {
            $where = ['t2.id'=>$spec_goods_id];
        } else {
            //没有sku的id，以goods_id作为查询条件
            $where = ['t1.id'=>$goods_id];
        }
        //连表查询
        /*select t1.*,t2.value_ids,t2.value_names,t2.price,t2.cost_price as cost_price2,t2.store_count from pyg_goods t1
        left join pyg_spec_goods t2 on t1.id = t2.goods_id where t2.id = 927*/
        $goods = self::alias('t1')
            ->join('pyg_spec_goods t2','t1.id = t2.goods_id','left')
            ->field('t1.*,t2.value_ids,t2.value_names,t2.price,t2.cost_price as cost_price2,t2.store_count')
            ->where($where)
            ->find();
        if ($goods['price'] > 0) {
            $goods['goods_price'] = $goods['price'];
        }
        if ($goods['cost_price2'] > 0) {
            $goods['cost_price'] = $goods['cost_price2'];
        }
        return $goods;
    }
}
