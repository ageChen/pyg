<?php

namespace app\common\model;

use think\Model;

class Brand extends Model
{
    //一对多的相对模型，一个品牌属于一个分类
    public function category()
    {
        //绑定方式同一对一，绑定cate_name
        return $this->belongsTo('Category','cate_id','id')->bind('cate_name');
    }
}
