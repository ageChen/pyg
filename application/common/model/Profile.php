<?php

namespace app\common\model;

use think\Model;

class Profile extends Model
{
    //建建档案和管理员的相对模型，一个档案属于一个管理员
    public function admin()
    {
        //绑定管理员表的用户名和邮箱
        return $this->belongsTo('Admin','uid','id')->bind('username,email');
    }
}
