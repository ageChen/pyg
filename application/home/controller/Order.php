<?php

namespace app\home\controller;

use app\common\model\Address;
use app\common\model\OrderGoods;
use app\common\model\SpecGoods;
use app\home\logic\OrderLogic;
use think\Db;
use think\Request;

class Order extends Base
{
    /**
     * 显示购物车订单信息
     *
     * @return \think\Response
     */
    public function create()
    {
        //登录检测
            //未登录，先保存当前访问地址，登录后跳转回购物车界面
        if (!session('?user_info')) {
            $back_url = session('back_url','home/cart/index');
            //跳转回登录页面
            $this->redirect('home/login/login');
        }
        //展示收货地址信息
            //获取用户id
        $user_id = session('user_info.id');
        //查询用户地址信息
        $address = Address::where('user_id',$user_id)->select();
 /*       //查询选中的购物车记录信息和商品SKU信息
        $cart_data = \app\common\model\Cart::with('goods,spec_goods')->where('is_selected',1)->where('user_id',$user_id)->select();
        //转化为标准的二维数组
        $cart_data = (new Collection($cart_data))->toArray();
//        dump($cart_data);die();
        //循环遍历处理购物车记录信息
            //初始化总金额和总数量
        $total_price = 0;
        $total_number = 0;
        foreach ($cart_data as &$v) {
            //使用sku价格覆盖购物车数据价格
            if (isset($v['price']) && $v['price'] > 0) {
                $v['goods_price'] = $v['price'];
            }
            if (isset($v['cost_price']) && $v['cost_price'] > 0) {
                $v['cost_price'] = $v['cost_price2'];
            }
            //库存处理
            if (isset($v['store_count']) && $v['store_count'] > 0) {
                $v['goods_number'] = $v['store_count'];
            }
            if (isset($v['frozen_count']) && $v['frozen_count'] > 0) {
                $v['frozen_number'] = $v['frozen_count'];
            }
            //总价格和总数量的累加
            $total_number += $v['number'];
            $total_price += $v['goods_price'] * $v['number'];
        }
        //删除多余的$v
        unset($v);*/
//        return view('create',['address'=>$address,'cart_data'=>$cart_data,
//            'total_price'=>$total_price,'total_number'=>$total_number]);
            //简洁写法
        //调用封装好的获取购物车信息和商品信息的函数
        $res = OrderLogic::getCartDataWithGoods();
//        $cart_data = $res['cart_data'];
//        $total_price = $res['total_price'];
//        $total_number = $res['total_number'];
//        return view('create',compact('address','cart_data','total_price','total_number'));
        //添加地址信息到$res中 简化写法
        $res['address'] = $address;
        return view('create',$res);
    }

