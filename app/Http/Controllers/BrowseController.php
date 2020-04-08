<?php

namespace App\Http\Controllers;

use App\Http\Models\Mypraise;
use App\Http\Models\Article;
use App\Http\Models\Follow;
use App\Http\Models\Browse;

use Illuminate\Support\Facades\DB;
// 历史浏览
class BrowseController extends Controller
{
    

   
    /**
     * [index 历史浏览]、
     * param:  user_id    用户id
     * @return [type] [description]
     */
    public function index()
    {
        // $time_start = microtime(true);
        $param = $this->requests->getQueryParams();
        $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;
        if(empty($user_id)){
            $res =  returnJson(-1,'缺少参数');
            return $res;
        }
       $where = array("m.user_id"=>$user_id);
         $article =   DB::table('lgp_home_browse as m')->where(function ($query) use($where) {
                            if ($where) {
                                $query->Where($where);
                            }
                    })
                    ->leftJoin('lgp_home_article as a', function ($join) {
                        $join->on('m.connect_id', '=', 'a.id')
                             ->where('m.type', '=', 1)
                             ->where('a.status', '=', 1);
                    })
                   
                    ->leftJoin('lgp_home_course as c', function ($join) {
                        $join->on('m.connect_id', '=', 'c.id')
                             ->where('c.status','=',1)->whereIn('m.type', [2,3]);
                    })
                    ->leftJoin('lgp_home_tutor as t', function ($join) {
                        $join->on( 'a.tutor_id', '=', 't.id')
                            ->orOn('c.tutor_id', '=', 't.id')
                             ->where('t.status','=',1);
                    })
                    ->leftJoin('lgp_home_video as v', function ($join) {
                        $join->on('m.connect_id', '=', 'v.id')
                             ->where('v.status','=',1)
                             ->where('m.type', '=', 4);
                    })
                    ->orderBy("m.add_time",'DESC')
                    ->select('a.id as article_id','m.type', 'a.article_name', 'a.article_img','a.browse_count','a.forward_count  as article_forward_count','a.praised_count as article_praised_count','c.id as course_id','c.class_name', 'c.class_title_img','c.forward_count as course_forward_count','c.praised_count as course_praised_count','c.play_count as course_play_count','c.class_video','v.id as video_id','v.title','m.type','v.img','v.play_count as video_play_count','v.forward_count as video_forward_count','v.praised_count as video_praised_count','v.content','t.tutor_name','t.tutor_follow_count','t.tutor_profile','t.id as tutor_id','a.status as article_status','c.status as course_status','t.status as tutor_status','v.status as video_status')
                    ->paginate(6)->toArray();
             
        $Mybrowse = objectToArray($article["data"]);
        $browse = array();
         foreach ($Mybrowse as $key => $value) {
             if(!empty($value['id']) && !empty($value["type"])){
                 $value["is_praise"] = Mypraise::where(['user_id'=>$user_id,'connect_id'=>$value['id'],'type'=>$value['type']])->exists();
             }
            // 文章
             if($value['type'] == 1 && $value['article_status'] == 1 && $value["tutor_status"] == 1){
                    $value["id"] = $value["article_id"];
                    $value["praised_count"]= $value["article_praised_count"];
                    $value['forward_count'] = $value["article_forward_count"];
                    // 浏览
                    $value['browse_count'] = getNumW( $value['browse_count']);
                     // 转发量
                    $value['forward_count'] = getNumW( $value['forward_count']);
                    // 我的赞
                    $value['praised_count'] =getNumW($value['praised_count']);
                    // 关注导师数量
                    $value['tutor_follow_count'] = getNumW($value['tutor_follow_count']);
                     // 判断是否关注
                     $value["is_follow"]  =   Follow::where(['user_id'=>$user_id,'tutor_id'=>$value['tutor_id']])->exists();
                      if(!empty($value['id']) && !empty($value["type"])){
                         $value["is_praise"] = Mypraise::where(['user_id'=>$user_id,'connect_id'=>$value['id'],'type'=>$value['type']])->exists();
                     }
                    $browse[] = $value;
             }
             // 课程
             if(($value['type'] == 2 || $value['type'] == 3 )&& $value['course_status'] == 1 && $value["tutor_status"] == 1){

                     $value["id"] = $value["course_id"];
                     $value["praised_count"]= $value["course_praised_count"];
                     $value['forward_count'] = $value["course_forward_count"];
                     $value['play_count'] = $value["course_play_count"];
                 // 转发量
                    $value['forward_count'] = getNumW( $value['forward_count']);
                    // 我的赞
                    $value['praised_count'] =getNumW($value['praised_count']);
                     // 关注导师数量
                    $value['tutor_follow_count'] = getNumW($value['tutor_follow_count']);
                    // 判断是否关注
                     $value["is_follow"]  =   Follow::where(['user_id'=>$user_id,'tutor_id'=>$value['tutor_id']])->exists();
                    // 播放量
                    $value['play_count'] = getNumW( $value['play_count']);
                     if(!empty($value['id']) && !empty($value["type"])){
                         $value["is_praise"] = Mypraise::where(['user_id'=>$user_id,'connect_id'=>$value['id'],'type'=>$value['type']])->exists();
                     }
                    $browse[] = $value;
             }
             // 小视频
              if($value['type'] == 4 && $value['video_status'] == 1 ){
                    $value["id"] = $value["video_id"];
                    $value["praised_count"]= $value["video_praised_count"];
                    $value['forward_count'] = $value["video_forward_count"];
                    $value['play_count'] = $value["video_play_count"];
                     // 转发量
                    $value['forward_count'] = getNumW( $value['forward_count']);
                    // 我的赞
                    $value['praised_count'] =getNumW($value['praised_count']);
                    // 播放量
                    $value['play_count'] = getNumW( $value['play_count']);
                     if(!empty($value['id']) && !empty($value["type"])){
                         $value["is_praise"] = Mypraise::where(['user_id'=>$user_id,'connect_id'=>$value['id'],'type'=>$value['type']])->exists();
                     }
                    $browse[] = $value;
             }
         }
            
        
          $data['todayData'] = array();
          $data['historyData'] = $browse;
          $data["last_page"] = $article["last_page"];
            $da = array();
            $da["last_page"] = 0;
            $da["historyData"] = array();
          return returnJson(2,'success',$data);
    }
    
     /**
     * [index 历史浏览]、
     * param:  user_id    用户id
     * @param  type 文章id/课程id/直播id/小视频id
     * @return [type] [description]
     */
    public function addbrowse(){

            $param = $this->requests->getQueryParams();
            $data['user_id'] = empty($param["user_id"]) ? 0 : $param["user_id"] ;
            $data['type'] = empty($param["type"]) ? 0 : $param["type"] ;  //类型
            $data['connect_id'] = empty($param["id"]) ? 0 : $param["id"] ;  //类型
            if(empty($data['user_id']) || empty($data['type']) || empty($data['connect_id'])){
                $res =  returnJson(-1,'缺少参数');
                return $res;
            }
           
            $data["add_time"] = time();
            $res =  DB::table('lgp_home_browse')
                ->updateOrInsert(['user_id' => $data['user_id'],'connect_id'=>$data['connect_id'],'type'=>$data['type']],$data);
            if($res){
                 return returnJson(2,'success');
            }else{
                 return returnJson(-1,'添加浏览失败！');
            }



    }
}
