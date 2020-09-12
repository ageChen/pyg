<?php

namespace app\common\model;

use think\Model;

class Cart extends Model
{
    //定义购物车记录-商品信息的关联，一条购物记录属于一个商品
    public function goods()
    {
        return $this->belongsTo('Goods','goods_id','id')
            ->bind('goods_name,goods_price,cost_price,goods_number,goods_logo,frozen_number');
    }

    //定义购物车记录-商品规格SKU的关联，一条购物记录属于一个商品SKU
    public function specGoods()
    {
        return $this->belongsTo('SpecGoods','spec_goods_id','id')
            ->bind(['value_ids','value_names','price','cost_price2'=>'cost_price','store_count','store_frozen']);
    }

}
