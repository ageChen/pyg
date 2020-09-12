<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------


use think\Route;

//后台接口路由
Route::domain('adminapi',function (){
    //默认首页路由
    Route::get('/','adminapi/index/index');
    //验证码接口路由
    Route::get('captcha/:id', "\\think\\captcha\\CaptchaController@index");   //访问图片需要
    Route::get('captcha','adminapi/login/captcha');
    //登录接口路由
    Route::post('login','adminapi/login/login');
    //退出接口路由
    Route::get('logout','adminapi/login/logout');
    //权限接口路由(第三个参数为后缀名，设为空，第四个参数是对传递参数的检测)
    Route::resource('auths','adminapi/auth',[],['id'=>'\d+']);
    //角色权限接口路由
    Route::get('nav','adminapi/auth/nav');
    //角色列表接口路由
    Route::resource('roles','adminapi/role');
    //管理员列表接口路由
    Route::resource('admins','adminapi/admin',[],['id'=>'\d+']);
    //商品列表接口路由
    Route::resource('categorys','adminapi/category',[],['id'=>'\d+']);
    //上传单个图片接口路由
    Route::post('logo','adminapi/upload/logo');
    //上传多个图片接口路由
    Route::post('images','adminapi/upload/images');
    //商品品牌接口路由
    Route::resource('brands','adminapi/brand');
    //商品模型接口路由
    Route::resource('types','adminapi/type');
    //商品接口路由
    Route::resource('goods','adminapi/goods');
    //删除相册图片
    Route::delete('delpics/:id','adminapi/goods/delpics',[],['id'=>'\d+']);
});