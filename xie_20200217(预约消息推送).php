<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Http\Models\Video;

use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

// 分享
class ShareController extends Controller
{
    public $getImg = "http://admin.highschool86.com";
    public $inputImg = "https://www.highschool86.com";
  

    
      /**
     * [addSubscribeNews 添加预约消息]
     * @param  string openid      [用户openid]
     * @param  array  fromId      [fromid]
     * @return [type] [description]
     */
    public function addSubscribeNews(){
    	$param = $this->requests->getQueryParams();
        $data["user_id"] = isset($param['user_id']) ? trim($param['user_id']) : ""  ;
        if(empty($user_id)){
            return returnJson(-1,'缺少参数');
        }
        $data['add_time'] = time();
        if(DB::table("lgp_subscription_message")->insert($data)){
             return returnJson(2,'success');
        }else{
             return returnJson(-1,'预约失败');
        }

    }

    /**
     * [subscribeNews 发送订阅消息]
     * @param  string openid      [用户openid]
     * @param  array  fromId      [fromid]
     * @return [type] [description]
     */
    public function subscribeNews()
    {
        $param = $this->requests->getQueryParams();
        $openid = isset($param['openid']) ? trim($param['openid']) : ""  ;
        // $fromId = isset($param['fromId']) ? trim($param['fromId']) : ""  ;
        $type = isset($param['type']) ? intval($param['type']) : ""  ;
        if(empty($openid) ||  empty($type)){
            return returnJson(-1,'缺少参数');
        }
        $data  = '';
         //课程消息推送
        if($type == 1){ 
            if(empty($param["course_id"])){
                 return returnJson(-1,'缺少课程id');
            }
             // $class = objectToArray( 
             //            DB::table("lgp_home_course")
             //            ->leftJoin('lgp_home_tutor as t', 'lgp_home_course.tutor_id', '=', 't.id')
             //             ->select('lgp_home_course.class_name','t.tutor_name','lgp_home_course.live_time','lgp_home_course.live_end')
             //            ->where("lgp_home_course.id",$param["class_id"])->first());
             // if(empty($class)){
             //     return returnJson(-1,'未查到该课程，请核实数据');
             // }

             // if(empty($class["live_time"]) || empty($class["live_end"])){
             //         return returnJson(-1,'课程开始时间或结束时间为空');
             // }  

             // $count =  DB::table("lgp_home_live_subscribe")->where("class_id",$param["class_id"])->count();
             // $date7 = date("Y-m-d",$class["live_time"]);
             // $stime = date('H:i:s',$class["live_time"]);
              // $data = '{
              //     "touser":"'.$openid.'",
              //     "template_id":"6OUkyiLHXLd_4hDFTadAWgtmPDlszdBttRucwDcnJr0",
              //     "form_id":"'.$fromId.'",   
              //     "data": {
              //         "keyword1": {
              //             "value":"课程直播提醒",
              //             "color": "#FF0300"
              //         },
              //         "keyword2": {
              //             "value":"点击进入直播👇👇👇",
              //             "color": "#FD503F"
              //         }
              //     },
              //     "emphasis_keyword": "keyword1.DATA",
              //     "page":"/pages/index/index"
              //   }';

        	$data = '{
				  "touser": "'.$openid.'",
				  "template_id": "TEMPLATE_ID",
				  "page": "index",
				  "miniprogram_state":"developer",
				  "lang":"zh_CN",
				  "data": {
				      "number01": {
				          "value": "339208499"
				      },
				      "date01": {
				          "value": "2015年01月05日"
				      },
				      "site01": {
				          "value": "TIT创意园"
				      } ,
				      "site02": {
				          "value": "广州市新港中路397号"
				      }
				  }
				}';

            
        }
        if(empty($data)){
            return returnJson(-1,'数据为空');
        }

        // $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?';
        // 订阅消息：https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=ACCESS_TOKEN

        $url = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send?';
        $url .= "access_token=" . $this->getToken();

        $result = $this->httpRequest($url,$data);
        $res = json_decode($result,true);
        if($res["errcode"] == 0){
            return returnJson(2,'success');
        }else{
            return returnJson(-1,'推送失败errcode='.$res["errcode"].'errmsg='.$res["errmsg"]);
        }
        return $result;
    }

   

}















