<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Http\Models\Tutor;
use App\Http\Models\Course;
use App\Http\Models\Myask;
use App\Http\Models\Live;
use App\Http\Models\Users;
use App\Http\Models\Follow;
// 课程详情
class LiveController extends Controller
{
    
    /**
     * [details 课程详情]
     * @return [type] [description]
     */
    public function details()
    {

          $param = $this->requests->getQueryParams();
          $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;
          $course_id = empty($param["course_id"]) ? 0 : $param["course_id"] ;
          if(empty($user_id) || empty($course_id)){
               return returnJson(-1,'缺少参数');
          }

          $class = DB::table('lgp_home_course as c') 
                              ->leftJoin('lgp_home_tutor as t', 'c.tutor_id', '=', 't.id')
                              ->leftJoin('lgp_home_education as e', 't.education_id', '=', 'e.id')
                              ->select("t.id as  tutor_id","t.tutor_name",'t.tutor_introduction',"t.tutor_profile","t.tutor_school","t.tutor_major","e.education_name","c.id as class_id","c.class_name","c.class_title_img","c.live_introduction","c.type as class_type","c.live_status",'c.live_time','c.live_end','c.class_video','c.live_video_middle','c.live_video_before','c.live_video_after','t.tutor_follow_count')
                              ->where(['c.id' => $course_id])
                              ->first();
          $class = objectToArray($class);
          $class["tutor_follow_count"] = getNumW($class['tutor_follow_count']);

         $class['is_follow'] = Follow::where(['user_id' => $user_id,'tutor_id'=>$class['tutor_id']])->exists();
          if(empty($class)){
            return returnJson(-1,'数据异常');
          }
          $where = array(
              "user_id" => $user_id,
              "class_id" => $course_id
          );

         $class['is_yuyue'] = true;
         $time = time();
      
         // 课程类型：１点播（小视频）／２直播课程
         if($class['class_type'] == 2){

              // 根据时间判断时间状态  '直播状态: 1 直播前-未预约/2直播中/3直播后/4直播剪辑后/5直播前-已预约',
              if($class["live_time"] < $time  && $time < $class["live_end"]){
                  // 直播中
                   $class["video_path"] = $class["live_video_middle"];
                  if($class["live_status"] != 2){
                      DB::table("lgp_home_course")->where("id", $course_id)->update(["live_status"=>2]);
                  }
                   $class["live_status"] = 2;

              } else if($class['live_time'] >$time ){   // 直播前
                 // $class["video_path"] = $class["live_video_before"];
                  $class["video_path"] = '/pages/video_cet/video_cet?id='.$course_id;
                 if($class["live_status"] != 1){
                      DB::table("lgp_home_course")->where("id", $course_id)->update(["live_status"=>1]);
                  }
                 $class["live_status"] = 1;   //直播前-未预约
                 $class['is_yuyue']  = Live::where([ "user_id" => $user_id, "class_id" => $course_id])->exists();
                  
                 if ($class['is_yuyue']) {
                      $class["live_status"] = 5;  //直播前-已预约
                 }
              // }else if($class['live_end'] < $time && !empty($class["live_video_after"]) && $class["live_status"] != 4){
              }else if($class['live_end'] < $time  && $class["live_status"] != 4){
                // 直播后
              
                 $class["video_path"] = $class["live_video_after"];
                  if($class["live_status"] != 3){
                      DB::table("lgp_home_course")->where("id", $course_id)->update(["live_status"=>3]);
                  }
                     $class["live_status"] = 3;
              }else{
                  $class["video_path"] = $class["class_video"];
                  if($class["live_status"] != 4){
                      DB::table("lgp_home_course")->where("id", $course_id)->update(["live_status"=>4]);
                  }
                  $class["live_status"] = 4;
              }

         }else{
            $class["video_path"] = $class["class_video"];
         }
          unset($class["live_video_before"]);
          unset($class["live_video_middle"]);
          unset($class["live_video_after"]);
          unset($class["class_video"]);

          $user =  objectToArray(Users::where("user_id",$user_id)->first());
          $class["is_mobile"] = false;
          if(is_judge($user)){
              if($user["mobile"]){
                $class["is_mobile"]  = true;
              }
           }
           // 3直播后/4直播剪辑后 增加浏览记录
        if($class["live_status"] == 3 || $class["live_status"] == 4 ){
            $type = 2;
            // １点播（小视频）／２直播课程
            if($class["class_type"] == 2){
              $type = 3;
            }
             $browse = array(
              "user_id" => $user_id,
              "connect_id" => $class["class_id"],
              "type" => $type,
              "add_time" => time()
             );
             // 添加/更新浏览记录
            DB::table('lgp_home_browse')
                  ->updateOrInsert(
                      ['user_id' => $user_id,'connect_id'=>$course_id,'type'=>$type],
                      $browse
                  );
            // 新增播放量
            DB::table('lgp_home_course')->where("id",$course_id)->increment('play_count');
        }

       
        $class["display"] = true;
        $class["speakerTutor"] = '导师信息';
        $class["vieoIntroduction"] = '内容简介';
        $class["classShear"] = '分享';
         return returnJson(2,'success',$class);
    }
    /**
     * [subscribe description]
     * @return [type] [description]
     */
    public function subscribe()
    {
          $param = $this->requests->getQueryParams();
          $data['user_id'] = empty($param["user_id"]) ? 0 : $param["user_id"] ;
          $data['class_id'] = empty($param["class_id"]) ? 0 : $param["class_id"] ;
          $data['is_push'] = 1;
          $data['fromId'] = empty($param["formid"]) ? 0 : $param["formid"] ;
          $data['add_time'] = time();
          $is_exit = objectToArray(DB::table('lgp_home_live_subscribe')->where(['user_id'=>$data["user_id"],'class_id'=>$data["class_id"]])->first());
         if(is_judge($is_exit)){
               $res = DB::table('lgp_home_live_subscribe')->where('id', $is_exit["id"])->update($data);
              if ($res) {
                return returnJson(2,'success',[]);
              }
              return returnJson(-1,'获取失败',[]);
         }else{
             $res = DB::table('lgp_home_live_subscribe')->insert($data);
              if ($res) {
                return returnJson(2,'success',[]);
              }
              return returnJson(-1,'获取失败',[]);
         }
         
    }


