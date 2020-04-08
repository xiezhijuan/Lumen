<?php

namespace App\Http\Controllers;

use App\Http\Models\Mypraise;
use App\Http\Models\Article;
use App\Http\Models\Users;
use App\Http\Models\Follow;
use App\Http\Models\Myask;
use App\Http\Models\Tutor;
use App\Http\Models\Education;
use App\Http\Models\School;
use App\Http\Models\Wenshu;    //文书写作

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Models\MyaskOrder;
class TeacherController extends Controller
{

    /**
     * [index 导师文书详情页]
     * @return [type] [description]
     */
    public function wenshu_detail()
    {
        $post_res = $this->requests->getQueryParams();
        $tutor_id = empty($post_res['tutor_id']) ? '' : $post_res['tutor_id'] ;
        $user_id = empty($post_res['user_id']) ? '' : $post_res['user_id'] ;
        $remark_id = empty($post_res['remark_id']) ? '' : $post_res['remark_id'] ;  //文书id
        $tutor_id = 33;
        $user_id = 15;
        $remark_id =  1;
        if (!$tutor_id || !$user_id || !$remark_id) {
            return returnJson(-1,'参数错误',[]); 
        }
        $tutor_res['tutor'] = objectToArray(DB::table('lgp_home_tutor as t')
                 ->leftJoin('lgp_home_webshu_t as wt', 't.id', '=', 'wt.wenshu_t_tid')
                 ->where(['t.id' => $tutor_id,'wt.wenshu_t_wid'=>$remark_id])
                ->first());
        $tutor_res['tutor']['tutor_label'] = str_replace('，', ',', $tutor_res['tutor']['tutor_label']);
        $tutor_res['tutor']['tutor_label'] = explode(',', $tutor_res['tutor']['tutor_label']);
        $xueli_array = [1=> '本科',2=>'硕士',3=>'博士'];
        $tutor_res['tutor']['education_id'] = $xueli_array[$tutor_res['tutor']['education_id']];
        if (!empty($tutor_res['tutor']['tutor_education_prove'])) {     //判断是否存在
            $tutor_res['tutor']['tutor_education_prove'] = explode(',', $tutor_res['tutor']['tutor_education_prove']);
        }else{
            $tutor_res['tutor']['tutor_education_prove'] = [];
        }
        // 是否关注 
        $tutor_res['is_guanzhu'] = Follow::where(['user_id' => $user_id,'tutor_id'=>$tutor_id ])->exists();
        // 是否预约
        $time = time()- 2*3600;
        $ywhere = array(
            "is_del" => 1,
            "user_id" => $user_id,
            "tutor_id" => $tutor_id 
        );
       
        $tutor_res['is_yuyue'] = Myask::where($ywhere)->where("communication_time",'>',$time)->exists();
         // ----------------------订单 MyaskOrder------------------
        $owhere = array(
            "is_del" => 1,
            "user_id" => $user_id,
            "tutor_id" => $tutor_id,
            'pay_status' =>2, //已支付
            'order_status' =>1 //订单已确认
        );
        $otime = time();
        $tutor_res["is_order"]= MyaskOrder::where($owhere)->where("connect_time",'>=',$otime)->exists();
        $tutor_res['guanzhu_count'] = getNumW($tutor_res['tutor']['tutor_follow_count']);
        $tutor_res['tutor']['tutor_follow_count'] = $tutor_res['guanzhu_count'];
        $tutor_res['bangzhu_count'] = getNumW($tutor_res['tutor']['tutor_help_count']);
        $tutor_res['tutor']['tutor_help_count'] = $tutor_res['bangzhu_count'];
       // 查询导师直播
        $course = objectToArray(DB::table('lgp_home_course') 
                    ->where(['tutor_id'=>$tutor_id,'status'=>1])
                    ->whereIn('live_status',['3','4','0'])
                    ->orderBy('sort','desc')
                    ->get());
        $tutor_res['dianbo'] = [];
        $tutor_res['zhibo'] = [];
        $tutor_res['jianji'] = [];
        foreach ($course as $key => &$value) {
            $mypraise = objectToArray(DB::table("lgp_home_mypraise")->where(["connect_id" => $value['id'],'user_id'=>$user_id])->whereIn('type',['2','3'])->first());
            $value["connect_id"] = 0;
            $value["praisatype"] = 0;
            $value['praised_count'] = getNumW($value['praised_count']);
            $value['forward_count'] = getNumW($value['forward_count']);
            $value['play_count'] = getNumW($value['play_count']);
            if(is_judge($mypraise)){
                $value["connect_id"] = $mypraise["id"];
                $value["praisatype"] = $mypraise["type"];
            }
            if ($value['type'] == 1) {
                $tutor_res['dianbo'][] = $value;
            }elseif ($value['type'] == 2) {
                if ($value['live_status'] == 3) {
                    $tutor_res['zhibo'][] = $value;  
                }elseif ($value['live_status'] == 4) {
                    $tutor_res['jianji'][] = $value;  
                }   
            }
        }
        $tutor_res['wenzhang'] = [];
        // 查询导师文章 
        $tutor_res['wenzhang'] = objectToArray(DB::table('lgp_home_article') ->where(['tutor_id'=>$tutor_id,'status'=>1])->orderBy('sort','desc')->get());
        foreach ($tutor_res['wenzhang'] as $key => &$value) {
                 $value['browse_count'] = getNumW($value['browse_count']);
                 $value['praised_count'] = getNumW($value['praised_count']);
                 $value['forward_count'] = getNumW($value['forward_count']);
        }
        // 课程是否展示 true展示 false不展示
       $tutor_res["is_kecheng"] = true;
        return returnJson(2,'success',$tutor_res);
    }

