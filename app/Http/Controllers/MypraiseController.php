<?php

namespace App\Http\Controllers;


use App\Http\Models\Mypraise;
use App\Http\Models\Article;
use App\Http\Models\Follow;
use Illuminate\Support\Facades\DB;


class MypraiseController extends Controller
{
    /**
     * [index 获取我的赞]、
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
          $article =   DB::table('lgp_home_mypraise as m')->where(function ($query) use($where) {
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
            $Mypraise = objectToArray($article["data"]);
            $praise = array();
        // 合并数组
         foreach ($Mypraise as $key => $value) {
              // 文章
             if($value['type'] == 1 && $value['article_status'] == 1 && $value["tutor_status"] == 1){
                    $value["id"] = $value["article_id"];
                    $value["praised_count"]= $value["article_praised_count"];
                    $value['forward_count'] = $value["article_forward_count"];
                    // 判断是否关注
                    $value["is_follow"]  =   Follow::where(['user_id'=>$user_id,'tutor_id'=>$value['tutor_id']])->exists();
                    // 关注导师数量
                    $value['tutor_follow_count'] =getNumW( $value['tutor_follow_count']);
                    // 点赞量
                    $value['praised_count'] = getNumW( $value['praised_count']);
                    // 转发量
                     $value['forward_count'] = getNumW( $value['forward_count']);
                    // 浏览量
                    $value['browse_count'] = getNumW( $value['browse_count']);
                    $praise[] = $value;
             }

              // 课程
             if(($value['type'] == 2 || $value['type'] == 3 )&& $value['course_status'] == 1 && $value["tutor_status"] == 1){
                  $value["id"] = $value["course_id"];
                  $value["praised_count"]= $value["course_praised_count"];
                  $value['forward_count'] = $value["course_forward_count"];
                  $value['play_count'] = $value["course_play_count"];
                  // 判断是否关注
                  $value["is_follow"]  =   Follow::where(['user_id'=>$user_id,'tutor_id'=>$value['tutor_id']])->exists();
                   // 关注导师数量
                  $value['tutor_follow_count'] =getNumW( $value['tutor_follow_count']);
                   // 点赞量
                  $value['praised_count'] = getNumW( $value['praised_count']);
                  // 转发量
                  $value['forward_count'] = getNumW( $value['forward_count']);
                  // 播放量
                  $value['play_count'] =  getNumW( $value['play_count']);
                $praise[] = $value;
            }

             // 小视频
              if($value['type'] == 4 && $value['video_status'] == 1 ){
                  $value["id"] = $value["video_id"];
                  $value["praised_count"]= $value["video_praised_count"];
                  $value['forward_count'] = $value["video_forward_count"];
                  $value['play_count'] = $value["video_play_count"];
                     // 点赞量
                  $value['praised_count'] = getNumW( $value['praised_count']);
                  // 转发量
                  $value['forward_count'] = getNumW( $value['forward_count']);
                  $value['play_count'] =  getNumW( $value['play_count']);
                  $praise[] = $value;
              }
            
         }
              $data = array();
              $data["data"] = $praise;
              $data["last_page"] =  $article["last_page"];

              $da = array();
              $da["last_page"] = 0;
              $da["data"] = array();
          return returnJson(2,'success',$data);
          // return returnJson(2,'success',[]);
    }



     /**
     * [spotpraise  d点赞]、
     * param:  user_id   用户id
     * param:  type     1文章/2点播/3直播/4小视频
     * param:  id       文章id/点播id/直播id/小视频id
     * @return [type] [description]
     */ 
    public function spotpraise(){
        $param = $this->requests->getQueryParams();
        $data['user_id'] = empty($param["user_id"]) ? 0 : $param["user_id"] ;
        $data['type'] = empty($param["type"]) ? 0 : $param["type"] ;  //类型
        $data['connect_id'] = empty($param["id"]) ? 0 : $param["id"] ;  //类型
        if(empty($data['user_id']) || empty($data['type']) || empty($data['connect_id'])){
            $res =  returnJson(-1,'缺少参数');
            return $res;
        }
       
         $is_exit = Mypraise::where($data)->exists();
        if($is_exit){
            $this->addpraiseAction($data['connect_id'],$data['type']);
            return returnJson(2,'success');
        }
         $data["add_time"] = time();
        if(DB::table("lgp_home_mypraise")->insert($data)){
            $this->addpraiseAction($data['connect_id'],$data['type']);
             return returnJson(2,'success');
        }else{
             return returnJson(-1,'点赞失败');
        }

    }

