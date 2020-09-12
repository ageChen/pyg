<?php

namespace app\adminapi\controller;

use think\Request;

class Auth extends BaseApi
{
    /**
     * 权限列表展示
     *
     * @return \think\Response
     */
    public function index()
    {
        //接收参数
        $params = input();
//        dump($params);die;
            //初始化where查询条件
        $where = [];
            //参数带keyword，进行一级权限的模糊查询
        if (!empty($params['keyword'])) {
            $where['auth_name'] = ['like',"%{$params['keyword']}%"];
        }
        //查询数据
            //普通列表展示
        $list = \app\common\model\Auth::field('id,auth_name,pid,pid_path,auth_c,auth_a,is_nav,level')
            ->where($where)->select();
                //列表数据转化为二维数组
        $list = (new Collection($list))->toArray();
//        dump($list);die();
            //参数带type，进行权限分级查询
        if (!empty($params['type']) && $params['type'] == 'tree') {
            //type为tree，树状结构列表
            $list = get_tree_list($list);
        } else {
            //type不为tree，无限级结构列表
            $list = get_cate_list($list);
        }
        //返回数据
        $this->ok($list);
    }

    /**
     * 添加权限
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        //接收参数
        $params = input();

        if (empty($params['pid'])) {
            $params['pid'] = 0;
        }
        if (empty($params['is_nav'])) {
            $params['is_nav'] = $params['radio'];
        }
        //参数检测
        $validata = $this->validate($params,[
            'auth_name|权限名称'     =>  'require',
            'pid|父级权限'           =>   'require',
            'is_nav|菜单权限'        =>   'require'
        ]);
        if ($validata !== true) {
            //参数检测有误
            $this->fail($validata,401);
        }
        //添加权限
            //顶级权限的处理
        if ($params['pid'] == 0) {
            //设置家族图谱，级别，控制器和控制方法
            $params['pid_path'] =  0;
            $params['level']    =  0;
            $params['auth_c']   = '';
            $params['auth_a']   = '';
        } else {
            //非顶级权限的处理
            //查询父级权限
            $p_info = \app\common\model\Auth::find($params['pid']);
            //查询不到父级权限
            if (empty($p_info)) {
                $this->fail('数据异常',404);
            }
            //设置家族图谱，级别
            $params['pid_path']   =  $p_info['pid_path'].'_'.$params['pid'];
            $params['level']      =  $p_info['level'] + 1;
        }
        //执行添加语句,过滤非数据表字段
        $auth = \app\common\model\Auth::create($params,true);
//        dump($auth);die;
            //查询添加的数据
        $info = \app\common\model\Auth::find($auth['id']);
        //返回添加的数据
        $this->ok($info);
    }

    /**
     * 权限详情展示
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        //查询数据
        $auth = \app\common\model\Auth::field('id,auth_name,pid,pid_path,auth_c,auth_a,is_nav,level')
            ->find($id);
        //返回数据
        $this->ok($auth);
    }

    /**
     * 修改权限
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //接收参数
        $params = input();
        //修改前先查询出数据
        $auth = \app\common\model\Auth::find($id);
        if (empty($auth)) {
            $this->fail('数据异常',404);
        }
        if (empty($params['pid'])) {
            $params['pid'] = 0;
        }
        if (empty($params['is_nav'])) {
            $params['is_nav'] = $params['radio'];
        }
        //参数检测
        $validata = $this->validate($params,[
            'auth_name|权限名称'     =>  'require',
            'pid|父级权限'           =>   'require',
            'is_nav|菜单权限'        =>   'require'
        ]);
        if ($validata !== true) {
            //参数检测有误
            $this->fail($validata,401);
        }
        //修改权限
            //修改为顶级权限
        if ($params['pid'] == 0) {
            //设置级别和家族图谱为0
            $params['level'] = 0;
            $params['pid_path'] = 0;
        } elseif ($params['pid'] != $auth['pid']) {
            //修改其父级权限
                //先查出父级权限
            $p_auth = \app\common\model\Auth::find($params['pid']);
            if (empty($p_auth)) {
                $this->fail('数据异常',404);
            }
                //重新设置级别和家族图谱
            $params['level']    = $p_auth['level'] + 1;
            $params['pid_path'] = $p_auth['pid_path'] . '_' . $params['pid'];
        //执行修改
        \app\common\model\Auth::update($params,['id'=>$id],true);
        //返回数据
            //查询更新后的数据并返回
            $info = \app\common\model\Auth::find($id);
            $this->ok($info);
        }
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //判断是否有子权限，存在子权限无法删除
            //通过pid能找到父级权限，说明有子权限，禁止删除
        $auth = \app\common\model\Auth::where('pid',$id)->count();
        if ($auth > 0) {
            $this->fail('亲，有子权限是无法直接删除的噢',401);
        }
        //执行删除
        \app\common\model\Auth::destroy($id);
        //返回数据
        $this->ok('删除成功');
    }

    /**
     * 角色权限展示
     */
    public function nav()
    {
        //获取登录的管理员用户id
        $user_id = input('user_id');
        //根据管理员用户id查询角色id
        $info = \app\common\model\Admin::find($user_id);
//        dump($info);die();
        $role_id = $info['role_id'];
        //判断角色是否是超级管理员
        if ($role_id == 1) {
            //超级管理员展示所有能展示到菜单的权限
            $data = \app\common\model\Auth::where('is_nav','=','1')->select();
        } else {
            //非超级管理员展示对应角色的权限
                //根据角色id查询角色中对应的角色权限id
            $role_info = \app\common\model\Role::find($role_id);
            $role_auth_ids = $role_info['role_auth_ids'];
                //根据角色权限id展示能展示到菜单的权限
            $data = \app\common\model\Auth::where('is_nav','=',1)
                ->where('id','in',$role_auth_ids)
                ->select();
        }
        //将权限数据转化为标准的二维数组
        $data = (new \think\Collection($data))->toArray();
        //再将权限数据转化为树状结构
        $data = get_tree_list($data);
        //返回权限数据
        $this->ok($data);
    }
}