    /**
     * [index 导师语言详情页]
     * @return [type] [description]
     */
    public function yuyue_detail()
    {
        $post_res = $this->requests->getQueryParams();
        $tutor_id = empty($post_res['tutor_id']) ? '' : $post_res['tutor_id'] ;
        $user_id = empty($post_res['user_id']) ? '' : $post_res['user_id'] ;
        $type_name = empty($post_res['type_name']) ? '' : $post_res['type_name'] ;  //语言id
        $tutor_id = 33;
        $user_id = 15;
        $remark_id =  2;

        if (!$tutor_id || !$user_id || !$remark_id ) {
            return returnJson(-1,'参数错误',[]); 
        }
        $remark_name = '';
        if($remark_id == 1){
            $remark_name  = '托福';
        }else if($remark_id == 2){
            $remark_name  = '雅思';
        }
        // 查询导师 信息
         $tutor_res['tutor'] = objectToArray(DB::table('lgp_home_tutor as t')
                             ->leftJoin('lgp_home_yuyan_t as yt', 't.id', '=', 'yt.yuyan_t_tid')
                             ->where('t.id',$tutor_id)
                             ->where('yt.yuyan_t_name','like','%'.$remark_name.'%')
                            ->first());
        $tutor_res['tutor']['tutor_label'] = str_replace('，', ',', $tutor_res['tutor']['tutor_label']);
        $tutor_res['tutor']['tutor_label'] = explode(',', $tutor_res['tutor']['tutor_label']);
        $xueli_array = [1=> '本科',2=>'硕士',3=>'博士'];
        $tutor_res['tutor']['education_id'] = $xueli_array[$tutor_res['tutor']['education_id']];
        
        if (!empty($tutor_res['tutor']['tutor_education_prove'])) {     //判断是否存在
            $tutor_res['tutor']['tutor_education_prove'] = explode(',', $tutor_res['tutor']['tutor_education_prove']);
        }else{
            $tutor_res['tutor']['tutor_education_prove'] = [];
        }

        // 是否关注 
        $tutor_res['is_guanzhu'] = Follow::where(['user_id' => $user_id,'tutor_id'=>$tutor_id ])->exists();
        // 是否预约
        $time = time()- 2*3600;
        $ywhere = array(
            "is_del" => 1,
            "user_id" => $user_id,
            "tutor_id" => $tutor_id 
        );
       
        $tutor_res['is_yuyue'] = Myask::where($ywhere)->where("communication_time",'>',$time)->exists();
         // ----------------------订单 MyaskOrder------------------
        $owhere = array(
            "is_del" => 1,
            "user_id" => $user_id,
            "tutor_id" => $tutor_id,
            'pay_status' =>2, //已支付
            'order_status' =>1 //订单已确认
        );
        $otime = time();
        $tutor_res["is_order"]= MyaskOrder::where($owhere)->where("connect_time",'>=',$otime)->exists();
        
        // 统计关注老师人数
        $tutor_res['guanzhu_count'] = getNumW($tutor_res['tutor']['tutor_follow_count']);

        $tutor_res['tutor']['tutor_follow_count'] = $tutor_res['guanzhu_count'];


        // 统计老师帮助人数
        $tutor_res['bangzhu_count'] = getNumW($tutor_res['tutor']['tutor_help_count']);
        $tutor_res['tutor']['tutor_help_count'] = $tutor_res['bangzhu_count'];

        
       // 查询导师直播
        $course = objectToArray(DB::table('lgp_home_course') 
                    ->where(['tutor_id'=>$tutor_id,'status'=>1])
                    ->whereIn('live_status',['3','4','0'])
                    ->orderBy('sort','desc')
                    ->get());

        $tutor_res['dianbo'] = [];
        $tutor_res['zhibo'] = [];
        $tutor_res['jianji'] = [];
        
        
        foreach ($course as $key => &$value) {
            $mypraise = objectToArray(DB::table("lgp_home_mypraise")->where(["connect_id" => $value['id'],'user_id'=>$user_id])->whereIn('type',['2','3'])->first());
            $value["connect_id"] = 0;
            $value["praisatype"] = 0;
            $value['praised_count'] = getNumW($value['praised_count']);
            $value['forward_count'] = getNumW($value['forward_count']);
            $value['play_count'] = getNumW($value['play_count']);

            if(is_judge($mypraise)){
                $value["connect_id"] = $mypraise["id"];
                $value["praisatype"] = $mypraise["type"];
            }

            if ($value['type'] == 1) {
                $tutor_res['dianbo'][] = $value;
            }elseif ($value['type'] == 2) {
                if ($value['live_status'] == 3) {
                    $tutor_res['zhibo'][] = $value;  
                }elseif ($value['live_status'] == 4) {
                    $tutor_res['jianji'][] = $value;  
                }   
            }
        }
        $tutor_res['wenzhang'] = [];

        // 查询导师文章 
        $tutor_res['wenzhang'] = objectToArray(DB::table('lgp_home_article') ->where(['tutor_id'=>$tutor_id,'status'=>1])->orderBy('sort','desc')->get());
        foreach ($tutor_res['wenzhang'] as $key => &$value) {
                 $value['browse_count'] = getNumW($value['browse_count']);
                 $value['praised_count'] = getNumW($value['praised_count']);
                 $value['forward_count'] = getNumW($value['forward_count']);
        }
      
        
        // 课程是否展示 true展示 false不展示
       $tutor_res["is_kecheng"] = true;
       $tutor_res["zhibo"] = [];
      
        return returnJson(2,'success',$tutor_res);
    }

