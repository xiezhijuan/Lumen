<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Http\Models\Video;
// 转发
class ForwardController extends Controller
{
    
     /**
     * [spotpraise  转发]、
     * param:  user_id   用户id
     * param:  type     1文章/2点播/3直播/4小视频
     * param:  id       文章id/点播id/直播id/小视频id
     * @return [type] [description]
     */ 
    public function index()
    {
        $param = $this->requests->getQueryParams();
        $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;
        $type = empty($param["type"]) ? 0 : $param["type"] ;  //类型
        $id = empty($param["id"]) ? 0 : $param["id"] ;  //类型
        if(empty($user_id) || empty($type) || empty($id)){
            return returnJson(-1,'缺少参数');
        }
        $res = false;
        if( $type == 1){ //文章
           $res =  DB::table('lgp_home_article')->where("id",$id)->increment('forward_count');
        }else if($type == 2 || $type == 3 ){ //点播和直播
             $res =  DB::table('lgp_home_course')->where("id",$id)->increment('forward_count');
        }else if($type == 4){ //小视频
             $res =  DB::table('lgp_home_video')->where("id",$id)->increment('forward_count');
        }

        if($res){
           return returnJson(2,'success');
         }else{
            return returnJson(-1,'转发增加失败');
         }
    }
}
