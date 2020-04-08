<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Http\Models\Video;
// 小视频
class VideoController extends Controller
{
    
    /**
     * [live 小视频]
     * @return [type] [description]
     */
    public function index()
    {

            $param = $this->requests->getQueryParams();
            $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;
            if(empty($user_id)){
                 return returnJson(-1,'缺少参数');
            }
            
              // 小视频信息
            $video = Video::Where("status",1)->orderBy('sort','desc')->paginate(5)->toArray();
            foreach ($video["data"] as $key => &$value) {
                // 是否点赞
               $is_praise =  DB::table("lgp_home_mypraise")->where(["user_id"=>$user_id,"connect_id"=>$value["id"],'type'=>4])->exists();
                if($is_praise){
                  $value["is_praise"]  = true;
               }else{
                  $value["is_praise"] = false;
               }
                $value['play_count'] = getNumW($value["play_count"]);
                $value['forward_count'] = getNumW($value["forward_count"]);
                $value['praised_count'] = getNumW($value["praised_count"]);
         } 
         
         return returnJson(2,'success',$video);
    }



}
