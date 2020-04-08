<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Http\Models\Tutor;
use App\Http\Models\Course;
use App\Http\Models\Myask;
use App\Http\Models\Live;
use App\Http\Models\Users;
use App\Http\Models\Follow;
header('Access-Control-Allow-Origin:https://m.beliwin.com'); // *代表允许任何网址请求
// 课程详情
class BroadcastController extends Controller
{
    
    /**
    *直播用户注册 
    *@param  personal_background  个人背景
    *@param  grade   目前年级
    *@param  is_mechanism  是否计划找留学机构
    *@param  WeChat  微信
    *@return  
    */
   public  function addBroadcast(){
       $param = $this->requests->getQueryParams();
       unset($param['s']);
       // $param['personal_background'] = 'personal_background';
       // $param['grade'] = 'grade';
       // $param['is_mechanism'] = 'is_mechanism';
       // $param['WeChat'] = 'WeChat';
       if(empty($param['personal_background']) || empty($param['grade']) || empty($param['is_mechanism']) || empty($param['WeChat'])){
         return returnJson(-1,'所填信息为空！'); 
       }
       $param['add_time'] = time();
       $id = DB::table("live_broadcast")->insertGetId($param);
       if($id){
          return returnJson(2,'success');
       }else{
         return json_msg(-2,'订单生成失败');
       }
   }

   
}