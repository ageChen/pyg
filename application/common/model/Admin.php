<?php

namespace app\common\model;

use think\Model;

class Admin extends Model
{
    //建立管理员-档案关联模型，一个管理员对应一个档案
    public function profile()
    {
        //绑定profile模型
//        return $this->hasOne('profile','uid','id');
        //绑定profile模型的idum字段属性
        return $this->hasOne('Profile','uid','id')->bind('idnum');
    }
}
