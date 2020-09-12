<?php

namespace app\adminapi\controller;

use think\Controller;

class Upload extends BaseApi
{
    /*
     * 上传单个文件
     */
    public function logo()
    {
        //接收参数
        $type = input('type');
        if (empty($type)) {
            $this->fail('参数有误');
        }
        //获取文件
        $file = request()->file('logo');
        if (empty($file)) {
            $this->fail('文件上传不能为空');
        }
        //移动图片
            //移动前先验证文件大小和文件类型
        $info = $file->validate(['size'=>15*1024*1024,'type'=>'image/jpeg,image/gif,image/png'])
                     ->move(ROOT_PATH.'public'.DS.'uploads'.DS.$type);
        if ($info) {
            //上传成功，返回路径
            $path = DS.'uploads'.DS.$type.DS.$info->getSaveName();
            $this->ok($path);
        } else {
            //上传失败，返回错误信息
            $msg = $file->getError();
            $this->fail($msg);
        }
    }

    /*
     * 上传多个文件
     */
    public function images()
    {
        //接收type参数，默认为goods
        $type = input('type','goods');
        if (empty($type)) {
            $this->fail('参数有误');
        }
        //获取上传文件
        $files = request()->file('images');
        //创建数组存放成功路径和失败信息
        $data = ['success'=>[],'error'=>[]];
        //遍历数组逐个上传文件
        foreach ($files as $file) {
            //上传路径
            $dir = ROOT_PATH.DS.'public'.DS.'uploads'.DS.$type;
            if (!is_dir($dir)) {
                //如果文件目录不存在则创建
                mkdir($dir);
            }
            //移动文件到指定目录
            $info = $file->validate(['size'=>15*1024*1024,'type'=>'image/jpeg,image/png,image/gif'])->move($dir);
            if ($info) {
                //成功拼接路径并返回
                $path = DS.'uploads'.DS.$type.DS.$info->getSaveName();
                $data['success'][] = $path;
            } else {
                //失败返回错误信息
                    //getInfo 用来获取文件原始信息
                $data['error'][] = [
                    'name'  => $file->getInfo('name'),  //返回出错的文件名
                    'msg'   => $file->getError()        //返回错误信息
                ];
            }
        }
        //返回数据
        $this->ok($data);
    }
}
