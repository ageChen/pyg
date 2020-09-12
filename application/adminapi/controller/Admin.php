<?php

namespace app\adminapi\controller;

use think\Controller;
use think\Request;

class Admin extends BaseApi
{
    /**
     * 管理员列表展示
     *
     * @return \think\Response
     */
    public function index()
    {
        //接收参数
        $params = input();
        //拼接查询条件
            //初始化查询条件
        $where = [];
            //判断keyword是否为空，不为空进行条件拼接
        if (!empty($params['keyword'])) {
            $keyword = $params['keyword'];
            $where['t1.username'] = ['like',"%$keyword%"];
        }
        //查询操作
            //select t1.*,t2.role_name from pyg_admin t1 left join pyg_role t2 on t1.id = t2.id
            // where t1.username like '%a%' limit 2
        $list = \app\common\model\Admin::alias('t1')     //alias表示给表取别名
                ->join('pyg_role t2','t1.id = t2.id','left')
                ->field('t1.id,t1.username,t1.email,t1.nickname,t1.last_login_time,t1.status,t2.role_name')
                ->where($where)
                ->paginate(2);
        //返回数据
        $this->ok($list);
    }


    /**
     * 添加管理员
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        //接收参数
        $params = input();
        //参数检测
        $validate = $this->validate($params,[
           'username|用户名'   =>   'require|unique:admin',         //unique检测唯一性，跟的参数为表名
           'password|密码'     =>   'length:6-14',                  //密码长度为6-14位
            'email|邮箱'       =>    'require|email',
            'role_id|角色ID'   =>    'require|integer|gt:0'              //角色ID位非负整数
        ]);
        if ($validate !== true) {
            $this->fail($validate);
        }
        //添加数据
            //处理密码和昵称
            //密码为空，设置为默认密码123456
        if (empty($params['password'])) {
            $params['password'] = '123456';
        }
        $params['password'] = encrypt_password($params['password']);
            //昵称为空，将用户名设置为昵称
        $params['nickname'] = $params['username'];
        $role = \app\common\model\Admin::create($params,true);
            //查询添加成功的数据
        $info = \app\common\model\Admin::find($role['id']);
        //返回数据
        $this->ok($info);

    }

    /**
     * 显示管理员详情
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        //查询数据
        $info = \app\common\model\Admin::field('id,username,email,nickname,last_login_time,status,role_id')
                ->find($id);
        //返回数据
        $this->ok($info);
    }

    /**
     * 修改管理员信息
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //超级管理员不能进行修改操作
        if ($id == 1) {
            $this->fail('该用户是尊贵的超级管理员，无法修改');
        }
        //接收参数
        $params = input();
        //判断是否有重置密码的操作
            //重置密码
        if (!empty($params['type']) && $params['type'] == 'reset_pwd') {
            //将密码设置为加密过的初始密码
            $password = encrypt_password('123456');
            //修改密码操作
            \app\common\model\Admin::update(['password'=>$password],['id'=>$id]);
        } else {
            //修改其他信息操作
                //参数检测
            $validate = $this->validate($params,[
                'email|邮箱'       =>  'email',
                'nickname|昵称'    =>  'max:50',       //最大长度为50
                'role_id|角色ID'   =>  'integer|gt:0'  //非负数整数
            ]);
            if ($validate !== true) {
                $this->fail($validate);
            }
                //修改操作
                //删除参数中不能修改的用户名和密码
            unset($params['username']);
            unset($params['password']);
            \app\common\model\Admin::update($params,['id'=>$id],true);
        }
            //查询更新的数据
        $info = \app\common\model\Admin::find($id);
        //返回数据
        $this->ok($info);
    }

    /**
     * 删除管理员
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //不能删除超级管理员
        if ($id == 1) {
            $this->fail('你不能删除高贵的超级管理员！');
        }
        //不能删除自己
        if ($id == input('id')) {
            $this->fail('你杀你自己???');
        }
        //删除操作
        \app\common\model\Admin::destroy($id);
        //返回数据
        $this->ok('删除成功');
    }
}