    /**
     * 结算订单
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        //接收参数
        $params = input();
//        dump($params);die();
        //参数检测
        $validate = $this->validate($params,[
            'address_id' => 'require|integer|gt:0'
        ]);
        if ($validate !== true) {
            $this->error($validate);
        }
        //向订单表添加一条数据
            //查询收货地址信息
        $address = Address::find($params['address_id']);
        if (!$address) {
            $this->error('请重新选择收货地址');
        }
            //生成订单编号 时间加6位随机数
        $order_sn = time().mt_rand(100000,999999);
            //获取用户id
        $user_id = session('user_info.id');
            //调用封装的函数查询购物记录及商品信息
        $res = OrderLogic::getCartDataWithGoods();
            //组装添加数据
        $order_data = [
            'order_sn' => $order_sn,
            'user_id' => $user_id,
            'consignee' => $address['consignee'],
            'address' => $address['area'].$address['address'],
            'phone' => $address['phone'],
            'goods_price' => $res['total_price'],   //商品总价
            'shipping_price' => 0,   //邮费
            'coupon_price' => 0,      //优惠券抵扣
            'order_amount' => $res['total_price'],  //应付款金额 总价+邮费-邮费
            'total_amount' => $res['total_price'],  //订单总价  总价+邮费
        ];
        //开启事务
        Db::startTrans();
        try {
            //进行库存检测
            foreach ($res['cart_data'] as $v) {
                //购买数量大于库存量，抛出异常
                if ($v['number'] > $v['goods_number']) {
                    throw new \Exception('订单中包含库存不足的商品');
                }
            }
            //执行添加操作
            $order = \app\common\model\Order::create($order_data);
            //向订单商品表添加多条数据
            //初始化结果数组
            $order_goods_data = [];
            foreach ($res['cart_data'] as $v) {
                $row = [
                    'order_id' => $order['id'],
                    'goods_id' => $v['goods_id'],
                    'spec_goods_id' => $v['spec_goods_id'],
                    'number' => $v['number'],
                    'goods_name' =>$v['goods_name'],
                    'goods_logo' => $v['goods_logo'],
                    'goods_price' => $v['goods_price'],
                    'spec_value_names' => $v['value_names'],
                ];
                $order_goods_data[] = $row;
            }
            //执行批量添加
            $order_goods_model = new OrderGoods();
            $order_goods_model->saveAll($order_goods_data);
            //删除购物车表中的对应数据
                //测试需要，先注释掉
//        \app\common\model\Cart::where('user_id',$user_id)->delete();
            //进行库存的预扣减，冻结库存
                //初始化结果数组
            $spec_goods = [];
            $goods = [];
            foreach ($res['cart_data'] as $v) {
                //判断商品是否有SKU属性
                if ($v['spec_goods_id']) {
                    //有则修改商品SKU表
                    $row = [
                        'id' => $v['spec_goods_id'],
                        'store_count' => $v['goods_number'] - $v['number'], //库存减去对应数量
                        'store_frozen' => $v['frozen_number'] + $v['number'] //冻结库存量加上对应数量
                    ];
                    $spec_goods[] = $row;
                } else {
                    //没有则修改商品表
                    $row = [
                        'id' => $v['goods_id'],
                        'goods_number' => $v['goods_number'] - $v['number'],
                        'frozen_number' => $v['frozen_number'] + $v['number']
                    ];
                    $goods[] = $row;
                }
            }
            //批量修改库存操作
            $spec_goods_model = new SpecGoods();
            $spec_goods_model->saveAll($spec_goods);
            $goods_model = new \app\common\model\Goods();
            $goods_model->saveAll($goods);
            //提交事务
            Db::commit();

            //二维码图片中的支付链接（本地项目自定义链接，传递订单id参数）
            //$url = url('/home/order/qrpay', ['id'=>$order->order_sn], true, true);
            //用于测试的线上项目域名 http://pyg.tbyue.com
            $url = url('/home/order/qrpay', ['id'=>$order->order_sn, 'debug'=>'true'], true, "http://pyg.tbyue.com");
            //生成支付二维码
            $qrCode = new \Endroid\QrCode\QrCode($url);
            //二维码图片保存路径（请先将对应目录结构创建出来，需要具有写权限）
            $qr_path = '/uploads/qrcode/'.uniqid(mt_rand(100000,999999), true).'.png';
            //将二维码图片信息保存到文件中
            $qrCode->writeFile('.' . $qr_path);
            $this->assign('qr_path', $qr_path);

            //展示支付界面
                //从配置文件中取出支付方式
            $pay_type = config('pay_type');
            return view('pay',['pay_type'=>$pay_type, 'order_sn'=>$order_sn, 'total_price'=>$res['total_price']]);
        }catch(\Exception $e) {
            //回滚事务
            Db::rollback();
            $this->error('订单创建失败，请重新创建');
        }
    }

    /**
     * 支付功能
     */
    public function pay()
    {
        //接收参数
        $params = input();
        //参数检测
        $validate = $this->validate($params,[
            'order_sn' => 'require',
            'pay_code' => 'require'
        ]);
        if ($validate !== true) {
            $this->error($validate);
        }
        //修改选择的支付方式到订单表
            //想查询订单信息
        $user_id = session('user_info.id');
        $order = \app\common\model\Order::where('order_sn',$params['order_sn'])
            ->where('user_id',$user_id)
            ->find();
        if (!$order) {
            $this->error('订单不存在');
        }
            //修改操作
        $order->pay_code = $params['pay_code'];
        $order->pay_name = config('pay_type.'.$params['pay_code'])['pay_name'];
        $order->save();
        //根据选择的支付方式进行支付
        switch ($params['pay_code']) {
            //微信支付
            case 'wechat':
                break;
            //银联支付
            case 'union':
                break;
            //支付宝支付（默认）
            case 'alipay':
            default:
                echo        //输出隐藏表单
                "<form id='alipayment' action='/plugins/alipay/pagepay/pagepay.php' method='post' style='display: none'>
                     <input id='WIDout_trade_no' name='WIDout_trade_no' value='{$order['order_sn']}'/>
                     <input id='WIDsubject' name='WIDsubject' value='品优购订单'/>
                     <input id='WIDtotal_amount' name='WIDtotal_amount' value='{$order['order_amount']}'/>
                     <input id='WIDbody' name='WIDbody' value='品优购订单，付款了也不会发货的订单，嘻嘻哈哈'/>
                </form> 
                     <script>
                         //提交表单
                         document.getElementById('alipayment').submit();
                     </script>
                ";
                break;
        }
    }

