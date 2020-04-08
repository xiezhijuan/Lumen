<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
// 微信公众号模板消息推送
class WechatController extends Controller
{
    public function sendNews (){

        $data = $this->requests->getQueryParams();
        //发送消息
        $params['data_array'] = array(
            'first' => array(
                'value' => $data['firstData'],
                'color' => $data['firstColor']
            ),
            'keyword1' => array(
                'value' => $data['keyword1Data'],
                'color' => $data['keyword1Color']

            ),
            'keyword2' => array(
                'value' => $data['keyword2Data'],
                'color' => $data['keyword2Color']
            ),
            'keyword3' => array(
                'value' => $data['keyword3Data'],
                'color' => $data['keyword3Color']

            ),
            'keyword4' => array(
                'value' => $data['keyword4Data'],
                'color' => $data['keyword4Color']
            ),
            'remark' => array(
                'value' => $data['remarkData'],
                'color' => $data['remarkColor']
            )
        );
        die('暂未开启');
        
        $userInfo = objectToArray(DB::table('lgp_home_users')->whereNotNull('unionid')->select('unionid','username','openid','mobile')->get());
        $params['url'] = $data['url'];        // 小程序-》跳转地址
        // $params['template_id'] = 'odDwfU4p1n-TH6hga0-4Dwn1vsG-NjjipypADzJTK54';     //微信公众号 模板id
        $params['template_id'] = $data['template_id'];     //微信公众号 模板id
        $success_count = 0;
        $fail_count = 0;
        foreach ($userInfo as $key => $value) {
              $params['unionid'] = $authorInfo['unionid'];    //用户id
              if($params['unionid']){
                    $res = $this->http($params);       //http的方法
                    $res = json_decode($res,true);
                    if($res['code'] == 1){
                            $success_count++; 
                    }else{
                        $fail_count++;
                    }
                }
        }
       

            
       
        return returnJson(2,'生成分享缩略图成功','成功条数:'.$success_count.' 失败条数：'.$fail_count);
       
        
    }

     public function sendNewsTest (){
           $data = $this->requests->getQueryParams();
        //发送消息
        $params['data_array'] = array(
            'first' => array(
                'value' => $data['firstData'],
                'color' => $data['firstColor']
            ),
            'keyword1' => array(
                'value' => $data['keyword1Data'],
                'color' => $data['keyword1Color']

            ),
            'keyword2' => array(
                'value' => $data['keyword2Data'],
                'color' => $data['keyword2Color']
            ),
            'keyword3' => array(
                'value' => $data['keyword3Data'],
                'color' => $data['keyword3Color']

            ),
            'keyword4' => array(
                'value' => $data['keyword4Data'],
                'color' => $data['keyword4Color']
            ),
            'remark' => array(
                'value' => $data['remarkData'],
                'color' => $data['remarkColor']
            )
        );
            
            $params['url'] = $data['url'];        // 小程序-》跳转地址
            $params['template_id'] = $data['template_id'];     //微信公众号 模板id
            $success_count = 0;
            $fail_count = 0;
            // $value['unionid'] = 'oVg9S5lFW6iaxQWVyK9CyZasv8k4';
            $authorInfo =objectToArray(DB::table('user_test')->where('status',1)->get()) ;
            foreach ($authorInfo as $key => $value) {
               $params['unionid'] = $value['unionid'];    //用户id
                if($params['unionid']){
                        $res = $this->http($params);       //http的方法
                        $res = json_decode($res,true);
                        if($res['code'] == 1){
                                $success_count++; 
                        }else{
                            $fail_count++;
                        }
                 
                }
            }
            return returnJson(2,'生成分享缩略图成功','成功条数:'.$success_count.' 失败条数：'.$fail_count);
            
        }


 public function testSendNews (){
           $data = $this->requests->getQueryParams();
        //发送消息
        $params['data_array'] = array(
            'first' => array(
                'value' => '测试标题',
            ),
            'keyword1' => array(
                'value' => '这是课程',
            ),
            'keyword2' => array(
                'value' => '这是地点',
            ),
            'keyword3' => array(
                'value' => '讲师名',
            ),
            'keyword4' => array(
                'value' => '2020年02月17日 16:06',
            ),
            'remark' => array(
                'value' => 'gogo>>>>',
                'color' => '#ef0606',
            )
        );
            
            $params['url'] = '/pages/index/index';        // 小程序-》跳转地址
            $params['template_id'] = 'odDwfU4p1n-TH6hga0-4Dwn1vsG-NjjipypADzJTK54';     //微信公众号 模板id
            $success_count = 0;
            $fail_count = 0;
            $value['unionid'] = 'oVg9S5lFW6iaxQWVyK9CyZasv8k4';
            // $authorInfo =objectToArray(DB::table('user_test')->get()) ;
            // foreach ($authorInfo as $key => $value) {
               $params['unionid'] = $value['unionid'];    //用户id
                if($params['unionid']){
                        $res = $this->http($params);       //http的方法
                        $res = json_decode($res,true);
                        if($res['code'] == 1){
                                $success_count++; 
                        }else{
                            $fail_count++;
                        }
                 
                }
            // }
                var_dump('成功条数:'.$success_count.' 失败条数：'.$fail_count);
            // return returnJson(2,'生成分享缩略图成功','成功条数:'.$success_count.' 失败条数：'.$fail_count);
            
        }




    /**
 * 发送HTTP请求方法
 * @param  string $url    请求URL
 * @param  array  $params 请求参数
 * @param  string $method 请求方法GET/POST
 * @return array  $data   响应数据
 */
protected function http($params, $url = 'http://wx.beliwin.com/index.php/admin/login/tpl', $method = 'POST', $header = array(), $multi = false){
    $opts = array(
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER     => $header
    );
    /* 根据请求类型设置特定参数 */
    switch(strtoupper($method)){
        case 'GET':
            $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
            break;
        case 'POST':
            //判断是否传输文件
            $params = $multi ? $params : http_build_query($params);
            $opts[CURLOPT_URL] = $url;
            $opts[CURLOPT_POST] = 1;
            $opts[CURLOPT_POSTFIELDS] = $params;
            break;
        default:
            throw new Exception('不支持的请求方式！');
    }
    /* 初始化并执行curl请求 */
    $ch = curl_init();
    curl_setopt_array($ch, $opts);
    $data  = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if($error){
        return '请求发生错误：' . $error;
    }
    return  $data;
}
}