    /**
     * [live 文书写作列表页-修改]
     * @return [type] [description]
     */
    public function wenshulist_two()
    {

            $param = $this->requests->getQueryParams();
            $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;
            $country_id = empty($param["country_id"]) ? 0 : $param["country_id"] ;
            $wenshu_page = empty($param["wenshu_page"]) ? 1 : $param["wenshu_page"] ;
            $remark_id =  empty($param["remark_id"]) ? 1 : $param["remark_id"] ;
            $user_id = 15; 
            $country_id = 1;
            $remark_id = 1;

            // 查询导师文书 start
            // $major = empty($param["major"]) ? 'PS' : $param["major"] ;

            if(empty($user_id) || empty($country_id)){
                 return returnJson(-1,'缺少参数');
            }
          


            // 查询导师文书 end
            $where = array(
                    "lgp_home_tutor.status" => 1,//是否展示该导师
                    "lgp_home_tutor.tutor_is_wenshu" => 1,//是否开启文书写作
                    "lgp_home_tutor.tutor_checked_status" => 2,//是否审核通过
                    'lgp_home_tutor.tutor_country' => $country_id,
                );
            if(!empty($param["education"])){    //学历
                $where["lgp_home_tutor.education_id"] = $param["education"];
            }

            // 导师信息
            $tutor = Tutor::Where(function ($query) use($where,$param) {
                        if ($where) {
                            $query->Where($where);
                        }
                        if(isset($param["school"]) && !empty($param["school"])){    //院校
                             $query->Where("lgp_home_tutor.tutor_school" ,'like', "%".$param["school"].'%');
                        }
                    })
                    ->orderBy('lgp_home_tutor.sort','asc')
                    // ->orderByRaw('RAND()')
                    ->leftJoin('lgp_home_education as e', 'education_id', '=', 'e.id')
                    ->leftJoin('lgp_home_webshu_t', 'lgp_home_webshu_t.wenshu_t_tid', '=', 'lgp_home_tutor.id')
                    ->where(['lgp_home_webshu_t.wenshu_t_wid'=>$remark_id])
                    ->select('lgp_home_tutor.tutor_name','lgp_home_tutor.tutor_anli','lgp_home_tutor.tutor_wenshu','lgp_home_tutor.id','lgp_home_tutor.tutor_profile','lgp_home_tutor.tutor_school','lgp_home_tutor.tutor_major','lgp_home_tutor.tutor_price','lgp_home_tutor.tutor_label','lgp_home_tutor.tutor_help_count','lgp_home_tutor.tutor_introduction','e.education_name','lgp_home_webshu_t.wenshu_t_money')
                    ->forPage($wenshu_page,5)->get()->toArray();

                echo "<pre>";
                var_dump($tutor);
                die;
            
            $count =   Tutor::where($where)->count();
            $to_pages=ceil($count/5);


            foreach ($tutor as $key => &$value) {

                if(!empty($value["tutor_label"])){
                    $value["tutor_label"] = str_replace('，', ',', $value["tutor_label"]);
                    $value["tutor_label"] = explode(',', $value["tutor_label"]);
                }

                if(!empty($value["tutor_anli"])){   //案例图
                    $value["tutor_anli"] = str_replace('，', ',', $value["tutor_anli"]);
                    $value["tutor_anli"] = explode(',', $value["tutor_anli"]);
                }
                
                $value["tutor_wenshu"] = json_decode($value['tutor_wenshu'],true);

                $value['tutor_price'] = $value['wenshu_t_money'];

                // foreach($value["tutor_wenshu"] as $wenshu_key => $wenshu_value){
                //     if ($wenshu_value['wenshu_name'] == $major ) {
                        // $value['tutor_price'] = $value['wenshu_t_money'];
                //         break;
                //     }
                // }


                // 判断是否关注
                // $value["is_follow"] = Follow::where(["user_id"=>$user_id,"tutor_id"=>$value["id"]])->exists();
                $value["is_follow"] = true;
                $value["tutor_help_count"] = getNumW($value['tutor_help_count']);
                $value['tutor_profile'] = imgTailor($value['tutor_profile'],100,100);
                
            }

             
            // 学历
            $education = objectToArray(Education::where("status",1)->orderBy("sort")->get());
            // 院校
            $school = objectToArray(School::where(["status"=>1,'country'=>$country_id])->orderBy("sort")->get());
            // 文书类型
            $subject = objectToArray(Wenshu::where("status",1)->orderBy("sort")->get()->toArray());
            
            $educationdemo[] = array('id'=>0 ,'education_name'=>'不限');
            $schooldemo[] = array('id'=>0 ,'school_name'=>'不限','describe'=>'');
            // $subjectdemo[] = array('id'=>0 ,'subject_name'=>'不限');
            $subjectdemo = array();
            $data['education'] =  array_merge($educationdemo,$education);
            $data['school'] =  array_merge($schooldemo,$school);
            $data['subject'] =  array_merge($subjectdemo,$subject);
            $data['tutor']  = $tutor;
            $data['tutor_count'] =  $to_pages;

            // 广告位
             // $data['advert'] = objectToArray(DB::table('lgp_home_advert') ->where('status',1)->orderBy("sort",'desc')->get());

             // 小视频 /pages/Small/Small
            $data['smallVieo'] = '';
            // 精选视频 /pages/Boutique/Boutique
            $data['selectedVieo'] = '';

            $data['title'] =  '学长学姐说';
            // 往期回放
            $data['past'] =  true;
            // 首页底部跳转客服展示
            $data["bottomAdvert"] = true;

            return returnJson(2,'success',$data); 

    }

}
