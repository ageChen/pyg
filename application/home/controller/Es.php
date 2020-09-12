<?php

namespace app\home\controller;

use think\Controller;
use think\Request;

class Es extends Controller
{
    /**
     * 创建索引
     *
     * @return \think\Response
     */
    public function index()
    {
        $es = \Elasticsearch\ClientBuilder::create()->setHosts(['127.0.0.1:9200'])->build();
        $params = [
            'index' => 'test_index'
        ];
        $r = $es->indices()->create($params);
        dump($r);die;
    }

    /**
     * 添加文档
     *
     * @return \think\Response
     */
    public function create()
    {
        $es = \Elasticsearch\ClientBuilder::create()->setHosts(['127.0.0.1:9200'])->build();
        $params = [
            'index' => 'test_index',
            'type' => 'test_type',
            'id' => 100,
            'body' => ['id'=>100, 'title'=>'PHP从入门到精通', 'author' => '张三']
        ];

        $r = $es->index($params);
        dump($r);die;
    }

    /**
     * 修改文档
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update()
    {
        $es = \Elasticsearch\ClientBuilder::create()->setHosts(['127.0.0.1:9200'])->build();
        $params = [
            'index' => 'test_index',
            'type' => 'test_type',
            'id' => 100,
            'body' => [
                'doc' => ['id'=>100, 'title'=>'MySQL从删库到跑路', 'author' => '刘晓兴']
            ]
        ];

        $r = $es->update($params);
        dump($r);die;
    }

    /**
     * 删除文档
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete()
    {
        $es = \Elasticsearch\ClientBuilder::create()->setHosts(['127.0.0.1:9200'])->build();
        $params = [
            'index' => 'test_index',
            'type' => 'test_type',
            'id' => 100,
        ];

        $r = $es->delete($params);
        dump($r);die;
    }
}
