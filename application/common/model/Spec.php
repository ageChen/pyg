<?php

namespace app\common\model;

use think\Model;

class Spec extends Model
{
    //定义规格和规格值的关联，一个规格下有多个规格值
    public function specValues()
    {
        return $this->hasMany('SpecValue','spec_id','id');
    }
}
