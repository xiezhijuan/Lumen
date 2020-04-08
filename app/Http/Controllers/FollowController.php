<?php

namespace App\Http\Controllers;

use App\Http\Models\Tutor;
use App\Http\Models\Follow;
use Illuminate\Support\Facades\DB;
// 我的关注
class FollowController extends Controller
{
    

    /**
     * [index 我的关注]、
     * param:  user_id    用户id
     * @return [type] [description]
     */
    public function index()
    {
        $param = $this->requests->getQueryParams();
        $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;
        if(empty($user_id)){
            $res =  returnJson(-1,'缺少参数');
            return $res;
        }
       
      $where = array(
                "lgp_home_follow.user_id" => $user_id,
                );
       $follows = Follow::Where(function ($query) use($where) {
                if ($where) {
                    $query->Where($where);
                }
        }) 
        ->orderBy('lgp_home_follow.add_time','desc')
        ->leftJoin('lgp_home_tutor as t', 'tutor_id', '=', 't.id')
        ->where("t.status",1)
        ->select('lgp_home_follow.*','t.tutor_name','t.id','t.tutor_profile','t.tutor_school','t.tutor_major','t.tutor_label','t.tutor_help_count','t.tutor_introduction')
        ->paginate(4)->toArray();
        $follow = $follows["data"];
        foreach ($follow as $key => &$value) {
           if(!empty($value["tutor_label"])){
                  $value["tutor_label"] = explode(',', $value["tutor_label"]);
                }

            if(intval($value['tutor_help_count'])/10000 >=1 ){ //过万
                 $value['tutor_help_count'] = getNumW($value['tutor_help_count']);
              
            }

            unset($value["user_id"]);
        }
            $data = array();
            $data["data"] = $follow;
            $data["last_page"] = $follows["last_page"];
          return returnJson(2,'success',$data);
    }

 /**
     * [addFollow 关注]、
     * param:  user_id    用户id
     * param:  tutor_id   导师id
     * @return [type] [description]
     */
    public function addFollow(){
        $param = $this->requests->getQueryParams();
        $data['user_id'] = empty($param["user_id"]) ? 0 : $param["user_id"] ;
        $data['tutor_id'] = empty($param["tutor_id"]) ? 0 : $param["tutor_id"] ;
        if(!is_judge($data['user_id'])|| !is_judge($data['tutor_id'])){
            $res =  returnJson(-1,'缺少参数');
            return $res;
        }
        $is_exit = Follow::where($data)->exists();
        if($is_exit){
            return returnJson(2,'success');
        }
         $data["add_time"] = time();
        if(DB::table("lgp_home_follow")->insert($data)){
            // 操作导师专注表
              $this->addFollowTutor($data['tutor_id']);
             return returnJson(2,'success');
        }else{
             return returnJson(-1,'点赞失败');
        }
    }
 /**
     * [cancelFollow 取消关注]、
     * param:  user_id    用户id
     * param:  tutor_id   导师id
     * @return [type] [description]
     */
    public function cancelFollow(){
        $param = $this->requests->getQueryParams();
        $data['user_id'] = empty($param["user_id"]) ? 0 : $param["user_id"] ;
        $data['tutor_id'] = empty($param["tutor_id"]) ? 0 : $param["tutor_id"] ;
        if(!is_judge($data['user_id'])|| !is_judge($data['tutor_id'])){
            $res =  returnJson(-1,'缺少参数');
            return $res;
        }
        $is_exit = Follow::where($data)->exists();
        if(!$is_exit){
            return returnJson(2,'success');
        }
        if(DB::table("lgp_home_follow")->where($data)->delete()){

             $this->reduceFollowTutor($data['tutor_id']);
             return returnJson(2,'success');
        }else{
             return returnJson(-1,'点赞失败');
        }

    }


    /**
     * [addInc  转发自增]
     * param:  id   文章id/点播id/直播id/小视频id
     * param:  type     1文章/2点播/3直播/4小视频
     * @return [type] [description]
     */ 
    public function addInc(){
        $param = $this->requests->getQueryParams();
        $id = empty($param["id"]) ? 0 : $param["id"] ;
        $type = empty($param["type"]) ? 0 : $param["type"] ;  //类型
        if(empty($id) || empty($type)){
            $res =  returnJson(-1,'缺少参数');
            return $res;
        }
       if($type == 1){ //文章
             DB::table('lgp_home_article')->where("id",$id)->where("forward_count",'>',0)->decrement('forward_count');

        }else if($type == 2 || $type == 3 ){
             DB::table('lgp_home_course')->where("id",$id)->where("forward_count",'>',0)->decrement('forward_count');

        }else if($type == 4){
            DB::table('lgp_home_video')->where("id",$id)->where("forward_count",'>',0)->decrement('forward_count');
        }

    }
}
