<?php
header('Access-Control-Allow-Origin:*');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
error_reporting(E_ALL);
ini_set('display_errors', '1');
// 定义时区
ini_set('date.timezone','Asia/Shanghai');//12202服务器时间差18秒

class Weixinpay {
    // 定义配置项
//    private $config=array(
//        'APPID'              => 'wx7d93e0114cc3453a', // 微信支付APPID
//        'MCHID'              => '1457705302', // 微信支付MCHID 商户收款账号
//        'KEY'                => 'ede449b5c872ada3365d8f91563dd8b6', // 微信支付KEY
//        'APPSECRET'          => 'e64bda5d1006894a4f3cfb1b908dca19',  //公众帐号secert
//        'NOTIFY_URL'         => 'http://310975f0.nat123.cc/Home/Weixinpay/notify', // 接收支付状态的连接  改成自己的域名
//        );
    // 构造函数
    public function __construct(){
    
        $this->config=C('WEIXINPAY_CONFIG');
    }
    /**
     * 统一下单
     * @param  array $order 订单 必须包含支付所需要的参数 body(产品描述)、total_fee(订单金额)、out_trade_no(订单号)、product_id(产品id)、trade_type(类型：JSAPI，NATIVE，APP)
     */
    public function unifiedOrder($order){
        // 获取配置项
        $weixinpay_config=$this->config;
        $config=array(
            'appid'=>$weixinpay_config['APPID'],
            'mch_id'=>$weixinpay_config['MCHID'],
            'nonce_str'=>'test',
            'spbill_create_ip'=>'192.168.1.164',
            'notify_url'=>$weixinpay_config['NOTIFY_URL']
            );
        // 合并配置数据和订单数据
        $data=array_merge($order,$config);
        // 生成签名
        $sign=$this->makeSign($data);//通过签名算法计算得出的签名值
        $data['sign']=$sign;
        $xml=$this->toXml($data);
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';//接收xml数据的文件
        $header[] = "Content-type: text/xml";//定义content-type为xml,注意是数组
        $ch = curl_init ($url);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 兼容本地没有指定curl.cainfo路径的错误
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $response = curl_exec($ch);
        if(curl_errno($ch)){
            // 显示报错信息；终止继续执行
            die(curl_error($ch));
        }
        curl_close($ch);
        $result=$this->toArray($response);
        // 显示错误信息
        if ($result['return_code']=='FAIL') {
            die($result['return_msg']);
        }
        $result['sign']=$sign;
        $result['nonce_str']='test';

        return $result;
    }
    /**
     * 验证
     * @return array 返回数组格式的notify数据
     */
    public function notify(){
        // 获取xml
        $xml=file_get_contents('php://input', 'r'); 
        // 转成php数组
        $data=$this->toArray($xml);
        // 保存原sign
        $data_sign=$data['sign'];
        // sign不参与签名
        unset($data['sign']);
        $sign=$this->makeSign($data);
        // 判断签名是否正确  判断支付状态
        if ($sign===$data_sign && $data['return_code']=='SUCCESS' && $data['result_code']=='SUCCESS') {
            $result=$data;
        }else{
            $result=false;
        }
        // 返回状态给微信服务器
        if ($result) {
            $str='<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        }else{
            $str='<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名失败]]></return_msg></xml>';
        }
        echo $str;
        return $result;
    }
    /**
     * 输出xml字符
     * @throws WxPayException
    **/
    public function toXml($data){
        if(!is_array($data) || count($data) <= 0){
            throw new WxPayException("数组数据异常！");
        }
        $xml = "<xml>";
        foreach ($data as $key=>$val){
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml; 
    }
    /**
     * 生成签名
     * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    public function makeSign($data){
        // 去空
        $data=array_filter($data);
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a=http_build_query($data);
        $string_a=urldecode($string_a);
        //签名步骤二：在string后加入KEY
        $config=$this->config;
        $string_sign_temp=$string_a."&key=".$config['KEY'];
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);
        // 签名步骤四：所有字符转为大写
        $result=strtoupper($sign);
        return $result;
    }
    /**
     * 将xml转为array
     * @param  string $xml xml字符串
     * @return array       转换得到的数组
     */
    public function toArray($xml){   
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $result= json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);        
        return $result;
    }
    /**
     * 获取jssdk需要用到的数据
     * @return array jssdk需要用到的数据
     */
    public function getParameters(){

        // 获取配置项
        $config=$this->config;
        // 如果没有get参数没有code；则重定向去获取openid；
        if (!isset($_GET['code'])) {

            // 获取订单号
            $out_trade_no=I('get.out_trade_no',1,'intval');

//            var_dump($out_trade_no);

            // 返回的url
            $redirect_uri=U('Home/Weixinpay/pay','','',true);
//            var_dump($redirect_uri);die;
//            $redirect_uri="http://www.12202.com.cn/diamond/index.php/Home/Ajax/index2";
//            $redirect_uri="http://www.12202.com.cn/vue/index.html#/recharge";//拉起支付SDK的页面
            // $redirect_uri="http://www.12202.com.cn/vue/#/PAY";//拉起支付SDK的页面
            $redirect_uri = "https://www.goldenbrother.cn/app/#/PAY";
            $redirect_uri=urlencode($redirect_uri);

            $url='https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$config['APPID'].'&redirect_uri='.$redirect_uri.'&response_type=code&scope=snsapi_base&state='.$out_trade_no.'#wechat_redirect';
//            var_dump($url);die;
            redirect($url);
        }else{
//            echo 6;
            // 如果有code参数；则表示获取到openid
            $code=I('get.code');
//            var_dump($code);die;
            // 取出订单号
            $out_trade_no=I('get.state',0,'intval');
//            $order_id = I('get.out_trade_no');
//            $order_id = substr($order_id, -1);


            // 组合获取prepay_id的url
            $url='https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$config['APPID'].'&secret='.$config['APPSECRET'].'&code='.$code.'&grant_type=authorization_code';

            // curl获取prepay_id
//            $result=$this->curl_get_contents($url);dump($result);die;
            $result=$this->httpGet($url);
            $result=json_decode($result,true);
            $openid=$result['openid'];
            // 订单数据  请根据订单号out_trade_no 从数据库中查出实际的body、total_fee、out_trade_no、product_id
            $log = M('order_log')
                ->alias("t1")
                ->field("t2.body,t2.money,t2.id as order_id")
                ->where("t1.out_trade_no = $out_trade_no")
                ->join("left join `order` as t2 on t2.id = t1.order_id")
                ->find();

//            $order=array(
//                'body'=>'test',// 商品描述（需要根据自己的业务修改）
//                'total_fee'=>1,// 订单金额  以(分)为单位（需要根据自己的业务修改）
//                'out_trade_no'=>$out_trade_no,// 订单号（需要根据自己的业务修改）
//                'product_id'=>'1',// 商品id（需要根据自己的业务修改）
//                'trade_type'=>'JSAPI',// JSAPI公众号支付
//                'openid'=>$openid// 获取到的openid
//            );
            $order=array(
                'body'=>$log['body'],// 商品描述（需要根据自己的业务修改）
                'total_fee'=>$log['money'],// 订单金额  以(分)为单位（需要根据自己的业务修改）
                'out_trade_no'=>$out_trade_no,// 订单号（需要根据自己的业务修改）
                'product_id'=>$log['order_id'],// 商品id（需要根据自己的业务修改）
                'trade_type'=>'JSAPI',// JSAPI公众号支付
                'openid'=>$openid// 获取到的openid
            );
            // 统一下单 获取prepay_id
            $unified_order=$this->unifiedOrder($order);
            // 获取当前时间戳
            $time=time();
            // 组合jssdk需要用到的数据
            $data=array(
                'appId'=>$config['APPID'], //appid
                'timeStamp'=>strval($time), //时间戳
                'nonceStr'=>$unified_order['nonce_str'],// 随机字符串
                'package'=>'prepay_id='.$unified_order['prepay_id'],// 预支付交易会话标识
                'signType'=>'MD5'//加密方式
            );
            // 生成签名
            $data['paySign']=$this->makeSign($data);
            //添加的返回数据
            $data['amount'] = $order['product_id'];
//            $data['amount'] = M('Order')->where(['id'=>$order['product_id']])->getField('amount');
            return $data;
//            $data = json_encode($data);
//            var_dump($data);die;
        }
    }