    /**
     * 页面跳转  同步通知地址
     */
    public function callback()
    {
        //接收参数
        $params = input();

        //验证参数，比较接收到的参数和支付宝传递过来的参数是否一致
            // 参考alipay的return_url.php文件
//        require_once("./plugins/alipay/config.php");
//        require_once './plugins/alipay/pagepay/service/AlipayTradeService.php';
//        $alipaySevice = new \AlipayTradeService($config);
//        $result = $alipaySevice->check($params);
//        if ($result) {
            //验签成功
            $order_sn = $params['out_trade_no'];
            //查询订单信息
            $order = \app\common\model\Order::where('order_sn',$order_sn)->find();
            //展示界面
            return view('pay_success',['pay_name'=>'支付宝', 'order_amount'=>$params['total_amount'], 'order'=>$order]);
//        } else {
//            //验签失败
//                //展示界面
//            return view('pay_fail',['msg'=>'数据异常']);
//        }
    }

    /**
     *聚合支付功能
     */
    public function qrpay()
    {
        $agent = request()->server('HTTP_USER_AGENT');
        //判断扫码支付方式
        if ( strpos($agent, 'MicroMessenger') !== false ) {
            //微信扫码
            $pay_code = 'wx_pub_qr';
        }else if (strpos($agent, 'AlipayClient') !== false) {
            //支付宝扫码
            $pay_code = 'alipay_qr';
        }else{
            //默认为支付宝扫码支付
            $pay_code = 'alipay_qr';
        }
        //接收订单id参数
        $order_sn = input('id');
        //创建支付请求
        $this->pingpp($order_sn,$pay_code);
    }

    /**
     * 发起ping++请求
     */
    public function pingpp($order_sn,$pay_code)
    {
        //查询订单信息
        $order = \app\common\model\Order::where('order_sn', $order_sn)->find();
        //ping++聚合支付
        \Pingpp\Pingpp::setApiKey(config('pingpp.api_key'));// 设置 API Key
        \Pingpp\Pingpp::setPrivateKeyPath(config('pingpp.private_key_path'));// 设置私钥
        \Pingpp\Pingpp::setAppId(config('pingpp.app_id'));
        $params = [
            'order_no'  => $order['order_sn'],
            'app'       => ['id' => config('pingpp.app_id')],
            'channel'   => $pay_code,
            'amount'    => $order['order_amount'],
            'client_ip' => '127.0.0.1',
            'currency'  => 'cny',
            'subject'   => 'Your Subject',//自定义标题
            'body'      => 'Your Body',//自定义内容
            'extra'     => [],
        ];
        if($pay_code == 'wx_pub_qr'){
            $params['extra']['product_id'] = $order['id'];
        }
        //创建Charge对象
        $ch = \Pingpp\Charge::create($params);
        //跳转到对应第三方支付链接
        $this->redirect($ch->credential->$pay_code);die;
    }

    /**
     *     查询订单状态
     */
    public function status()
    {
        //接收订单编号
        $order_sn = input('order_sn');
        //查询订单状态
        /*$order_status = \app\common\model\Order::where('order_sn', $order_sn)->value('order_status');
        return json(['code' => 200, 'msg' => 'success', 'data'=>$order_status]);*/
        //通过线上测试
        $res = curl_request("http://pyg.tbyue.com/home/order/status/order_sn/{$order_sn}");
        echo $res;die;
    }

    /**
     * 展示支付结果界面
     */
    public function payresult()
    {
        $order_sn = input('order_sn');
        $order = \app\common\model\Order::where('order_sn', $order_sn)->find();
        if(empty($order)){
            return view('payfail', ['msg' => '订单编号错误']);
        }else{
            return view('paysuccess', ['pay_name' => $order->pay_name, 'order_amount'=>$order['order_amount'], 'order' => $order]);
        }
    }
}
