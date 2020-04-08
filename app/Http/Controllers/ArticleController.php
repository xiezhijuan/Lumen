<?php

namespace App\Http\Controllers;

use App\Http\Models\Mypraise;
use App\Http\Models\Follow;
use App\Http\Models\Article;
use App\Http\Models\Users;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ArticleController extends Controller
{



    /**
     * [index 文章详情页]
     * @return [type] [description]
     */
    public function detail()
    {
         
        // 获取code
        $post_res = $this->requests->getQueryParams();
        $article_id = $post_res['article_id']; 
        $user_id = $post_res['user_id'];
        if (empty($user_id) && empty($article_id)) {
            return returnJson(-1,'参数错误',[]);
        }

        $article = DB::table('lgp_home_article as a')->where(function ($query) use($article_id) {
                            if ($article_id) {
                                $query->where(['a.id'=>$article_id,'a.status'=> 1]);
                            }
                    }) 
             ->leftJoin('lgp_home_tutor as t', function ($join) {
                        $join->on('t.id', '=','a.tutor_id');
                    })
            ->leftJoin('lgp_home_mypraise as m', function ($join) {
                        $join->on('m.connect_id', '=', 'a.id')
                             ->where('m.type', '=', 1);
                    })
            ->select('a.*','t.tutor_name','t.tutor_profile','t.tutor_follow_count','m.id as mypraise_id')
            ->first();
        if (empty($article)) {
            return returnJson(-1,'非法访问',[]);
        }
     
         $article->is_zan = is_judge($article->mypraise_id);
         $article->is_guanzhu = Follow::where(['user_id' => $user_id,'tutor_id'=>$article->tutor_id])->exists();
         // 关注导师数
        $article->tutor_follow_count =getNumW($article->tutor_follow_count);
        // 浏览量
         $article->browse_count = getNumW($article->browse_count);
        // 点赞量
         $article->praised_count = getNumW($article->praised_count);
         $article->forward_count = getNumW($article->forward_count);
           
        // 增加浏览量
        DB::table('lgp_home_article')->where("id",$article_id)->increment('browse_count');

        //保存用户浏览记录
        // DB::table('lgp_home_browse')->insert(['user_id'=>$user_id,'connect_id'=>$article_id,'type'=>1,'add_time' => time()]);
        $browse["user_id"] =  $user_id;
        $browse["connect_id"] =  $article_id;
        $browse["type"] =  1;
        $browse["add_time"] =  time();
        
        // 保存用户
        DB::table('lgp_home_browse')
            ->updateOrInsert(
                ['user_id' => $user_id,'connect_id'=>$article_id,'type'=>1],
                $browse
            );
        $tutor_res['article'] = $article;
        return returnJson(2,'success',$tutor_res);

    }

      /**
     * [tutorArticle 3.0 文章详情]
     * @param string article_id   文章id
     * @param string user_id   用户id
     * @return [type] [description]
     */
      public function article_detail(){
            $param = $this->requests->getQueryParams();
            $article_id = empty($param["article_id"]) ? 0 : $param["article_id"];
            $user_id = empty($param["user_id"]) ? 0 : $param["user_id"];
            if (empty($user_id) && empty($article_id)) {
                return returnJson(-1,'参数错误',[]);
            }

            $article = DB::table('lgp_home_article as a')
                 ->leftJoin('lgp_home_tutor as t','t.id', '=','a.tutor_id')
                  ->leftJoin('lgp_home_education as e', 't.education_id', '=', 'e.id')
                  ->leftJoin('lgp_home_school as s', 't.tutor_school_id', '=', 's.id')
                 ->select('a.*','t.tutor_name','t.tutor_profile','t.tutor_follow_count','s.school_name','e.education_name','t.tutor_major')
                 ->where('a.id',$article_id)
                 ->first();
            if (empty($article)) {
                return returnJson(-1,'非法访问',[]);
            }
             $article->is_follow = Follow::where(['user_id' => $user_id,'tutor_id'=>$article->tutor_id])->exists();
             // 关注导师数
             $article->tutor_follow_count =getNumW($article->tutor_follow_count);
            // 浏览量
             $article->browse_count = getNumW($article->browse_count);
            // 点赞量
             $article->praised_count = getNumW($article->praised_count);
             $article->forward_count = getNumW($article->forward_count);
              // 增加浏览量
             DB::table('lgp_home_article')->where("id",$article_id)->increment('browse_count');
             $data['article'] = $article;
             return returnJson(2,'success',$data);
      }
}
