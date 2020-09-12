<?php

namespace app\adminapi\controller;

use think\Collection;
use think\Controller;
use think\Image;
use think\Request;

class Category extends BaseApi
{
    /**
     * 显示商品列表
     *
     * @return \think\Response
     */
    public function index()
    {
        //接收pid参数，影响查询的结果，按pid进行查找
        $params = input();
        //初始化where条件
        $where = [];
        if (isset($params['pid'])) {
            $where['pid'] = $params['pid'];
        }
        //查询数据
        $list = \app\common\model\Category::where($where)->select();
            //转化为标准的二维数组
        $list = (new Collection($list))->toArray();
        //接收type参数，不为list则以无限级结构返回数据
        if (!isset($params['type']) || $params['type'] != 'list') {
            //转化为无限级结构
            $list = get_cate_list($list);
        }
        //返回数据
        $this->ok($list);
    }


    /**
     * 保存新建的资源
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
           'cate_name|分类名'     =>      'require|length:2,20',
            'pid|父级ID'          =>      'require|integer|egt:0',
            'is_show|是否显示'     =>      'require|in:0,1',
            'is_hot|是否热门'      =>       'require|in:0,1',
            'sort|排序'           =>       'require|integer|between:0,999'
        ]);
        if ($validate !== true) {
            $this->fail($validate);
        }
        //处理pid_path,pid_path_name,level
        if ($params['pid'] == 0) {
            //顶级分类
            $params['pid_path'] = 0;
            $params['pid_path_name'] = '';
            $params['level'] = 0;
        } else {
            //不是顶级分类
                //查询父级的pid_path和pid_path_name
            $p_info = \app\common\model\Category::where('pid','=',$params['pid'])->find();
                //没找到父级信息
            if (empty($p_info)) {
                $this->fail('数据异常',404);
            }
            //拼接pid_path和pid_path_name
            $params['pid_path'] = $p_info['pid_path']. '_'. $p_info['id'];
            $params['pid_path_name'] = $p_info['pid_path_name']. '_'. $p_info['cate_name'];
            $params['level'] = $p_info['level'] + 1;
        }
        //logo图片处理
        $params['image_url'] = $params['logo'] ?? '';
        if (isset($params['image_url']) && !empty($params['image_url']) && is_file('.'.$params['image_url'])) {
            Image::open('./'.$params['image_url'])->thumb(120,50)->save('.'.$params['image_url']);
        }
        //添加数据
        $cate = \app\common\model\Category::create($params,true);
        $info = \app\common\model\Category::find($cate['id']);
        //返回数据
        $this->ok($info);
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        //查询数据
        $info = \app\common\model\Category::find($id);
        //返回数据
        $this->ok($info);
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * 保存更新的资源
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
            'cate_name|分类名'     =>      'require|length:2,20',
            'pid|父级ID'          =>      'require|integer|egt:0',
            'is_show|是否显示'     =>      'require|in:0,1',
            'is_hot|是否热门'      =>       'require|in:0,1',
            'sort|排序'           =>       'require|integer|between:0,999'
        ]);
        if ($validate !== true) {
            $this->fail($validate);
        }
        //修改数据
        //处理pid_path,pid_path_name,level
        if ($params['pid'] == 0) {
            //顶级分类
            $params['pid_path'] = 0;
            $params['pid_path_name'] = '';
            $params['level'] = 0;
        } else {
            //不是顶级分类
            //查询父级的pid_path和pid_path_name
            $p_info = \app\common\model\Category::where('pid','=',$params['pid'])->find();
            //没找到父级信息
            if (empty($p_info)) {
                $this->fail('数据异常',404);
            }
            //拼接pid_path和pid_path_name
            $params['pid_path'] = $p_info['pid_path']. '_'. $p_info['id'];
            $params['pid_path_name'] = $p_info['pid_path_name']. '_'. $p_info['cate_name'];
            $params['level'] = $p_info['level'] + 1;
        }
        //处理图片logo
        if (isset($params['logo']) && !empty($params['logo'])) {
            $params['image_url'] = $params['logo'];
        }
        \app\common\model\Category::update($params,['id'=>$id],true);
        $info = \app\common\model\Category::find($id);
        $this->ok($info);
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //查询子分类，存在则不能删除
        $total = \app\common\model\Category::where('pid','=',$id)->count();
        if ($total > 0) {
            $this->fail('该权限有子分类权限，无法删除',403);
        }
        \app\common\model\Category::destroy($id);
        $this->ok('删除成功');
    }

}
