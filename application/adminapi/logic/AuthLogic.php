<?php
namespace app\adminapi\logic;

use app\common\model\Admin;
use app\common\model\Auth;
use app\common\model\Role;

class AuthLogic
{
    //权限检测方法
    public static function check()
    {
        //特殊页面不需要权限检测
        $user_id = input('user_id');
            //获取控制器名称和方法名称，调用请求对象的方法
        $controller = request()->controller();
        $action = request()->action();
            //首页不需要权限检测
        if ($controller == 'Index' && $action == 'index') {
            //有权限访问
            return true;
        }
        //超级管理员不需要权限检测
            //根据用户ID查询角色ID
        $admin = Admin::find($user_id);
        $role_id = $admin['role_id'];
        if ($role_id == 1) {
            //有权限访问
            return true;
        }
        //进行权限检测
            //根据角色ID查询权限ID
        $role = Role::find($role_id);
            //将权限ID字符串转化为数组
        $role_auth_ids = explode(',',$role['role_auth_ids']);
            //根据当前访问的控制器名称和方法名称查找权限
        $auth = Auth::where('auth_c','=',$controller)->where('auth_a','=',$action)->find();
        $auth_id = $auth['id'];
            //判断权限是否在权限表里
        if (in_array($auth_id,$role_auth_ids)) {
            //有权限访问
            return true;
        }
        //没有权限访问
        return false;
    }
}