     /**
     * [details 3.0课程详情]
     * @return [type] [description]
     */
    public function course_details()
    {

          $param = $this->requests->getQueryParams();
          $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;
          $course_id = empty($param["course_id"]) ? 0 : $param["course_id"] ;
          if(empty($user_id) || empty($course_id)){
               return returnJson(-1,'缺少参数');
          }

          $class = DB::table('lgp_home_course as c') 
                              ->leftJoin('lgp_home_tutor as t', 'c.tutor_id', '=', 't.id')
                              ->leftJoin('lgp_home_education as e', 't.education_id', '=', 'e.id')
                              ->leftJoin('lgp_home_school as s', 't.tutor_school_id', '=', 's.id')
                              ->select("t.id as  tutor_id","t.tutor_name",'t.tutor_introduction',"t.tutor_profile","s.school_name","t.tutor_major","e.education_name","c.id as class_id","c.class_name","c.class_title_img","c.live_introduction","c.type as class_type","c.live_status",'c.live_time','c.live_end','c.class_video','c.live_video_middle','c.live_video_before','c.live_video_after','t.tutor_follow_count')
                              ->where(['c.id' => $course_id])
                              ->first();
          $class = objectToArray($class);
          $class["tutor_follow_count"] = getNumW($class['tutor_follow_count']);
          $class['is_follow'] = Follow::where(['user_id' => $user_id,'tutor_id'=>$class['tutor_id']])->exists();;
          if(empty($class)){
            return returnJson(-1,'数据异常');
          }
          $where = array(
              "user_id" => $user_id,
              "class_id" => $course_id
          );

         $class['is_yuyue'] = true;
         $time = time();
      
         // 课程类型：１点播（小视频）／２直播课程
         if($class['class_type'] == 2){

              // 根据时间判断时间状态  '直播状态: 1 直播前-未预约/2直播中/3直播后/4直播剪辑后/5直播前-已预约',
              if($class["live_time"] < $time  && $time < $class["live_end"]){
                  // 直播中
                   $class["video_path"] = $class["live_video_middle"];
                  if($class["live_status"] != 2){
                      DB::table("lgp_home_course")->where("id", $course_id)->update(["live_status"=>2]);
                  }
                   $class["live_status"] = 2;

              } else if($class['live_time'] >$time ){   // 直播前
                 // $class["video_path"] = $class["live_video_before"];
                  $class["video_path"] = '/pages/video_cet/video_cet?id='.$course_id;
                 if($class["live_status"] != 1){
                      DB::table("lgp_home_course")->where("id", $course_id)->update(["live_status"=>1]);
                  }
                 $class["live_status"] = 1;   //直播前-未预约
                 $class['is_yuyue']  = Live::where([ "user_id" => $user_id, "class_id" => $course_id])->exists();
                  
                 if ($class['is_yuyue']) {
                      $class["live_status"] = 5;  //直播前-已预约
                 }

                  

              // }else if($class['live_end'] < $time && !empty($class["live_video_after"]) && $class["live_status"] != 4){
              }else if($class['live_end'] < $time  && $class["live_status"] != 4){
                // 直播后
              
                 $class["video_path"] = $class["live_video_after"];
                  if($class["live_status"] != 3){
                      DB::table("lgp_home_course")->where("id", $course_id)->update(["live_status"=>3]);
                  }
                     $class["live_status"] = 3;
              }else{
                  $class["video_path"] = $class["class_video"];
                  if($class["live_status"] != 4){
                      DB::table("lgp_home_course")->where("id", $course_id)->update(["live_status"=>4]);
                  }
                  $class["live_status"] = 4;
              }

         }else{
          // 点播课
            $class["video_path"] = $class["class_video"];

            // 随机取3条
              $whereobj = ['c.type'=> 1, 'c.status'=>1,'t.status' =>1];
              $class["course_list"] =objectToArray( DB::table('lgp_home_course as c')
                          ->leftJoin('lgp_home_tutor as t', 'c.tutor_id', '=', 't.id')
                          ->where($whereobj)
                          ->where('c.id' ,'!=' ,$course_id)
                          ->orderBy(DB::raw('RAND()'))
                          ->select('c.class_video','c.id',"c.class_name",'c.class_title_img','forward_count','c.type','c.type','c.praised_count','c.live_time','c.tutor_id','t.tutor_follow_count','t.tutor_name','t.tutor_profile','t.tutor_country')
                         ->limit(3)->get());
              $gz_list = array_column(objectToArray(DB::table('lgp_home_follow')->where('user_id', $user_id)->get(['tutor_id'])),'tutor_id');
              foreach ($class["course_list"] as $key => &$value) {
                  $value['is_follow'] = false;
                  if( in_array($value['tutor_id'], $gz_list) ) {
                      $value['is_follow'] = true;
                  }
                  $value['live_time'] = date("Y-m-d",$value['live_time']);
                  $value['praised_count'] = getNumW( $value['praised_count']);
                  $value['forward_count'] = getNumW( $value['forward_count']);
                  $value['tutor_follow_count'] = getNumW( $value['tutor_follow_count']);
              }

         }
          unset($class["live_video_before"]);
          unset($class["live_video_middle"]);
          unset($class["live_video_after"]);
          unset($class["class_video"]);

          $user =  objectToArray(Users::where("user_id",$user_id)->first());
          $class["is_mobile"] = false;
          if(is_judge($user)){
              if($user["mobile"]){
                $class["is_mobile"]  = true;
              }
           }
           // 3直播后/4直播剪辑后 增加浏览记录
        if($class["live_status"] == 3 || $class["live_status"] == 4 ){
            $type = 2;
            // １点播（小视频）／２直播课程
            if($class["class_type"] == 2){
              $type = 3;
            }
             $browse = array(
              "user_id" => $user_id,
              "connect_id" => $class["class_id"],
              "type" => $type,
              "add_time" => time()
             );
            // 新增播放量
            DB::table('lgp_home_course')->where("id",$course_id)->increment('play_count');
        }

       
        $class["display"] = true;
        $class["speakerTutor"] = '导师信息';
        $class["vieoIntroduction"] = '内容简介';
        $class["classShear"] = '分享';
        
        return returnJson(2,'success',$class);
    }

}
