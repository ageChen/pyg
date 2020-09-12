<?php

namespace app\adminapi\controller;

use app\admin\model\SpecValue;
use app\common\model\Attribute;
use app\common\model\Goods;
use app\common\model\Spec;
use think\Controller;
use think\Db;
use think\Request;

class Type extends BaseApi
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        //查询数据
        $list = \app\common\model\Type::select();
        //返回数据
        $this->ok($list);
    }

    /**
     * 添加商品模型
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
            'type_name|模型名称'    =>  'require|max:20',
            'spec|商品规格'         =>  'require|array',
            'attr|商品属性'         =>  'require|array'
        ]);
        if ($validate !== true) {
            $this->fail('参数错误',403);
        }
        //开启事务
        Db::startTrans();
        try {
            //添加商品模型
            $type = \app\common\model\Type::create($params,true);
            //添加商品规格名，去空操作
                //外层循环，去除空的规格名
            foreach ($params['spec'] as $i=>$spec) {
                if (trim($spec['name']) == '') {
                    unset($params['spec'][$i]);
                    //去掉后进入下一个外层循环
                    continue;
                }
                //内层循环，去除空的规格值
                foreach ($spec['value'] as $k=>$value) {
                    if (trim($value) == '') {
                        unset($params['spec'][$i]['value'][$k]);
                    }
                }
                //内层循环结束，去除去掉空的规格值后规格名变为空的数组
                if (empty($params['spec'][$i]['value'])) {
                    unset($params['spec'][$i]);
                }
            }
                //组装数据表需要的数据
                    //初始化批量添加数据数组
            $specs = [];
            foreach ($params['spec'] as $spec) {
                $row = [
                    'type_id'   =>  $type['id'],
                    'spec_name' =>  $spec['name'],
                    'sort'      =>  $spec['sort']
                ];
                //将添加的数据加入到specs数组中进行批量添加
                $specs[] = $row;
            }
                //批量添加操作
                    //实例化spec模型，调用allowField方法去除非数据表字段，saveAll方法批量添加数据
            $spec_model = new Spec();
            $spec_data = $spec_model->allowField(true)->saveAll($specs);
                //获取批量添加后的数据ID array_colum方法获取数组中的一列数据
            $spec_ids = array_column($spec_data,'id');
            //添加商品规格值
                //初始化批量商品规格值数据
            $spec_values = [];
                //外层循环，获取商品规格名下的规格值
            foreach ($params['spec'] as $i=>$spec) {
                //内层循环，获取商品规格值
                foreach ($spec['value'] as $value) {
                    $row = [
                      'spec_id'     =>  $spec_ids[$i],
                      'spec_value'  =>  $value,
                      'type_id'     =>  $type['id']
                    ];
                    //将要添加的数据放到批量添加数组中
                    $spec_values[] = $row;
                }
            }
                //批量添加操作
                    //实例化规格值模型
            $spec_value_model = new SpecValue();
            $spec_value_model->allowField(true)->saveAll($spec_values);
            //添加商品属性值，去空操作
                //外层循环去除空的属性名
            foreach ($params['attr'] as $i=>$attr) {
                if (trim($attr['name']) == '') {
                    unset($params['attr'][$i]);
                    continue;
                }
                //内层循环去除空的属性值
                foreach ($attr['value'] as $k=>$value) {
                    if (trim($value) == '') {
                        unset($params['attr'][$i]['value'][$k]);
                    }
                }
                //内层循环结束，去除去掉空的属性值后属性名为空的数据
                if (empty($params['attr'][$i]['value'])) {
                    unset($params['attr'][$i]);
                }
            }
                //初始化批量添加属性数据
            $attrs = [];
            foreach ($params['attr'] as $attr) {
                $row = [
                    'attr_name'     =>  $attr['name'],
                    'attr_values'   =>  implode(',',$attr['value']),
                    'sort'          =>  $attr['sort'],
                    'type_id'       =>  $type['id']
                ];
                $attrs[] = $row;
            }
                //批量添加操作
            $attr_model = new Attribute();
            $attr_model->allowField(true)->saveAll($attrs);
            //添加成功，提交事务
            Db::commit();
            //查询添加成功的数据并返回
            $type = \app\common\model\Type::find($type['id']);
            $this->ok($type);
        }catch (\Exception $e) {
            //添加失败，事务回滚
            Db::rollback();
            $this->fail('添加出错，请重新添加',402);
//            $msg = $e->getMessage();
//            $this->fail($msg);
        }

    }

    /**
     * 商品模型详情
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        //查询数据
            //关联类型规格，类型规格关联类型规格值 关联类型属性
        $info = \app\common\model\Type::with('spec,spec.spec_values,attrs')->find($id);
        //返回数据
        $this->ok($info);
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
           'type_name|模型名称'     =>  'require|max:20',
           'spec|商品规格'          =>  'require|array',
           'attr|商品属性'          =>  'require|array'
        ]);
        if ($validate !== true) {
            $this->fail($validate,403);
        }
        //开启事务
        Db::startTrans();
        try {
            //修改模型名称
            \app\common\model\Type::update(['type_name'=>$params['type_name']],['id'=>$id],true);
            //去掉空的规格名和规格值
                //外层遍历规格名
            foreach ($params['spec'] as $i=>$spec) {
                if (trim($spec['name']) == '') {
                    unset($params['spec'][$i]);
                    continue;
                }
                //内层遍历规格名
                foreach ($spec['value'] as $k=>$value) {
                    if (trim($value) == '') {
                        unset($params['spec'][$i]['value'][$k]);
                    }
                }
                //内层遍历结束去掉规格名为空的数组
                if (empty($params['spec'][$i]['value']) == '') {
                    unset($params['spec'][$i]);
                }
            }
            //批量删除原来的规格名 条件是type_id
            Spec::destroy(['type_id'=>$id]);
            //批量添加新的规格名
            $specs = [];
            foreach ($params['spec'] as $spec) {
                    $row = [
                        'type_id'   => $id,
                        'spec_name' => $spec['spec_name'],
                        'sort'      => $spec['sort']
                    ];
                    $specs[] = $row;
            }
            $spec_model = new Spec();
            $spec_data = $spec_model->allowField(true)->saveAll($specs);
            //批量删除原来的规格值
            \app\common\model\SpecValue::destroy(['type_id'=>$id]);
            //批量添加规格值
            $spec_values = [];
            foreach ($params['spec'] as $i=>$spec) {
                foreach ($spec['value'] as $value) {
                    $row = [
                        'type_id'   => $id,
                        'spec_id'   => $spec_data[$i]['id'],
                        'spec_value'=> $value
                    ];
                    $spec_values[] = $row;
                }
            }
            $spec_value_model = new SpecValue();
            $spec_value_model->saveAll($spec_values);
            //去除空的属性值
            foreach ($params['attr'] as $i=>$attr) {
                //外层循环获取属性名
                if (trim($attr['name']) == '') {
                    unset($params['attr'][$i]);
                    continue;
                }
                //内层循环获取属性值
                foreach ($attr['value'] as $k=>$value) {
                  if (trim($value) == '') {
                      unset($params['attr'][$i]['value'][$k]);
                  }
                }
                //内层循环结束去掉属性值为空的数组
                if (empty($params['attr'][$i]['value'])) {
                    unset($params['attr'][$i]);
                }
            }
            //批量删除原来的属性值
            Attribute::destroy(['type_id'=>$id]);
            //批量添加新的属性
            $attrs = [];
            foreach ($params['attr'] as $attr) {
                $row = [
                    'type_id' => $id,
                    'attr_name'  => $attr['name'],
                    'attr_value' => implode(',',$attr['value']),    //将attr['value]数组拼接成字符串
                    'sort'       => $attr['sort']
                ];
                $attrs[] = $row;
            }
            $attr_model = new Attribute();
            $attr_model->saveAll($attrs);
            //修改成功，提交事务
            Db::commit();
            $data = ['id'=>$id,'type_name'=>$params['type_name']];
            $this->ok($data);
        }catch(\Exception $e) {
            //修改失败，事务回滚
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
        //查询类型下是否存在商品信息，存在则无法删除
        $goods = Goods::where('type_id','=',$id)->find();
//        $goods = Goods::find(['type_id',$id]);
//        dump($goods);die();
        if ($goods) {
            $this->fail('类型下存在商品信息，无法删除');
        }
        //开启MySQL事务 设计多个数据表的操作，全部成功才算成功，有一个失败则整体操作失败，回滚操作
        Db::startTrans();
        try {
            //删除类型表，类型规格、类型规格值、类型属性值数据
            \app\common\model\Type::destroy($id);
            Spec::destroy(['type_id'=>$id]);
            SpecValue::destroy(['type_id'=>$id]);
            Attribute::destroy(['type_id'=>$id]);
            //删除成功，提交事务，给出成功提示
            Db::commit();
            $this->ok('删除成功');
        }catch (\Exception $e) {
            //删除出错，回滚事务，给出错误提示
            Db::rollback();
            $this->fail('删除出错，请重新删除');
        }
    }
}