    //物流支付
    public function getParameters2(){

        // 获取配置项
        $config=$this->config;
        // 如果没有get参数没有code；则重定向去获取openid；
        if (!isset($_GET['code'])) {

            // 获取订单号
            $out_trade_no=I('get.out_trade_no',1,'intval');

//            var_dump($out_trade_no);

            // 返回的url
            $redirect_uri=U('Home/Weixinpay/pay','','',true);
//            var_dump($redirect_uri);die;
//            $redirect_uri="http://www.12202.com.cn/diamond/index.php/Home/Ajax/index2";
//            $redirect_uri="http://www.12202.com.cn/vue/#/CREATE_ORDER";
            // $redirect_uri="http://www.12202.com.cn/vue/#/PAY";
            $redirect_uri="https://www.goldenbrother.cn/app/#/PAY";
            $redirect_uri=urlencode($redirect_uri);

            $url='https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$config['APPID'].'&redirect_uri='.$redirect_uri.'&response_type=code&scope=snsapi_base&state='.$out_trade_no.'#wechat_redirect';

            redirect($url);
        }else{

            // 如果有code参数；则表示获取到openid
            $code=I('get.code');
//            var_dump($code);die;
            // 取出订单号
            $out_trade_no=I('get.state',0,'intval');
//            $order_id = I('get.out_trade_no');
//            $order_id = substr($order_id, -1);


            // 组合获取prepay_id的url
            $url='https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$config['APPID'].'&secret='.$config['APPSECRET'].'&code='.$code.'&grant_type=authorization_code';

            // curl获取prepay_id
//            $result=$this->curl_get_contents($url);dump($result);die;
            $result=$this->httpGet($url);
            $result=json_decode($result,true);
            $openid=$result['openid'];
            // 订单数据  请根据订单号out_trade_no 从数据库中查出实际的body、total_fee、out_trade_no、product_id
            $log = M('Express_pay')
                ->alias("t1")
                ->field("t2.body,t2.money,t2.id as order_id")
                ->where("t1.out_trade_no = $out_trade_no")
                ->join("left join `order` as t2 on t2.id = t1.order_id")
                ->find();

//            $order=array(
//                'body'=>'test',// 商品描述（需要根据自己的业务修改）
//                'total_fee'=>1,// 订单金额  以(分)为单位（需要根据自己的业务修改）
//                'out_trade_no'=>$out_trade_no,// 订单号（需要根据自己的业务修改）
//                'product_id'=>'1',// 商品id（需要根据自己的业务修改）
//                'trade_type'=>'JSAPI',// JSAPI公众号支付
//                'openid'=>$openid// 获取到的openid
//            );
            $order=array(
                'body'=>$log['body'],// 商品描述（需要根据自己的业务修改）
//                'total_fee'=>$log['money'],// 订 单金额  以(分)为单位（需要根据自己的业务修改）
                'total_fee'=>10,// 订 单金额  以(分)为单位（需要根据自己的业务修改）
                'out_trade_no'=>$out_trade_no,// 订单号（需要根据自己的业务修改）
                'product_id'=>$log['order_id'],// 商品id（需要根据自己的业务修改）
                'trade_type'=>'JSAPI',// JSAPI公众号支付
                'openid'=>$openid// 获取到的openid
            );
            // 统一下单 获取prepay_id
            $unified_order=$this->unifiedOrder($order);
            // 获取当前时间戳
            $time=time();
            // 组合jssdk需要用到的数据
            $data=array(
                'appId'=>$config['APPID'], //appid
                'timeStamp'=>strval($time), //时间戳
                'nonceStr'=>$unified_order['nonce_str'],// 随机字符串
                'package'=>'prepay_id='.$unified_order['prepay_id'],// 预支付交易会话标识
                'signType'=>'MD5'//加密方式
            );
            // 生成签名
            $data['paySign']=$this->makeSign($data);
            //添加的返回数据
            $data['amount'] = $order['product_id'];
//            $data['amount'] = M('Order')->where(['id'=>$order['product_id']])->getField('amount');
            return $data;
//            $data = json_encode($data);
//            var_dump($data);die;
        }
    }


    /**
     * 生成支付二维码
     * @param  array $order 订单 必须包含支付所需要的参数 body(产品描述)、total_fee(订单金额)、out_trade_no(订单号)、product_id(产品id)、trade_type(类型：JSAPI，NATIVE，APP)
     */
    public function pay($order){
        $result=$this->unifiedOrder($order);
        $decodeurl=urldecode($result['code_url']);
        qrcode($decodeurl);
    }
    /**
     * curl 请求http
     */
    public function curl_get_contents($url){
        $ch=curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);                //设置访问的url地址
        // curl_setopt($ch,CURLOPT_HEADER,1);               //是否显示头部信息
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);               //设置超时
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);   //用户访问代理 User-Agent
        curl_setopt($ch, CURLOPT_REFERER,$_SERVER['HTTP_HOST']);        //设置 referer
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);          //跟踪301
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);        //返回结果
        $r=curl_exec($ch);
        curl_close($ch);
        return $r;
    }

    function httpGet($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }

   
}