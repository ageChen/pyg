<?php

namespace app\common\model;

use think\Model;

class Category extends Model
{
    //绑定品牌和种类的模型，一个品牌有多个分类
    public function brands()
    {
        return $this->hasMany('Brand','cate_id','id');
    }
}
