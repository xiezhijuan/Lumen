<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Http\Models\Course;
use App\Http\Models\Follow;
use App\Http\Models\Mypraise;
// 课程
class CourseController extends Controller
{
    
    /**
     * [index 课程]
     * @return [type] [description]
     */
    public function index()
    {

        $param = $this->requests->getQueryParams();
        $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;
        $country_id = empty($param["country_id"]) ? 0 : $param["country_id"] ;
        if(empty($user_id) || empty($country_id)){
             return returnJson(-1,'缺参数');
        }

        $where = array(
            "lgp_home_course.status" => 1
            );
        // 导师信息
        $course = Course::Where(function ($query) use($where) {
                        if ($where) {
                            $query->Where($where);
                        }
                }) 
                ->orderBy('lgp_home_course.sort','desc')
                ->leftJoin('lgp_home_tutor as t', 'lgp_home_course.tutor_id', '=', 't.id')
                ->leftJoin('lgp_home_users as u', 't.id', '=', 'u.tutor_id')
                ->where("lgp_home_course.type",1)
                ->select('lgp_home_course.class_video','lgp_home_course.id as class_id',"lgp_home_course.class_name",'lgp_home_course.class_title_img','lgp_home_course.type','lgp_home_course.praised_count','lgp_home_course.play_count','lgp_home_course.forward_count','lgp_home_course.tutor_id','t.tutor_name','t.tutor_follow_count','u.user_id','t.tutor_profile')
                ->paginate(5)->toArray();

        foreach ($course["data"] as $key => &$value) {
           $value["is_follow"]  =  Follow::where(["user_id"=>$user_id,"tutor_id"=>$value['tutor_id']])->exists();
            // 是否点赞
           $value["is_praise"] =  Mypraise::where(["user_id"=>$user_id,"connect_id"=>$value["class_id"]])->whereIn('type',[2,3])->exists();
           
            $value['play_count'] = getNumW($value["play_count"]);
            $value['forward_count'] = getNumW($value["forward_count"]);
            $value['praised_count'] = getNumW($value["praised_count"]);
            $value['tutor_follow_count'] = getNumW($value["tutor_follow_count"]);

        }
        // $course["data"] = [];   

        // 判断直播课程状态
        
        
        return returnJson(2,'success',$course);
    }
    /**
     * [list 往期回放]
     * @return [type] [description]
     */
    public function list()
    {
        $course_res = DB::table('lgp_home_course')->where(["status"=>1,'live_status'=>4,'type'=>2])->orderBy("sort",'desc')->get();
        $course_res = objectToArray($course_res);
        
        foreach ($course_res as $key => &$value) {
            $value['live_time'] = date("Y-m-d",$value['live_time']);
        }

        return returnJson(2,'success',$course_res);
        // return returnJson(2,'success',[]);

    }

    

    /**
     * [index 留学情报局]
     * @return [type] [description]
     */
    public function index_video()
    {

        $param = $this->requests->getQueryParams();
        $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;
        $videopage = empty($param["videopage"]) ? 1 : $param["videopage"] ;
        if(empty($user_id) || empty($videopage)){
             return returnJson(-1,'缺参数');
        }

        $where = array(
            "lgp_home_course.status" => 1
            );
        // 导师信息
        $course = Course::Where(function ($query) use($where) {
                        if ($where) {
                            $query->Where($where);
                        }
                }) 
                ->orderBy('lgp_home_course.sort','desc')
                ->leftJoin('lgp_home_tutor as t', 'lgp_home_course.tutor_id', '=', 't.id')
                ->leftJoin('lgp_home_users as u', 't.id', '=', 'u.tutor_id')
                ->where("lgp_home_course.type",1)
                ->select('lgp_home_course.class_video','lgp_home_course.id as class_id',"lgp_home_course.class_name",'lgp_home_course.class_title_img','lgp_home_course.type','lgp_home_course.praised_count','lgp_home_course.play_count','lgp_home_course.forward_count','lgp_home_course.tutor_id','t.tutor_name','t.tutor_follow_count','u.user_id','t.tutor_profile')
                ->paginate(5, ['*'], 'page', $videopage)->toArray();

        foreach ($course["data"] as $key => &$value) {
           $value["is_follow"]  =  Follow::where(["user_id"=>$user_id,"tutor_id"=>$value['tutor_id']])->exists();
        }

        // 判断直播课程状态
        
        $course['class_video'] = '';
        return returnJson(2,'success',$course);
    }

    /**
     * [Play 导师直播播放]
     */
    public function Play()
    {
        $param = $this->requests->getQueryParams();
        $user_id = empty($param["user_id"]) ? '' : $param["user_id"] ;
        $live_id = empty($param["live_id"]) ? '' : $param["live_id"] ;

        if (empty($user_id) || empty($live_id)) {
            return returnJson(-1,'参数错误',[]);
        }

        $course_res = DB::table('lgp_home_course')->where(["id"=>$live_id ,'status'=>1])->first();
        $course_res = objectToArray($course_res);
        
       
        if (empty($course_res)) {
            return returnJson(-1,'没有可用资源',[]);
        }

        if ($course_res['live_status']) {
            if ($course_res['live_status'] == 2) {
                return returnJson(2,'success',['link' => $course_res['live_video_middle'] ]);
            }
            if ($course_res['live_status'] == 3) {
                return returnJson(2,'success',['link' => $course_res['live_video_after'] ]);

            }
        }
    }


}
