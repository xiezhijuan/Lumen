<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Http\Models\Tutor;
use App\Http\Models\Course;
use App\Http\Models\Myask;
use App\Http\Models\Live;
use App\Http\Models\Users;
use App\Http\Models\Myaskproblem;
// 定时任务
class TimingController extends Controller
{
    
    // 直播定时
    public function pushSubscribe(){
         writelog('========直播定时===============' ,'Timing','pushSubscribe');
         $subscribe = objectToArray(
                      DB::table('lgp_home_live_subscribe as s')
                      ->leftJoin('lgp_home_course as c', 's.class_id', '=', 'c.id')
                      ->leftJoin('lgp_home_users as u', 'u.user_id', '=', 's.user_id')
                       ->select("c.class_name",'c.live_time','c.live_end','u.openid','s.fromId','s.id')
                      ->where('s.is_push',1)
                      ->get()
                    );
          writelog($subscribe ,'Timing','pushSubscribe');
         $time = time();
         foreach ($subscribe as $key => $value) {
            // $value["live_time"] = 1576655226;
          // 获取直播前20分钟的时间戳   （直播前20分钟推送）
            $starttime =  strtotime('-20minute',$value["live_time"]);
            // 判断当前时间大于等于 直播前20分钟  并小于直播开始时间
            if($starttime <= $time && $time <= $value["live_time"] ){
                  $url = 'https://www.highschool86.com/share_formanualTem?openid='.$value['openid']."&fromId=".$value['fromId'].'&type=1';
                  writelog($url ,'Timing','pushSubscribe');
                  $res =  $this->curl_get_https($url);
                  $res = json_decode($res,true);
                  writelog($res ,'Timing','pushSubscribe');
                  if($res['code'] == 2){
                      $time = time();
                      DB::table('lgp_home_live_subscribe')->where('id='.$value['id'])->update(["is_push"=>2,'push_time'=>$time]);
                      // return ['code'=>1,'msg'=>'推送成功!'];
                  }
                  // else{
                  //     // return ['code'=>0,'msg'=>'推送失败!'.$res["message"]]; 
                  // }
            }
                     
         }


    }


      // 咨询定时
    public function pushMyask(){

          $time = time();
          $myask = objectToArray(Myask::where('is_del',1)->where("communication_time",'>',$time)->get());
          // echo "<pre>";
           foreach ($myask as $key => $value) {
                 // 获取预约咨询前20分钟的时间戳   （预约咨询前20分钟推送）
                $starttime =  strtotime('-20minute',$value["communication_time"]);
                 // 判断当前时间大于等于 预约咨询前20分钟  并小于预约咨询开始时间
                if($starttime <= $time && $time <= $value["communication_time"] ){
                      // 判断当前时间段中是否有咨询问题 判断条件为未评价 未处理
                     $myaskproblem = objectToArray(Myaskproblem::where(["myask_id"=>$value["id"],'is_dispose'=>1,'is_evaluate'=>2])->where("communication_time",$value["communication_time"])->select("form_id",'id')->first());
                     $myaskproblem["form_id"] = '3254365';
                     if(!is_judge($myaskproblem)){
                        continue;
                     }
                     if(empty($myaskproblem["form_id"])){
                          continue;
                     }
                     // 查询用户openid
                      $openid =  objectToArray(DB::table('lgp_home_users')->where('user_id' ,$value['user_id'])->value('openid'));
                      if(!is_judge($openid)){
                        continue;
                     }
                      // 推送消息
                       $url = 'https://www.highschool86.com/share_formanualTem?openid='.$openid."&fromId=".$myaskproblem['form_id'].'&type=2';
                       // echo $url;
                        $res =  $this->curl_get_https($url);
                        $res = json_decode($res,true);
                      
                        if($res['code'] == 2){
                            $time = time();
                            DB::table('lgp_home_myask_problem')->where('id='.$myaskproblem['id'])->update(["is_push"=>2,'push_time'=>$time]);
                            // return ['code'=>1,'msg'=>'推送成功!'];
                        }else{
                            // return ['code'=>0,'msg'=>'推送失败!'.$res["message"]]; 
                        }
                }

            }

    } 


     public  function curl_get_https($url){
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        // curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);  // 从证书中检查SSL加密算法是否存在
        $tmpInfo = curl_exec($curl);     //返回api的json对象
        //关闭URL请求
        curl_close($curl);
        return $tmpInfo;    //返回json对象
    }


}
