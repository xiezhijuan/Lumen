<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
// 微信公众号模板消息推送
class WechatController extends Controller
{
    // 微信公众号授权配置
   private static $wxappid = "wx9840b2471be196fe";//填写微信后台的appid 
   private static  $wxsecret = "4097f2b5993d1d7f3496ccc75bdc1fac";//填写微信后台的appsecret  
   private $wxtoken = '02U82f3X3Cb0kCuIbVevIqcjEsIlFMP5';

   private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        
        $token = $this->wxtoken;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        
        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }
    // 验证
   public function index(){
        $echoStr = $_GET['echostr'];
      if($this->checkSignature()){
           return  $echoStr;
      }else{
        $echoStr = 'Token verification failed.';
        return  $echoStr ;
      }
   }
   // 回调地址
   public function geReturnNotice(){

    echo "回调地址";

   }

   public  function gettest(){
        $weiwei_token = self::getToken(); // 获取微信token
        $url = 'https://api.weixin.qq.com/cgi-bin/user/get?access_token='.$weiwei_token.'&next_openid=';
        $outputa = self::curlGet($url);
        $result = json_decode($outputa, true);
        $data = $result['data']['openid'];
         echo "<pre>";
         // var_dump($result);
         // die;
        foreach ($data as $key => $value) {
            $this->getUnionid($value);
        }
            
         
   }

   public  function getUnionid($openid){
            $weiwei_token = self::getToken(); // 获取微信token
            $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$weiwei_token.'&openid='.$openid.'&lang=zh_CN';
            $outputa = self::curlGet($url);
            $result = json_decode($outputa, true);
            
             var_dump($result);
         
                
   }
    // 获取TOKEN
    public static function getToken(){
         writelog('---------getToken-----------------','Wechat','getToken');
        if (empty(Cache::get('wx_access_token'))) {
            writelog('请求获取-------------------','Wechat','getToken');
             $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".self::$wxappid . "&secret=" .self::$wxsecret;
            $outputa = self::curlGet($url);
            $data = json_decode($outputa, true);
            Cache::put('wx_access_token',$data['access_token'],10);
            writelog($data,'Wechat','getToken');
            return $data['access_token'];
        }else{
            writelog('缓存获取-------------------','Wechat','getToken');
            writelog(Cache::get('wx_access_token'),'Wechat','getToken');
             return Cache::get('wx_access_token');
        }
    }

     // 微信授权地址
    public static function getAuthorizeUrl($url){
        $url_link = urlencode($url);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . self::$wxappid . "&redirect_uri={$url_link}&response_type=code&scope=snsapi_base&state=1#wechat_redirect";
    }


    public function sendNews (){
        writelog('-----------------sendNews----------------------' ,'Wechat','sendNews');
        $param = $this->requests->getQueryParams();
        $weiwei_token = self::getToken(); // 获取微信token
        writelog('weiwei_token='.$weiwei_token ,'Wechat','sendNews');
        $openid = 'o6sFT51WYsWU44chYcXvvV50bbxs';//谢志娟
          
         # 公众号消息推送
       $res =  self::pushMessage([
                'openid' => $openid, // 用户openid
                'access_token' => $weiwei_token,
                'template_id' => "odDwfU4p1n-TH6hga0-4Dwn1vsG-NjjipypADzJTK54", // 填写你自己的消息模板ID
                // "url"=>"http://weixin.qq.com",
             
                'appid'=> $this->appid, //小程序appid
                'pagepath'=>'/pages/index/index', //小程序跳转路径
                
                'data' => [ // 模板消息内容，根据模板详情进行设置
                    'first'    => ['value' => urlencode("测试标题"),'color' => "#743A3A"],
                    'keyword1' => ['value' => urlencode("这是课程"),'color'=>'blue'],
                    'keyword2' => ['value' => urlencode("这是地点"),'color'=>'blue'],
                    'keyword3' => ['value' => urlencode("讲师名"),'color' => 'green'],
                    'keyword4' => ['value' => urlencode("2020年02月17日 16:06"),'color' => 'green'],
                    'remark'   => ['value' => urlencode("点击进入小程序"),'color' => '#743A3A']
                ],
            ]);
        writelog('-----------------sendNews-----end-----------------' ,'Wechat','sendNews');

           echo "<pre>";
            var_dump($res);


       

    }


    /**
     * pushMessage 发送自定义的模板消息
     * @param  array  $data          模板数据
        $data = [
            'openid' => '', 用户openid
            'url' => '', 跳转链接
            'template_id' => '', 模板id
             'url'         => $data['url'],
               'miniprogram'=>[
                    'appid' =>'小程序appid',
                    'pagepath'=>'小程序跳转路径'
               ],
            'data' => [ // 消息模板数据
                'first'    => ['value' => urlencode('黄旭辉'),'color' => "#743A3A"],
                'keyword1' => ['value' => urlencode('男'),'color'=>'blue'],
                'keyword2' => ['value' => urlencode('1993-10-23'),'color' => 'blue'],
                'remark'   => ['value' => urlencode('我的模板'),'color' => '#743A3A']
            ]
        ];
     * @param  string $topcolor 模板内容字体颜色，不填默认为黑色
     * @return array
     */
    public static function pushMessage($data = [],$topcolor = '#0000'){
        writelog('-----------------pushMessage---------------------' ,'Wechat','pushMessage');
        $template = [
            'touser'      => $data['openid'],
            'template_id' => $data['template_id'],
            // 'url'         => $data['url'],
               'miniprogram'=>[
                    'appid' =>$data['appid'],
                    'pagepath'=>$data['pagepath']
               ],
            'topcolor'    => $topcolor,
            'data'        => $data['data']
        ];
        writelog($template ,'Wechat','pushMessage');
        $json_template = json_encode($template);
        writelog($json_template ,'Wechat','pushMessage');
        $access_token = self::getToken();
        writelog($access_token ,'Wechat','pushMessage');
        if(!empty($access_token)){
            $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=" . $access_token;
            $result = self::curlPost($url, urldecode($json_template));
            $resultData = json_decode($result, true);
        return $resultData;
        }else{
           return false;
        }

        
    }
 

    /**
     * get_page_url 获取完整URL
     * @return url
     */
    public function get_page_url($type = 0){
        $pageURL = 'http';
        if($_SERVER["HTTPS"] == 'on'){
            $pageURL .= 's';
        }
        $pageURL .= '://';
        if($type == 0){
            $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        }else{
            $pageURL .= $_SERVER["SERVER_NAME"];
        }
        return $pageURL;
    }


     /**
     * 发送get请求
     * @param string $url 链接
     * @return bool|mixed
     */
    private static function curlGet($url){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        if(curl_errno($curl)){
            return 'ERROR ' . curl_error($curl);
        }
        curl_close($curl);
        return $output;
    }

    /**
     * 发送post请求
     * @param string $url 链接
     * @param string $data 数据
     * @return bool|mixed
     */
    private static function curlPost($url, $data = null){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if(!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

}
