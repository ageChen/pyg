<?php

namespace app\adminapi\controller;

use app\common\model\Goods;
use think\Controller;
use think\Image;
use think\Request;

class Brand extends BaseApi
{
    /**
     * 显示商品品牌列表
     *
     * @return \think\Response
     */
    public function index()
    {
        //接收参数
        $params = input();
        //初始化where条件
        $where = [];
        if (isset($params['cate_id']) && !empty($params['cate_id'])) {
            //获取分类下的品牌，无需分页
            $where['cate_id'] = $params['cate_id'];
            $list = \app\common\model\Brand::where($where)->field('id,name')->select();
        } else {
            if (isset($params['keyword']) && !empty($params['keyword'])) {
                //获取所有品牌列表，需要搜索+分页
                $keyword = $params['keyword'];
                $where['t1.name'] = ['like',"%$keyword%"];
            }
            //select t1.*,t2.cate_name from pyg_brand t1 left join pyg_category t2 on t1.cate_id = t2.id
            $list = \app\common\model\Brand::alias('t1')
                ->join(config('database.prefix').'category t2','t1.cate_id = t2.id','left')
                ->field('t1.id,t1.name,t1.logo,t1.desc,t1.sort,t1.is_hot,t2.cate_name')
                ->where($where)
                ->paginate(10);
        }
        $this->ok($list);
    }

    /**
     * 添加品牌信息
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
           'name|品牌名称'  =>  'require',
            'cate_id|所属分类ID'  =>  'require|integer|gt:0',
            'is_hot|是否热门'     =>  'require|in:0,1',
            'sort|排序'          =>   'require|between:0,999',
        ]);
        if ($validate !== true) {
            $this->fail($validate);
        }
        //生成缩略图
        if (isset($params['logo']) && !empty($params['logo']) && is_file('.'.$params['logo'])) {
            Image::open('.'.$params['logo'])->thumb(150,50)->save('.'.$params['logo']);
        }
        //添加数据
        $brand = \app\common\model\Brand::create($params,true);
        $info = \app\common\model\Brand::find($brand['id']);
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
        $info = \app\common\model\Brand::field('id,name,logo,desc,sort,is_hot,cate_id,url')
        ->find($id);
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
            'name|品牌名称'  =>  'require',
            'cate_id|所属分类ID'  =>  'require|integer|gt:0',
            'is_hot|是否热门'     =>  'require|in:0,1',
            'sort|排序'          =>   'require|between:0,999',
        ]);
        if ($validate !== true) {
            $this->fail($validate);
        }
        //生成缩略图
        if (isset($params['logo']) && !empty($params['logo']) && is_file('.'.$params['logo'])) {
            Image::open('.'.$params['logo'])->thumb(150,50)->save('.'.$params['logo']);
        }
        //修改数据
        \app\common\model\Brand::update($params,['id'=>$id],true);
        $info = \app\common\model\Brand::find($id);
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
        //品牌下有商品则无法删除
        $total = Goods::where('id',$id)->count();
        if ($total > 0) {
            $this->fail('品牌下有商品存在，无法删除',403);
        }
        \app\common\model\Brand::destroy($id);
        $this->fail('删除成功');
    }
}
