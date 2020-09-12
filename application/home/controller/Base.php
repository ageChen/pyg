<?php

namespace app\home\controller;

use app\common\model\Category;
use think\Collection;
use think\Controller;
use think\Request;

class Base extends Controller
{
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        //查询分类信息
        $category = Category::select();
        //转化为标准二维数组
        $category = (new Collection($category))->toArray();
        //转化为父子级树状结构
        $category = get_tree_list($category);
//        dump($category);die();
        //变量赋值
        $this->assign('category',$category);
    }
}