    /**
     * [canclepraise  取消点赞]、
     * param:  user_id   用户id
     * param:  type     1文章/2点播/3直播/4小视频
     * param:  id       文章id/点播id/直播id/小视频id
     * @return [type] [description]
     */ 
    public function canclepraise(){
        $param = $this->requests->getQueryParams();
        $data['user_id'] = empty($param["user_id"]) ? 0 : $param["user_id"] ;
        $data['type'] = empty($param["type"]) ? 0 : $param["type"] ;  //类型
        $data['connect_id'] = empty($param["id"]) ? 0 : $param["id"] ;  //类型
        if(empty($data['user_id']) || empty($data['type']) || empty($data['connect_id'])){
            $res =  returnJson(-1,'缺少参数');
            return $res;
        }
        $is_exit = Mypraise::where($data)->exists();
        if(!$is_exit){
            return returnJson(2,'success');
        }

        if(DB::table("lgp_home_mypraise")->where($data)->delete()){
            $this->cancelpraiseAction($data['connect_id'], $data['type']);
             return returnJson(2,'success');
        }else{
             return returnJson(-1,'点赞失败');
        }

    }
    // ----------3.0版本---------------------------------------
     /**
     * [ addPPraise 添加点赞3.0]
     * param:  user_id   用户id
     * param:  type      1文章/2点播/3直播/4小视频
     * param:  id       文章id/点播id/直播id/小视频id
     * @return [type] [description]
     */ 
     public function addPraise(){
        $param = $this->requests->getQueryParams();
        // $param['id'] = 1;
        // $param['type'] =1;
        // $param['user_id'] = 15;
        if(empty($param['user_id']) || empty($param['type']) || empty($param['id'])){
            $res =  returnJson(-1,'缺少参数');
            return $res;
        }
          $res = false;
         if($param['type'] == 1){ //文章
            $res = DB::table('lgp_home_article')->where("id",$param['id'])->increment('praised_count');
        }else if($param['type'] == 2 || $param['type'] == 3 ){
             $res =DB::table('lgp_home_course')->where("id",$param['id'])->increment('praised_count');

        }else if($param['type'] == 4){
          $res =  DB::table('lgp_home_video')->where("id",$param['id'])->increment('praised_count');
        }
        if($res){
           return returnJson(2,'success');
        }else{
             return returnJson(-1,'操作失败');
        }
    }

    /**
     * [ addPPraise 取消点赞3.0]
     * param:  user_id   用户id
     * param:  type      1文章/2点播/3直播/4小视频
     * param:  id       文章id/点播id/直播id/小视频id
     * @return [type] [description]
     */ 
     public function  removePraise(){
        $param = $this->requests->getQueryParams();
         if(empty($param['user_id']) || empty($param['type']) || empty($param['id'])){
            $res =  returnJson(-1,'缺少参数');
            return $res;
        }
        $res = false;
        if($param['type']  == 1){ //文章
             $res =DB::table('lgp_home_article')->where("id",$param['id'])->where("praised_count",'>',0)->decrement('praised_count');

        }else if($param['type'] == 2 || $param['type'] == 3 ){
            $res = DB::table('lgp_home_course')->where("id",$param['id'])->where("praised_count",'>',0)->decrement('praised_count');

        }else if($param['type'] == 4){
            $res =DB::table('lgp_home_video')->where("id",$param['id'])->where("praised_count",'>',0)->decrement('praised_count');
        }
        if($res){
           return returnJson(2,'success');
        }else{
             return returnJson(-1,'操作失败');
        }
    }

     /**
     * [getPraiseorBrowse  我赞过/浏览 3.0]
     * param:  user_id   用户id
     * param:  type      1文章/2视频
     * param:  id        文章id/视频id
     * @return [type] [description]
     */ 
    public function getPraiseorBrowse(){
        $param = $this->requests->getQueryParams();
        // $param['type'] = 2;
        // $param['user_id'] = 15;
        // $param['id'] = json_encode(array(1,2,3,4));
        if(empty($param['type']) || empty($param['id']) || empty($param['user_id'])){
            $res =  returnJson(-1,'缺少参数');
            return $res;
        }
        $ids = json_decode($param['id'],true);
        $info = array();
        if($param['type'] == 1){ //文章
            $gz_list = array_column(objectToArray(DB::table('lgp_home_follow')->where('user_id', $param['user_id'])->get(['tutor_id'])),'tutor_id');
            $info =  DB::table('lgp_home_article as a')
                        ->leftJoin('lgp_home_tutor as t', 'a.tutor_id', '=', 't.id')
                        // ->where('a.status',1)
                        ->whereIn('a.id',$ids)
                        ->select('a.id', 'article_name', 'article_img','browse_count','forward_count','praised_count','t.tutor_follow_count','t.tutor_profile','t.id as tutor_id','article_content','t.tutor_name')
                        ->paginate(self::$pageNum)->toArray();
            $info = objectToArray($info['data']);
            foreach ( $info as $key => &$value) {
                 if( in_array($value['tutor_id'], $gz_list) ){
                        $value['is_follow'] = true;
                    }else{
                        $value['is_follow'] = false;
                    }
                    $value['article_content'] = strip_tags($value['article_content']);

                    $value['tutor_follow_count'] =getNumW( $value['tutor_follow_count']);// 关注导师数量
                    $value['praised_count'] = getNumW( $value['praised_count']);// 点赞量
                    // $value['forward_count'] = getNumW( $value['forward_count']); // 转发量
                    $value['browse_count'] = getNumW( $value['browse_count']); // 浏览量
            }
        }elseif($param['type'] == 2){ //视频
            $info =   DB::table('lgp_home_course as c')
                        ->leftJoin('lgp_home_tutor as t','c.tutor_id', '=', 't.id')
                        // ->where('c.status',1)
                        ->whereIn('c.id',$ids)
                        ->select('c.id','c.class_name', 'c.type','c.class_title_img','c.forward_count','c.praised_count','c.play_count','c.class_video','t.tutor_name','t.tutor_follow_count','t.tutor_profile','t.id as tutor_id','c.status','t.tutor_name')
                    ->paginate(6)->toArray();
            $info = objectToArray($info['data']);
            foreach ( $info as $key => &$value) {
                    $value['tutor_follow_count'] =getNumW( $value['tutor_follow_count']);// 关注导师数量
                    $value['praised_count'] = getNumW( $value['praised_count']);// 点赞量
                    // $value['forward_count'] = getNumW( $value['forward_count']); // 转发量
                    $value['play_count'] = getNumW( $value['play_count']); // 浏览量
            }

        }

        $data['data'] = $info;
        $data['is_video'] = self::$is_show;
        return returnJson(2,'success',$data);
    
    }



}
