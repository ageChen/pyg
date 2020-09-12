<?php

namespace app\adminapi\controller;

use app\common\model\GoodsImages;
use app\common\model\SpecGoods;
use think\Controller;
use think\Db;
use think\Image;
use think\Request;

class Goods extends BaseApi
{
    /**
     * 显示商品列表
     *
     * @return \think\Response
     */
    public function index()
    {
        //接收参数
        $params = input();
        //初始化where条件
        $where = [];
        //有搜索的情况
        if (isset($params['keyword']) && !empty($params['keyword'])) {
            $keyword = $params['keyword'];
            $where['goods_name'] = ['like',"%$keyword%"];
        }
        //分页展示列表
        $list = \app\common\model\Goods::with('type,category,brand')
            ->order('id desc')      //按ID降序排列
            ->where($where)
            ->paginate(10);
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
        //接收数据
        $params = input();
        //参数检测
        $validate = $this->validate($params,[
            'goods_name|商品名称'           =>  'require|max:20',
            'goods_remark|商品简介'         =>  'require',
            'cate_id|商品分类ID'            =>  'require',
            'brand_id|商品品牌类ID'          => 'require',
            'goods_price|商品价格'          =>  'require|float|egt:0',
            'market_price|市场价'          =>  'require|float|egt:0',
            'cost_price|成本价'            =>  'require|float|egt:0',
            'goods_logo|商品logo'          =>  'require',
            'is_hot|是否热卖'               =>  'in:0,1',
            'is_on_sale|是否上架'           =>  'in:0,1',
            'is_free_shipping|是否包邮'     =>  'in:0,1',
            'is_recommend|是否推荐'         =>  'in:0,1',
            'is_new|是否新品'               =>  'in:0,1',
            'goods_images|相册图片'         =>  'array',
            'type_id|商品模型Id'            =>  'require|egt:0',
            'item|商品规格值'               =>  'require|array',
            'attr|商品属性值'               =>  'require|array'
        ],[
            'goods_price.float'         =>  '商品价格必须为非负整数或小数',
            'market_price.float'        =>  '市场价必须为非负整数或小数',
            'cost_price.float'          =>  '成本价必须为非负整数或小数',
        ]);
        if ($validate !== true) {
            $this->fail($validate);
        }
        //添加数据：
            //开启事务
        Db::startTrans();
        try {
            //商品logo处理
            if (is_file('.'.$params['logo'])) {
                //生成缩略图
                //拼接缩略图路径   dirname 获取路径的目录    basename 获取路径的文件名
                $goods_logo = dirname($params['logo']).DS.'thumb_'.basename($params['logo']);
                Image::open('.'.$params['logo'])->thumb(210,240)->save('.'.$goods_logo);
            }
            //处理商品属性值字段 JSON字段
            //JSON_UNESCAPED_UNICODE常量用于防止转化过程将汉字自动转化为unicode编码
            $params['goods_attr'] = json_encode($params['attr'],JSON_UNESCAPED_UNICODE);
            //添加操作
            $goods = \app\common\model\Goods::create($params,true);
            //批量添加商品相册图片
            //初始化数组存放图片
            $goods_images = [];
            //生成两张缩略图
            foreach ($params['goods_images'] as $image) {
                $img_big = dirname($image).DS.'thumb_big_'.basename($image);
                $img_small = dirname($image).DS.'thumb_small_'.basename($image);
                $img_obj = Image::open('.'.$image);     //图片只打开一次，先生成800大图再缩放成400小图
                $pic_big = $img_obj->thumb(800,800)->save('.'.$img_big);   //800*800缩略大图
                $pic_small = $img_obj->thumb(400,400)->save('.'.$img_small); //400*400缩略小图
                $row = [
                    'goods_id'    =>  $goods['id'],
                    '$pics_big'   =>  $pic_big,
                    '$pics_small' =>  $pic_small
                ];
                $goods_images[] = $row;
            }
            //批量添加操作
            $goods_images_model = new GoodsImages();
            $goods_images_model->saveAll($goods_images);
            //添加规格商品SKU
            //初始化数组存放商品规格
            $spec_goods = [];
            foreach ($params['item'] as $v) {
                $v['good_id'] = $goods['id'];
                $spec_goods[] = $v;
            }
            //批量添加操作
            $spec_goods_model = new SpecGoods();
            $spec_goods_model->allowField(true)->saveAll($spec_goods);
            //提交事务
            Db::commit();
            //查询添加的数据
            $info = \app\common\model\Goods::with('category,brand,type')->find($goods['id']);
            //返回数据
            $this->ok($info);
        }catch (\Exception $e) {
            //添加失败，回滚事务
            Db::rollback();
            $msg = $e->getMessage();
            $this->fail($msg);
        }
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        //关联模型查询商品所属分类，品牌，相册图片，规格信息
        $info = \app\common\model\Goods::with('category_row,brand_row,goods_images,spec_goods')
        ->find($id);
        //转化数据中的字段名
        $info['category'] = $info['category_row'];
        unset($info['category_row']);
        $info['brand'] = $info['brand_row'];
        unset($info['brand_row']);
        //关联模型查询商品类型信息
        $type = \app\common\model\Type::with('specs,specs.spec_values,attrs')->find($id);
//        $this->ok($type);
        //将商品类型整合到商品信息中
        $info['type'] = $type;
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
        //查询商品信息
            //商品分类、分类下的品牌、品牌、相册图片、规格
        $goods = \app\common\model\Goods::with('category_row,category_row.brands,brand_row,goods_images,spec_goods')
        ->find($id);
        $goods['category'] = $goods['category_row'];
        $goods['brand'] = $goods['brand_row'];
        unset($goods['category_row']);
        unset($goods['brand_row']);
            //商品类型、类型下的规格值、属性
        $goods['type'] = \app\common\model\Type::with('specs,specs.spec_values,attrs')->find($goods['type_id']);
        //查询分类信息
            //查询一级分类 pid=0
        $cate_one = \app\common\model\Category::where('pid','=',0)->field('id,cate_name')->select();
            //将分类下的pid_path分割为数组，获取二级分类和三级分类
        $id_path = explode('_',$goods['category']['pid_path']);
//        dump($id_path);die();
            //二级分类：$id['path'][1]
        $cate_two = \app\common\model\Category::where('pid','=',$id_path[1])->field('id,cate_name')->select();
            //三级分类：$id['path'][2]
        $cate_three = \app\common\model\Category::where('pid','=',$id_path[2])->field('id,cate_name')->select();
        //查询所有的类型信息
        $type = \app\common\model\Type::field('id,type_name')->select();
        //组装数据
        $data = [
            'goods' =>  $goods,
            'category'  =>  [
              'cate_one'    =>  $cate_one,
              'cate_two'    =>  $cate_two,
              'cate_three'  =>  $cate_three
            ],
            'type'  =>  $type,
        ];
        //返回数据
        $this->ok($data);
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
            'goods_name|商品名称'           =>  'require|max:20',
            'goods_remark|商品简介'         =>  'require',
            'cate_id|商品分类ID'            =>  'require',
            'brand_id|商品品牌类ID'          => 'require',
            'goods_price|商品价格'          =>  'require|float|egt:0',
            'market_price|市场价'          =>  'require|float|egt:0',
            'cost_price|成本价'            =>  'require|float|egt:0',
            'goods_logo|商品logo'          =>  'require',
            'is_hot|是否热卖'               =>  'in:0,1',
            'is_on_sale|是否上架'           =>  'in:0,1',
            'is_free_shipping|是否包邮'     =>  'in:0,1',
            'is_recommend|是否推荐'         =>  'in:0,1',
            'is_new|是否新品'               =>  'in:0,1',
            'goods_images|相册图片'         =>  'array',
            'type_id|商品模型Id'            =>  'require|egt:0',
            'item|商品规格值'               =>  'require|array',
            'attr|商品属性值'               =>  'require|array'
        ],[
            'goods_price.float'         =>  '商品价格必须为非负整数或小数',
            'market_price.float'        =>  '市场价必须为非负整数或小数',
            'cost_price.float'          =>  '成本价必须为非负整数或小数',
        ]);
        if ($validate !== true) {
            $this->fail($validate);
        }
        //开启事务
        Db::startTrans();
        try {
            //处理商品logo图片
            if (isset($params['goods_logo']) && is_file('.'.$params['goods_logo'])) {
                //生成商品缩略图
                //拼接商品logo路径
                $goods_logo = dirname($params['goods_logo']).DS.'thumb_'.basename($params['goods_logo']);
                Image::open('.'.$params['goods_logo'])->thumb(210,240)->save('.'.$goods_logo);
                $params['goods_logo'] = $goods_logo;
            }
            //处理商品属性值 转化为json字符串 第二个参数阻止汉字自动转化为unicode编码
            $params['goods_attr'] = json_encode($params['goods_attr'],JSON_UNESCAPED_UNICODE);
            //修改操作
            \app\common\model\Goods::update($params,['id'=>$id],true);
            //处理商品相册图片，批量添加
                //初始化相册图片数组
            $goods_images = [];
            if (isset($params['goods_images'])) {
                foreach ($params['goods_images'] as $image) {
                    //拼接缩略图路径
                    $pics_big = dirname($image).DS.'thumb_big_'.basename($image);
                    $pics_sma = dirname($image).DS.'thumb_samll'.basename($image);
                    //生成两种尺寸的缩略图
                    $pric_obj = Image::open('.'.$params['goods_images']);
                    $pric_obj->thumb(800,800)->save('.'.$pics_big);     //800*800
                    $pric_obj->thumb(400,400)->save('.'.$pics_sma);   //400*400
                    //组装图片数据
                    $row = [
                      'goods_id'  => $id,
                      'pics_big'  => $pics_big,
                      'pics_sma'  => $pics_sma
                    ];
                    $goods_images[] = $row;
                }
                //批量添加操作
                $goods_images_model = new GoodsImages();
                $goods_images_model->saveAll($goods_images);
            }
            //删除原来的规格商品SKU
            SpecGoods::destroy(['goods_id'=>$id]);
            //批量添加新的规格商品SKU
            $spec_goods = [];
            foreach ($params['item'] as $v) {
                $v['goods_id'] = $id;
                $spec_goods[] = $v;
            }
                //批量添加操作
            $spec_goods_model = new SpecGoods();
            $spec_goods_model->allowField(true)->saveAll($spec_goods);
            //提交事务
            Db::commit();
            //查询修改成功的数据
            $info = \app\common\model\Goods::with('category,brand,type')->find($id);
            //返回数据
            $this->ok($info);
        }catch (\Exception $e) {
            //事务回滚
            Db::rollback();
            $msg = $e->getMessage();
            $this->fail($msg);
//            $this->fail('修改失败，请重新操作');
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
        //上架中的商品无法删除
            //查询商品信息，存在才能执行删除操作
        $goods = \app\common\model\Goods::find($id);
        if (empty($goods)) {
            $this->fail('数据异常，商品不存在');
        }
            // is_on_sale值只有0和1，可以省略==1的判断
        if ($goods['is_on_sale']) {
            $this->fail('商品上架中，请下架后再删除');
        }
        //删除操作，可以直接调用查询到的goods对象的delete方法
        $goods->delete();
        //删除成功，返回提示信息
        $this->ok('删除成功');
    }

    /**
     * 删除相册图片
     * @param $id
     */
    public function delpics($id)
    {
        //查询要删除的图片记录
        $info = GoodsImages::find($id);
        if (empty($info)) {
            $this->fail('数据异常，图片不存在');
        }
        //删除数据库中的记录
        $info->delete();
        //删除磁盘中的图片文件
        unlink('.'.$info['pics_big']);
        unlink('.'.$info['pics_sma']);
        $this->ok('删除成功');
    }
}
