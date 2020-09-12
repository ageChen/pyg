<?php

namespace app\adminapi\controller;

use app\common\model\Admin;
use think\Collection;
use think\Request;

class Role extends BaseApi
{
    /**
     * 显示角色权限
     *
     * @return \think\Response
     */
    public function index()
    {
        //查询角色表，不包含超级管理员
        $list = \app\common\model\Role::field('id,role_name,desc,role_auth_ids')
        ->where('id','<>','1')->select();
        //遍历循环角色权限
        foreach ($list as $k=>$v) {
            //查询权限表里角色对应的权限
            $auth = \app\common\model\Auth::where('id','in',$v['role_auth_ids'])->select();
            //转化为标准的二维数组
            $auth = (new Collection($auth))->toArray();
            //转化为父子集树状结构
            $auth = get_tree_list($auth);
            //将权限添加到role_auths字段，通过数组下标$k添加
            $list[$k]['role_auths'] = $auth;
        }
            //将用不到的$v销毁
        unset($v);
        //返回数据
        $this->ok($list);
    }

    /**
     * 添加角色
     *
     * @return \think\Response
     */
    public function save()
    {
        //接收参数
        $params = input();
        //参数检测
        $validate = $this->validate($params,[
            'role_name|角色名称' => 'require',
            'auth_ids|权限ID'   => 'require'
        ]);
        if ($validate !== true) {
            $this->fail($validate);
        }
        //添加数据
            //将auth_ids转化为role_auth_ids
        $params['role_auth_ids'] = $params['auth_ids'];
            //清除用不到的auth_ids
        unset($params['auth_ids']);
//        dump($params);die;
        $role = \app\common\model\Role::create($params,true);
        //查询添加成功的数据
        $info = \app\common\model\Role::find($role['id']);
        //返回数据
        $this->ok($info);
    }

    /**
     * 显示角色详情
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        //查询角色权限列表
        $info = \app\common\model\Role::find($id);
        //返回数据
        $this->ok($info);
    }

    /**
     * 更新角色列表
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //接收参数
        $params = input();
        //参数检测
        $validate = $this->validate($params,[
            'role_name|角色名称'   =>   'require',
            'auth_ids|权限ID'     =>    'require'
        ]);
        if ($validate !== true) {
            $this->fail($validate);
        }
        //更新数据
            //将auth_ids转化为role_auth_ids
        $params['role_auth_ids'] = $params['auth_ids'];
            //销毁用不到的auth_ids
        unset($params['auth_ids']);
        \app\common\model\Role::update($params,['id'=>$id],true);
            //查询更新了的数据
        $info = \app\common\model\Role::field('id,role_name,desc,role_auth_ids')->find($id);
        //返回数据
        $this->ok($info);
    }

    /**
     * 删除角色
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //角色为超级管理员，无法删除
        if ($id == 1) {
            $this->fail('角色为高贵的超级管理员，无法删除',404);
        }
        //根据ID查询管理员表
        $total = Admin::where('role_id','=',$id)->count();
            //角色是管理员的，无法删除
        if ($total > 0) {
            $this->fail('角色正在使用，无法删除',403);
        }
        //删除操作
        \app\common\model\Role::destroy($id);
        //返回数据
        $this->ok('删除成功');
    }
}
