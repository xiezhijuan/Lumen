<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Http\Models\Banner;
use App\Http\Models\Education;
use App\Http\Models\School;
use App\Http\Models\Subject;
use App\Http\Models\Wenshu;    //文书写作
use App\Http\Models\Tutor;
use App\Http\Models\Follow;
use App\Http\Models\Users;
use Illuminate\Http\Request;
use App\Http\Models\Course;
use App\Http\Models\Mypraise;


header("Content-Type: text/html;charset=utf-8");
// 课程
class IndexController extends Controller
{
 

    /**
     * [live 直播课]
     * @return [type] [description]
     */
    public function index()
    {
            

            $param = $this->requests->getQueryParams();
            $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;
            $country_id = empty($param["country_id"]) ? 0 : $param["country_id"] ;
            if(empty($user_id) || empty($country_id)){
                 return returnJson(-1,'缺少参数');
            }
            $where = array(
                "lgp_home_tutor.status" => 1,//是否展示该导师
                "lgp_home_tutor.tutor_checked_status" => 2,//是否审核通过
                'lgp_home_tutor.tutor_country' => $country_id
                );
            // 导师信息
            $tutor = Tutor::Where(function ($query) use($where) {
                            if ($where) {
                                $query->Where($where);
                            }
                    }) 
                    ->orderBy('lgp_home_tutor.sort','asc')
                    // ->orderByRaw('RAND()')
                    ->leftJoin('lgp_home_education as e', 'education_id', '=', 'e.id')
                    ->select('lgp_home_tutor.tutor_name','lgp_home_tutor.id','lgp_home_tutor.tutor_profile','lgp_home_tutor.tutor_school','lgp_home_tutor.tutor_major','lgp_home_tutor.tutor_price','lgp_home_tutor.tutor_label','lgp_home_tutor.tutor_help_count','lgp_home_tutor.tutor_introduction','e.education_name')
                    ->take(5)->get()->toArray();

            $count =   Tutor::where($where)->count();
            $to_pages=ceil($count/5);


            foreach ($tutor as $key => &$value) {
                if(!empty($value["tutor_label"])){
                    $value["tutor_label"] = str_replace('，', ',', $value["tutor_label"]);
                    $value["tutor_label"] = explode(',', $value["tutor_label"]);
                }
                // 判断是否关注
                $value["is_follow"] = Follow::where(["user_id"=>$user_id,"tutor_id"=>$value["id"]])->exists();
                $value["tutor_help_count"] = getNumW($value['tutor_help_count']);
                $value['tutor_profile'] = imgTailor($value['tutor_profile'],100,100);
                
            }

             // 轮播查询
            $time = time();
            $data['banner'] =  Banner::where("status",1)
                ->where("start_time",'<=', $time )
                ->where("end_time",'>=',$time)
                ->orderBy("sort")
                ->get()
                ->toArray();
            $mobile = DB::table('lgp_home_users') ->where(['user_id' => $user_id])->value('mobile');    //查询用户

            foreach ($data['banner'] as $key => &$value) {
                
                if ($value['course_id']) {
                    
                    if ($mobile) {  //判断是否授权手机号
                        
                        $live_status = DB::table('lgp_home_course') ->where(['id' => $value['course_id']])->value('live_status');    //判断当前课程状态
                        switch ($live_status) {
                            case '1':
                                // 直播前
                                $value['path'] = '/pages/video_cet/video_cet?id='.$value['course_id'];
                                break;
                            case '2':
                                // 直播
                                $value['path'] = '/pages/webview/webview?live_id='.$value['course_id'];
                                break;
                            case '3':
                                // 回放
                                $value['path'] = '/pages/webview/webview?live_id='.$value['course_id'];
                                break;
                            case '4':
                                // 剪辑
                                $value['path'] = '/pages/video/video?id='.$value['course_id'];
                                break;
                        }

                    }else{

                        $value['path'] = '/pages/video_cet/video_cet?id='.$value['course_id'];
                    }
                }
            }
            // 学历
            $education = objectToArray(Education::where("status",1)->orderBy("sort")->get());
            // 院校
            $school = objectToArray(School::where(["status"=>1,'country'=>$country_id])->orderBy("sort")->get());
            // 学科
            $subject = objectToArray(Subject::where("status",1)->orderBy("sort")->get()->toArray());
            
            $educationdemo[] = array('id'=>0 ,'education_name'=>'不限');
            $schooldemo[] = array('id'=>0 ,'school_name'=>'不限','describe'=>'');
            $subjectdemo[] = array('id'=>0 ,'subject_name'=>'不限');
            $data['education'] =  array_merge($educationdemo,$education);
            $data['school'] =  array_merge($schooldemo,$school);
            $data['subject'] =  array_merge($subjectdemo,$subject);
            $data['tutor']  = $tutor;
            $data['tutor_count'] =  $to_pages;

            // 广告位
             $data['advert'] = objectToArray(DB::table('lgp_home_advert') ->where('status',1)->orderBy("sort",'desc')->get());

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


    /**
     * [live 总搜索]
     * @return [type] [description]
     */
    public function sousou()
    {
        $param = $this->requests->getQueryParams();
        $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;
        $sou_type = empty($param["sou_type"]) ? 0 : $param["sou_type"] ;    //1.导师咨询 2.文书写作 3.语言培训
        $sou_content = empty($param["sou_content"]) ? '' : $param["sou_content"] ;    
        $country_id = empty($param["country_id"]) ? '' : $param["country_id"] ;    //国家
        if(empty($user_id) || empty($sou_type) || empty($country_id)){
                 return returnJson(-1,'缺少参数');
        }

        if ($sou_type == 1) {   //搜索导师
            $where = array(
                "lgp_home_tutor.status" => 1,//是否展示该导师
                "lgp_home_tutor.tutor_checked_status" => 2, //是否审核通过
                'lgp_home_tutor.tutor_country' => $country_id
                );

            // 导师信息
            $tutor = Tutor::Where(function ($query) use($where,$param) {
                            if ($where) {
                                $query->Where($where);
                            }
                            if(isset($param["sou_content"]) && !empty($param["sou_content"])){
                                 $query->Where("lgp_home_tutor.tutor_name" ,'like', "%".$param["sou_content"].'%');
                            }
                            
                    }) 
                    ->orderBy('lgp_home_tutor.sort','asc')
                    ->leftJoin('lgp_home_education as e', 'education_id', '=', 'e.id')
                    ->select('lgp_home_tutor.tutor_name','lgp_home_tutor.tutor_price','lgp_home_tutor.id','lgp_home_tutor.tutor_profile','lgp_home_tutor.tutor_school','lgp_home_tutor.tutor_major','lgp_home_tutor.tutor_label','lgp_home_tutor.tutor_help_count','lgp_home_tutor.tutor_introduction','e.education_name')
                    ->paginate(5)->toArray();

             foreach ($tutor["data"] as $key => &$value) {
                if(!empty($value["tutor_label"])){
                  $value["tutor_label"] = str_replace('，', ',', $value["tutor_label"]);
                  $value["tutor_label"] = explode(',', $value["tutor_label"]);
                }
                // 判断是否关注
                // $value["is_follow"] = Follow::where(["user_id"=>$user_id,"tutor_id"=>$value["id"]])->exists();
                // $value['tutor_profile'] = imgTailor($value['tutor_profile'],100,100);
                $value["tutor_help_count"] = getNumW($value['tutor_help_count']);

            }
            
            return returnJson(2,'success',$tutor); 
        }elseif ($sou_type == 2) {     //搜索文书写作
            // 先查询 文书写作
            $wenshu_id_res = DB::table('lgp_home_wenshu')->where("wenshu_name" ,'like', "%".$param["sou_content"].'%')->select('wenshu_id')->take(5)->get()->toArray();
            $wenshu_id_res = array_column($wenshu_id_res,'wenshu_id');

            if (empty($wenshu_id_res)) {
                if (empty($tutor["data"])) {
                    $tutor = [
                        'current_page'=> 1,
                        'data'=>[],
                        'first_page_url'=> 'page=1',
                        'from' => null,
                        'last_page' => 1,
                        'last_page_url' => 'page=1',
                        'next_page_url' => null,
                        'path' => 'https://www.highschool86.com/index_sousou',
                        'per_page' => 5,
                        'prev_page_url' => null,
                        'to' => null,
                        'total' => 0
                    ];
                }
                return returnJson(2,'success',$tutor);
            }

            // 文书和导师 中间表查询
            $tutor_id_res = DB::table('lgp_home_webshu_t')
                ->whereIn('wenshu_t_wid',$wenshu_id_res)
                ->groupBy('wenshu_t_tid')
                ->select('wenshu_t_tid')
                ->take(5)->get()->toArray();

            if (empty($tutor_id_res)) {
                return returnJson(2,'success',[]);
            }
            $tutor_id_res = array_column($tutor_id_res,'wenshu_t_tid');

            // 查询导师
            $where = array(
                "lgp_home_tutor.status" => 1,//是否展示该导师
                "lgp_home_tutor.tutor_checked_status" => 2, //是否审核通过
                'lgp_home_tutor.tutor_country' => $country_id
                );

            // 导师信息
            $tutor = Tutor::Where(function ($query) use($where,$param,$tutor_id_res) {
                        if ($where) {
                            $query->Where($where);
                        }
                        $query->whereIn('lgp_home_tutor.id',$tutor_id_res);

                    }) 
                    ->orderBy('lgp_home_tutor.sort','asc')
                    ->leftJoin('lgp_home_education as e', 'education_id', '=', 'e.id')
                    ->select('lgp_home_tutor.tutor_name','lgp_home_tutor.tutor_price','lgp_home_tutor.id','lgp_home_tutor.tutor_profile','lgp_home_tutor.tutor_school','lgp_home_tutor.tutor_major','lgp_home_tutor.tutor_label','lgp_home_tutor.tutor_help_count','lgp_home_tutor.tutor_introduction','e.education_name')
                    ->paginate(5)->toArray();

            foreach ($tutor["data"] as $key => &$value) {
                if(!empty($value["tutor_label"])){
                  $value["tutor_label"] = str_replace('，', ',', $value["tutor_label"]);
                  $value["tutor_label"] = explode(',', $value["tutor_label"]);
                }
                // 判断是否关注
                // $value["is_follow"] = Follow::where(["user_id"=>$user_id,"tutor_id"=>$value["id"]])->exists();
                // $value['tutor_profile'] = imgTailor($value['tutor_profile'],100,100);
                $value["tutor_help_count"] = getNumW($value['tutor_help_count']);

            }
            
            
            
            return returnJson(2,'success',$tutor); 
        }elseif ($sou_type == 3) {     //搜索语言培训
            // 先查询 文书写作
            $tutor_id_res = DB::table('lgp_home_yuyan_t')
                ->where("yuyan_t_name" ,'like', "%".$param["sou_content"].'%')
                ->groupBy('yuyan_t_tid')
                ->select('yuyan_t_tid')
                ->take(5)
                ->get()
                ->toArray();

            if (empty($tutor_id_res)) {
                if (empty($tutor["data"])) {
                    $tutor = [
                        'current_page'=> 1,
                        'data'=>[],
                        'first_page_url'=> 'page=1',
                        'from' => null,
                        'last_page' => 1,
                        'last_page_url' => 'page=1',
                        'next_page_url' => null,
                        'path' => 'https://www.highschool86.com/index_sousou',
                        'per_page' => 5,
                        'prev_page_url' => null,
                        'to' => null,
                        'total' => 0
                    ];
                }

                return returnJson(2,'success',$tutor);
            }
            $tutor_id_res = array_column($tutor_id_res,'yuyan_t_tid');

            // 查询导师
            $where = array(
                "lgp_home_tutor.status" => 1,//是否展示该导师
                "lgp_home_tutor.tutor_checked_status" => 2, //是否审核通过
                'lgp_home_tutor.tutor_country' => $country_id
                );

            // 导师信息
            $tutor = Tutor::Where(function ($query) use($where,$param,$tutor_id_res) {
                        if ($where) {
                            $query->Where($where);
                        }
                        $query->whereIn('lgp_home_tutor.id',$tutor_id_res);

                    }) 
                    ->orderBy('lgp_home_tutor.sort','asc')
                    ->leftJoin('lgp_home_education as e', 'education_id', '=', 'e.id')
                    ->select('lgp_home_tutor.tutor_name','lgp_home_tutor.tutor_price','lgp_home_tutor.id','lgp_home_tutor.tutor_profile','lgp_home_tutor.tutor_school','lgp_home_tutor.tutor_major','lgp_home_tutor.tutor_label','lgp_home_tutor.tutor_help_count','lgp_home_tutor.tutor_introduction','e.education_name')
                    ->paginate(5)->toArray();

             foreach ($tutor["data"] as $key => &$value) {
                if(!empty($value["tutor_label"])){
                  $value["tutor_label"] = str_replace('，', ',', $value["tutor_label"]);
                  $value["tutor_label"] = explode(',', $value["tutor_label"]);
                }
                // 判断是否关注
                // $value["is_follow"] = Follow::where(["user_id"=>$user_id,"tutor_id"=>$value["id"]])->exists();
                // $value['tutor_profile'] = imgTailor($value['tutor_profile'],100,100);
                $value["tutor_help_count"] = getNumW($value['tutor_help_count']);

            }
            
            
            
            return returnJson(2,'success',$tutor); 
        }



    }

    

    /**
     * [live 导师咨询列表页]
     * @return [type] [description]
     */
    public function zixun()
    {
            

            $param = $this->requests->getQueryParams();
            $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;
            $country_id = empty($param["country_id"]) ? 0 : $param["country_id"] ;
            if(empty($user_id) || empty($country_id)){
                 return returnJson(-1,'缺少参数');
            }
            $where = array(
                "lgp_home_tutor.status" => 1,//是否展示该导师
                "lgp_home_tutor.tutor_checked_status" => 2,//是否审核通过
                'lgp_home_tutor.tutor_country' => $country_id
                );
            // 导师信息
            $tutor = Tutor::Where(function ($query) use($where) {
                            if ($where) {
                                $query->Where($where);
                            }
                    }) 
                    ->orderBy('lgp_home_tutor.sort','asc')
                    // ->orderByRaw('RAND()')
                    ->leftJoin('lgp_home_education as e', 'education_id', '=', 'e.id')
                    ->select('lgp_home_tutor.tutor_name','lgp_home_tutor.id','lgp_home_tutor.tutor_profile','lgp_home_tutor.tutor_school','lgp_home_tutor.tutor_major','lgp_home_tutor.tutor_price','lgp_home_tutor.tutor_label','lgp_home_tutor.tutor_help_count','lgp_home_tutor.tutor_introduction','e.education_name')
                    ->take(5)->get()->toArray();

            $count =   Tutor::where($where)->count();
            $to_pages=ceil($count/5);


            foreach ($tutor as $key => &$value) {
                if(!empty($value["tutor_label"])){
                    $value["tutor_label"] = str_replace('，', ',', $value["tutor_label"]);
                    $value["tutor_label"] = explode(',', $value["tutor_label"]);
                }
                // 判断是否关注
                $value["is_follow"] = true;
                // $value["is_follow"] = Follow::where(["user_id"=>$user_id,"tutor_id"=>$value["id"]])->exists();
                $value["tutor_help_count"] = getNumW($value['tutor_help_count']);
                $value['tutor_profile'] = imgTailor($value['tutor_profile'],100,100);
                
            }

            
            // 学历
            $education = objectToArray(Education::where("status",1)->orderBy("sort")->get());
            // 院校
            $school = objectToArray(School::where(["status"=>1,'country'=>$country_id])->orderBy("sort")->get());
            // 学科
            $subject = objectToArray(Subject::where("status",1)->orderBy("sort")->get()->toArray());
            
            $educationdemo[] = array('id'=>0 ,'education_name'=>'不限');
            $schooldemo[] = array('id'=>0 ,'school_name'=>'不限','describe'=>'');
            $subjectdemo[] = array('id'=>0 ,'subject_name'=>'不限');
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
    /**
     * [live 文书写作列表页-修改]
     * @return [type] [description]
     */
    public function wenshulist()
    {

            $param = $this->requests->getQueryParams();
            $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;
            $country_id = empty($param["country_id"]) ? 0 : $param["country_id"] ;
            $wenshu_page = empty($param["wenshu_page"]) ? 1 : $param["wenshu_page"] ;

            // 查询导师文书 start
            $major = empty($param["major"]) ? 'PS' : $param["major"] ;

            if(empty($user_id) || empty($country_id)){
                 return returnJson(-1,'缺少参数');
            }
            $wenshu_id_res = '';
            $wenshu_id_res = DB::table('lgp_home_wenshu')->where("wenshu_name" ,'like', "%".$major.'%')->select('wenshu_id')->first();
            $wenshu_id_res = objectToArray($wenshu_id_res);
            $tutor_id_res = [];
            if (!empty($wenshu_id_res['wenshu_id'])) {  //判断是否查询到对应的文书
                $wenshu_id_res = $wenshu_id_res['wenshu_id'];
                if (!empty($wenshu_id_res)) {
                    // 文书和导师 中间表查询
                    $tutor_id_res = DB::table('lgp_home_webshu_t')
                        ->where('wenshu_t_wid',$wenshu_id_res)
                        ->groupBy('wenshu_t_tid')
                        ->select('wenshu_t_tid')
                        ->take(5)->get()->toArray();
                    if (!empty($tutor_id_res)) {
                        $tutor_id_res = array_column($tutor_id_res,'wenshu_t_tid');
                    }
                }
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
            $tutor = Tutor::Where(function ($query) use($where,$param,$major,$tutor_id_res,$wenshu_id_res) {
                        $where = array(
                            'lgp_home_webshu_t.wenshu_t_wid' => $wenshu_id_res, //连表查询
                        );
                        if ($where) {
                            $query->Where($where);
                        }
                        
                        if(isset($param["school"]) && !empty($param["school"])){    //院校
                             $query->Where("lgp_home_tutor.tutor_school" ,'like', "%".$param["school"].'%');
                        }

                        if(!empty($tutor_id_res)){  //文书
                           $query->whereIn('lgp_home_tutor.id',$tutor_id_res);
                        }
                    })
                    ->orderBy('lgp_home_tutor.sort','asc')
                    // ->orderByRaw('RAND()')
                    ->leftJoin('lgp_home_education as e', 'education_id', '=', 'e.id')
                    ->leftJoin('lgp_home_webshu_t', 'lgp_home_webshu_t.wenshu_t_tid', '=', 'lgp_home_tutor.id')
                    ->select('lgp_home_tutor.tutor_name','lgp_home_tutor.tutor_anli','lgp_home_tutor.tutor_wenshu','lgp_home_tutor.id','lgp_home_tutor.tutor_profile','lgp_home_tutor.tutor_school','lgp_home_tutor.tutor_major','lgp_home_tutor.tutor_price','lgp_home_tutor.tutor_label','lgp_home_tutor.tutor_help_count','lgp_home_tutor.tutor_introduction','e.education_name','lgp_home_webshu_t.wenshu_t_money')
                    ->forPage($wenshu_page,5)->get()->toArray();
            
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

    /**
     * [live 文书写作列表页]
     * @return [type] [description]
     */
    public function wenshu()
    {

            $param = $this->requests->getQueryParams();
            $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;
            $country_id = empty($param["country_id"]) ? 0 : $param["country_id"] ;
            $major = empty($param["major"]) ? 'PS' : $param["major"] ;

            $wenshu_page = empty($param["wenshu_page"]) ? 1 : $param["wenshu_page"] ;

            if(empty($user_id) || empty($country_id)){
                 return returnJson(-1,'缺少参数');
            }
            $where = array(
                "lgp_home_tutor.status" => 1,//是否展示该导师
                "lgp_home_tutor.tutor_is_wenshu" => 1,//是否开启文书写作
                "lgp_home_tutor.tutor_checked_status" => 2,//是否审核通过
                'lgp_home_tutor.tutor_country' => $country_id
                );
            if(!empty($param["education"])){    //学历
                $where["lgp_home_tutor.education_id"] = $param["education"];
            }

            // 导师信息
            $tutor = Tutor::Where(function ($query) use($where,$param,$major) {
                            if ($where) {
                                $query->Where($where);
                            }
                            
                            if(isset($param["school"]) && !empty($param["school"])){    //院校
                                 $query->Where("lgp_home_tutor.tutor_school" ,'like', "%".$param["school"].'%');
                            }

                            if(isset($major) && !empty($major)){  //文书
                               $query->Where("lgp_home_tutor.tutor_wenshu" ,'like', "%".$major.'%');
                            }

                    }) 
                    ->orderBy('lgp_home_tutor.sort','asc')
                    // ->orderByRaw('RAND()')
                    ->leftJoin('lgp_home_education as e', 'education_id', '=', 'e.id')
                    ->select('lgp_home_tutor.tutor_name','lgp_home_tutor.tutor_anli','lgp_home_tutor.tutor_wenshu','lgp_home_tutor.id','lgp_home_tutor.tutor_profile','lgp_home_tutor.tutor_school','lgp_home_tutor.tutor_major','lgp_home_tutor.tutor_price','lgp_home_tutor.tutor_label','lgp_home_tutor.tutor_help_count','lgp_home_tutor.tutor_introduction','e.education_name')
                    ->forPage($wenshu_page,5)->get()->toArray();

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

                // foreach($value["tutor_wenshu"] as $wenshu_key => $wenshu_value){
                //     if ($wenshu_value['wenshu_name'] == $major ) {
                //         $value['tutor_price'] = $wenshu_value['money'];
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
    /**
     * [live 语言培训列表页——修改]
     * @return [type] [description]
     */
    public function yuyanlist()
    {
        
        // 筛选 排序
        $param = $this->requests->getQueryParams();
        $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;   
        $country_id = empty($param["country_id"]) ? 0 : $param["country_id"] ;  //国家
        $yuyan_page = empty($param["yuyan_page"]) ? 1 : $param["yuyan_page"] ;
        $major = empty($param["major"]) ? '托福' : $param["major"] ;  //语言培训
        
        // 排序 start
        $order_name = empty($param["order_name"]) ? 'yuyan_t_money' : $param["order_name"] ;  //排序字段
        $order_type = empty($param["order_type"]) ? 'desc' : $param["order_type"] ;  //排序方法
        // 排序   end

        if ($order_name = 'yuyan_t_money') {
            $order_name = 'lgp_home_yuyan_t.'. $order_name;
        }
        if ($order_name = 'num') {
            $order_name = 'lgp_home_yuyan_t.yuyan_t_money';
        }

        if(empty($user_id) || empty($country_id)){
             return returnJson(-1,'缺少参数');
        }
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
        $tutor = Tutor::Where(function ($query) use($where,$param,$major) {
                    $where = array(
                        'lgp_home_yuyan_t.yuyan_t_name' => $major,
                    );

                    if ($where) {
                        $query->Where($where);
                    }
                        
                }) 
                ->orderBy($order_name,$order_type)
                // ->orderByRaw('RAND()')
                ->leftJoin('lgp_home_education as e', 'education_id', '=', 'e.id')
                ->leftJoin('lgp_home_yuyan_t', 'lgp_home_yuyan_t.yuyan_t_tid', '=', 'lgp_home_tutor.id')
                ->select('lgp_home_tutor.tutor_name','lgp_home_tutor.tutor_anli','lgp_home_tutor.tutor_yuyan','lgp_home_tutor.id','lgp_home_tutor.tutor_profile','lgp_home_tutor.tutor_school','lgp_home_tutor.tutor_major','lgp_home_tutor.tutor_price','lgp_home_tutor.tutor_label','lgp_home_tutor.tutor_help_count','lgp_home_tutor.tutor_introduction','e.education_name','lgp_home_yuyan_t.yuyan_t_money')
                ->forPage($yuyan_page,5)->get()->toArray();

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


            $value["tutor_yuyan"] = json_decode($value['tutor_yuyan'],true);
            $value['tutor_price'] = $value['yuyan_t_money'];

            // foreach($value["tutor_yuyan"] as $wenshu_key => $wenshu_value){
            //     if ($wenshu_value['yuyan_name'] == $major ) {
                    // $value['tutor_price'] = $wenshu_value['money'];
            //         break;
            //     }
            // }


            // 判断是否关注
            // $value["is_follow"] = Follow::where(["user_id"=>$user_id,"tutor_id"=>$value["id"]])->exists();
            $value["is_follow"] = true;
            $value["tutor_help_count"] = getNumW($value['tutor_help_count']);
            // $value['tutor_profile'] = imgTailor($value['tutor_profile'],100,100);
            
        }

         
        // 学历
        $education = objectToArray(Education::where("status",1)->orderBy("sort")->get());
        // 院校
        $school = objectToArray(School::where(["status"=>1,'country'=>$country_id])->orderBy("sort")->get());
        // 文书类型
        $subject = [
                        [
                            'yuyan_id'=>1,
                            'yuyan_name'=>"托福",
                            'status'=>1,
                            'sort'=>1
                        ],
                        [
                            'yuyan_id'=>2,
                            'yuyan_name'=>"雅思",
                            'status'=>1,
                            'sort'=>1
                        ]
                    ];
        
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
        // 搜索
         
    }


    /**
     * [live 语言培训列表页]
     * @return [type] [description]
     */
    public function yuyan()
    {
            $param = $this->requests->getQueryParams();
            $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;
            $country_id = empty($param["country_id"]) ? 0 : $param["country_id"] ;
            $major = empty($param["major"]) ? '托福' : $param["major"] ;

            $yuyan_page = empty($param["yuyan_page"]) ? 1 : $param["yuyan_page"] ;

            if(empty($user_id) || empty($country_id)){
                 return returnJson(-1,'缺少参数');
            }
            $where = array(
                "lgp_home_tutor.status" => 1,//是否展示该导师
                "lgp_home_tutor.tutor_is_wenshu" => 1,//是否开启文书写作
                "lgp_home_tutor.tutor_checked_status" => 2,//是否审核通过
                'lgp_home_tutor.tutor_country' => $country_id
                );
            if(!empty($param["education"])){    //学历
                $where["lgp_home_tutor.education_id"] = $param["education"];
            }

            // 导师信息
            $tutor = Tutor::Where(function ($query) use($where,$param,$major) {
                            if ($where) {
                                $query->Where($where);
                            }
                            
                            if(isset($param["school"]) && !empty($param["school"])){    //院校
                                 $query->Where("lgp_home_tutor.tutor_school" ,'like', "%".$param["school"].'%');
                            }

                            if(isset($major) && !empty($major)){  //文书
                               $query->Where("lgp_home_tutor.tutor_yuyan" ,'like', "%".$major.'%');
                            }

                    }) 
                    ->orderBy('lgp_home_tutor.sort','asc')
                    // ->orderByRaw('RAND()')
                    ->leftJoin('lgp_home_education as e', 'education_id', '=', 'e.id')
                    ->select('lgp_home_tutor.tutor_name','lgp_home_tutor.tutor_anli','lgp_home_tutor.tutor_yuyan','lgp_home_tutor.id','lgp_home_tutor.tutor_profile','lgp_home_tutor.tutor_school','lgp_home_tutor.tutor_major','lgp_home_tutor.tutor_price','lgp_home_tutor.tutor_label','lgp_home_tutor.tutor_help_count','lgp_home_tutor.tutor_introduction','e.education_name')
                    ->forPage($yuyan_page,5)->get()->toArray();

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


                $value["tutor_yuyan"] = json_decode($value['tutor_yuyan'],true);

                // foreach($value["tutor_yuyan"] as $wenshu_key => $wenshu_value){
                //     if ($wenshu_value['yuyan_name'] == $major ) {
                //         $value['tutor_price'] = $wenshu_value['money'];
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
            $subject = [
                            [
                                'yuyan_id'=>1,
                                'yuyan_name'=>"托福",
                                'status'=>1,
                                'sort'=>1
                            ],
                            [
                                'yuyan_id'=>2,
                                'yuyan_name'=>"雅思",
                                'status'=>1,
                                'sort'=>1
                            ]
                        ];
            
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



    /*
    *  首页2.0
    *  改版接口
    */
    public function indexTwo()
    {
        $this->banner_arrat = 1;  //调用方法 查询轮播图
        $this->course_list_array = 1;  //调用方法 查询 学长学姐说
        $this->article_list_array = 1;  //情报局-文章
        $this->video_list_array = 1;  //情报局-视频

        $data['banner_res'] = $this-> indexBanner();     //查询轮播图
        $data['course_res'] = $this-> listHome();    //首页-学长学姐说
        $data['article_res'] = $this-> article();    //首页-情报局-文章
        $data['video_res'] = $this-> index_video();    //首页-情报局-视频
        $data['link'] = '/pages/Boutique/Boutique';    //首页-情报局-视频
        
        return returnJson(2,'success',$data);

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
                ->orderBy('lgp_home_course.sort','asc')
                ->leftJoin('lgp_home_tutor as t', 'lgp_home_course.tutor_id', '=', 't.id')
                ->leftJoin('lgp_home_users as u', 't.id', '=', 'u.tutor_id')
                ->where("lgp_home_course.type",1)
                ->select('lgp_home_course.class_video','lgp_home_course.id as class_id',"lgp_home_course.class_name",'lgp_home_course.class_title_img','lgp_home_course.type','lgp_home_course.praised_count','lgp_home_course.play_count','lgp_home_course.forward_count','lgp_home_course.tutor_id','t.tutor_name','t.tutor_follow_count','u.user_id','t.tutor_profile','t.tutor_studied_before_school')
                ->paginate(4, ['*'], 'page', $videopage)->toArray();

        foreach ($course["data"] as $key => &$value) {
           $value["is_follow"]  =  Follow::where(["user_id"=>$user_id,"tutor_id"=>$value['tutor_id']])->exists();
        }


        // 判断直播课程状态
        if (!empty($this->video_list_array)) {
            return $course;
        }
        
        return returnJson(2,'success',$course);
    }



    /*
    * 首页查询轮播图
    */
    public function indexBanner()
    {
        $param = $this->requests->getQueryParams();
        $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;

         // 轮播查询    s
            $time = time();
            $data['banner'] =  Banner::where("status",1)
                ->where("start_time",'<=', $time )
                ->where("end_time",'>=',$time)
                ->orderBy("sort")
                ->get()
                ->toArray();
            $mobile = DB::table('lgp_home_users') ->where(['user_id' => $user_id])->value('mobile');    //查询用户

            foreach ($data['banner'] as $key => &$value) {
                
                if ($value['course_id']) {
                    
                    if ($mobile) {  //判断是否授权手机号
                        
                        $live_status = DB::table('lgp_home_course') ->where(['id' => $value['course_id']])->value('live_status');    //判断当前课程状态
                        switch ($live_status) {
                            case '1':
                                // 直播前
                                $value['path'] = '/pages/video_cet/video_cet?id='.$value['course_id'];
                                break;
                            case '2':
                                // 直播
                                $value['path'] = '/pages/webview/webview?live_id='.$value['course_id'];
                                break;
                            case '3':
                                // 回放
                                $value['path'] = '/pages/webview/webview?live_id='.$value['course_id'];
                                break;
                            case '4':
                                // 剪辑
                                $value['path'] = '/pages/video/video?id='.$value['course_id'];
                                break;
                        }

                    }else{

                        $value['path'] = '/pages/video_cet/video_cet?id='.$value['course_id'];
                    }
                }
            }

        if (!empty($this->banner_arrat)) {
            return $data['banner'];
        }

        return returnJson(2,'success',$data);
    }

    /**
     * [listHome 首页-学长学姐说-查询]
     * @return [type] [description]
     */
    public function listHome()
    {
        
        $param = $this->requests->getQueryParams();

        $course_res = DB::table('lgp_home_course')->where(["status"=>1,'live_status'=>4,'type'=>2])->orderBy("sort",'desc')->limit(5)->get();
        $course_res = objectToArray($course_res);
        
        foreach ($course_res as $key => &$value) {
            $value['live_time'] = date("Y-m-d",$value['live_time']);
        }

        if (!empty($this->course_list_array)) {
            return $course_res;
        }

        return returnJson(2,'success',$course_res);

    }



    /**
     * [article 咨询查询]
     * @return 
     */
    public function article()
    {
        // 参数
        $param = $this->requests->getQueryParams();
        $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;
        $articlepage = empty($param["articlepage"]) ? 1 : $param["articlepage"] ;
        if (empty($param["user_id"]) || empty($articlepage)) {
            return returnJson(-1,'缺少参数');
        }

        $pagesite = 4;
        $article_res =  DB::table('lgp_home_article')
            ->where(["lgp_home_article.status"=>1])
            ->leftJoin('lgp_home_tutor', 'lgp_home_tutor.id', '=', 'lgp_home_article.tutor_id')
            ->orderBy("lgp_home_article.sort",'asc')
            ->select('lgp_home_article.*','lgp_home_tutor.tutor_profile')
            ->forPage($articlepage,$pagesite)
            ->get();

        $article_res = objectToArray($article_res);
        foreach ($article_res as $key => &$value) {
            
            $value['article_content'] = strip_tags($value['article_content']);

            // 是否点赞
            $is_praise =  DB::table("lgp_home_mypraise")->where(["user_id"=>$user_id,"connect_id"=>$value["id"],'type'=>1])->exists();

            if($is_praise){
              $value["is_praise"]  = true;
            }else{
              $value["is_praise"] = false;
            }
            $value['browse_count'] = getNumW($value["browse_count"]);
            $value['forward_count'] = getNumW($value["forward_count"]);
            $value['praised_count'] = getNumW($value["praised_count"]);
        }


        $data['article_res'] = $article_res;
        $data['current_page'] = $articlepage;
        $data['last_page'] = ceil(DB::table('lgp_home_article')->where(["lgp_home_article.status"=>1])->count()/$pagesite) ;

        if (!empty($this->article_list_array)) {
            return $data;
        }

        return returnJson(2,'success',$data);

        
    }
    

    public function tutorpPaginate(){
            $param = $this->requests->getQueryParams();
            $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;
            $country_id = empty($param["country_id"]) ? 0 : $param["country_id"] ;
            if(empty($user_id) || empty($country_id)){
                 return returnJson(-1,'缺少参数');
            }
             $where = array(
                "lgp_home_tutor.status" => 1,//是否展示该导师
                "lgp_home_tutor.tutor_checked_status" => 2, //是否审核通过
                'lgp_home_tutor.tutor_country' => $country_id
                );

            if(!empty($param["education"])){
                $where["lgp_home_tutor.education_id"] = $param["education"];
            }
              // 导师信息
            $tutor = Tutor::Where(function ($query) use($where,$param) {
                            if ($where) {
                                $query->Where($where);
                            }
                            if(isset($param["school"]) && !empty($param["school"])){
                                 $query->Where("lgp_home_tutor.tutor_school" ,'like', "%".$param["school"].'%');
                            }
                            if(isset($param["major"]) && !empty($param["major"])){
                               $query->Where("lgp_home_tutor.subject_name" ,'like', "%".$param["major"].'%');
                            }
                    }) 
                    ->orderBy('lgp_home_tutor.sort','asc')
                    // ->orderByRaw('RAND()')
                    ->leftJoin('lgp_home_education as e', 'education_id', '=', 'e.id')
                    ->select('lgp_home_tutor.tutor_name','lgp_home_tutor.tutor_price','lgp_home_tutor.id','lgp_home_tutor.tutor_profile','lgp_home_tutor.tutor_school','lgp_home_tutor.tutor_major','lgp_home_tutor.tutor_label','lgp_home_tutor.tutor_help_count','lgp_home_tutor.tutor_introduction','e.education_name')
                    ->paginate(5)->toArray();

             foreach ($tutor["data"] as $key => &$value) {
                if(!empty($value["tutor_label"])){
                  $value["tutor_label"] = str_replace('，', ',', $value["tutor_label"]);
                  $value["tutor_label"] = explode(',', $value["tutor_label"]);
                }
                // 判断是否关注
                $value["is_follow"] = Follow::where(["user_id"=>$user_id,"tutor_id"=>$value["id"]])->exists();
                $value['tutor_profile'] = imgTailor($value['tutor_profile'],100,100);
                $value["tutor_help_count"] = getNumW($value['tutor_help_count']);

            }
            
            return returnJson(2,'success',$tutor); 


    }


    // 小灰鸽3.0首页
    public function index_three(){
           
            $param = $this->requests->getQueryParams();
            $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;
            $country_id = empty($param["country_id"]) ? 1 : $param["country_id"] ;
            // $user_id = 151
            // $country_id = 1;
            if(empty($user_id) || empty($country_id)){
                 return returnJson(-1,'缺少参数');
            }
            $where = array(
                "lgp_home_tutor.status" => 1,//是否展示该导师
                "lgp_home_tutor.tutor_checked_status" => 2,//是否审核通过
                "lgp_home_tutor.tutor_is_recommend" => 1,//是否审核通过
                'lgp_home_tutor.tutor_country' => $country_id
                );
            // 导师信息
            $tutor = Tutor::Where(function ($query) use($where) {
                            if ($where) {
                                $query->Where($where);
                            }
                    }) 
                    ->orderBy('lgp_home_tutor.sort','asc')
                    // ->orderByRaw('RAND()')
                    ->leftJoin('lgp_home_education as e', 'education_id', '=', 'e.id')
                    ->leftJoin('lgp_home_school as s' ,'tutor_school_id', '=', 's.id')
                    ->select('lgp_home_tutor.tutor_name','lgp_home_tutor.id','lgp_home_tutor.tutor_profile','lgp_home_tutor.tutor_major','lgp_home_tutor.tutor_label','lgp_home_tutor.tutor_help_count','lgp_home_tutor.tutor_introduction','e.education_name','s.school_name')
                    ->get()->toArray();


            foreach ($tutor as $key => &$value) {
                if(!empty($value["tutor_label"])){
                    $value["tutor_label"] = str_replace('，', ',', $value["tutor_label"]);
                    $value["tutor_label"] = explode(',', $value["tutor_label"]);
                }
                // 判断是否关注
                $value["is_follow"] = Follow::where(["user_id"=>$user_id,"tutor_id"=>$value["id"]])->exists();
                $value["tutor_help_count"] = getNumW($value['tutor_help_count']);
                 if(!empty($value['tutor_profile'])){
                    // $value['tutor_profile'] = imgTailor($value['tutor_profile'],100,100);
                    $value['tutor_profile'] = self::$lgp_url.imgTailor($value['tutor_profile'],100,100);
                }
                
            }

             // 轮播查询
            $time = time();
            $data['banner'] =  Banner::where("status",1)
                ->where("start_time",'<=', $time )
                ->where("end_time",'>=',$time)
                ->orderBy("sort")
                ->get()
                ->toArray();
            $mobile = DB::table('lgp_home_users') ->where(['user_id' => $user_id])->value('mobile');    //查询用户
            foreach ($data['banner'] as $key => &$value) {
                
                if ($value['course_id']) {
                    
                    if ($mobile) {  //判断是否授权手机号
                        
                        $live_status = DB::table('lgp_home_course') ->where(['id' => $value['course_id']])->value('live_status');    //判断当前课程状态
                        switch ($live_status) {
                            case '1':
                                // 直播前
                                $value['path'] = '/pages/video_cet/video_cet?id='.$value['course_id'];
                                break;
                            case '2':
                                // 直播
                                $value['path'] = '/pages/webview/webview?live_id='.$value['course_id'];
                                break;
                            case '3':
                                // 回放
                                $value['path'] = '/pages/webview/webview?live_id='.$value['course_id'];
                                break;
                            case '4':
                                // 剪辑
                                $value['path'] = '/pages/video/video?id='.$value['course_id'];
                                break;
                        }

                    }else{

                        $value['path'] = '/pages/video_cet/video_cet?id='.$value['course_id'];
                    }
                }
            }
           
            $data['tutor']  = $tutor;
            // 广告位
            $data['advert'] = objectToArray(DB::table('lgp_home_advert') ->where('status',1)->where('type',7)->orderBy("sort",'desc')->first());
            // 底部群是否展示
            $data["is_group"] = true;

             return returnJson(2,'success',$data); 
           


    }

   
    /**
     * [information 3.0留学情报局]
     * @param string user_id      用户名id（必传）
     * @param string country_id   国家id（必传）
     * @return [type] [description]
     */
    public function information(){
        // 学长学姐
        $this->is_leader = 1; 
        // 文章
        $this->is_article = 1;
        // 点播课程
        $this->is_video = 1;
        $data['title_banner'] =  self::getAdvert(5); //获取banner图

        $data['leaderAndsister'] = $this->leaderAndsister();// 留学情报局-学长学姐说
        $data['article']  = $this->tutorArticle();// 留学情报局-文章
        $data['video']  = $this->demand();  // 留学情报局-点播课程
        $data["is_display"] = self::$is_show;
        return returnJson(2,'success',$data);
    }
    /**
     * [information 3.0 留学情报局-学长学姐说 往期回放]
     * @param string country_id   国家id
     * @param string page   分页
     * @return [type] [description]
     */
    public function leaderAndsister(){
        $param = $this->requests->getQueryParams();
        $country_id = empty($param["country_id"]) ? 0 : $param["country_id"] ;
        $param["country_id"] = 1;
        $where = [
                    'c.type'=> 2 ,
                    'c.live_status'=>4,
                    'c.status'=>1,
                    't.status' =>1
                ];

        // 学长学姐说
        if(isset($param["country_id"]) && !empty($param["country_id"])){
            $where['t.tutor_country'] = $param['country_id'];
        }
        $courseobj= DB::table('lgp_home_course as c')
                  ->leftJoin('lgp_home_tutor as t', 'c.tutor_id', '=', 't.id')
                  ->where($where)
                  ->select('c.class_video','c.id',"c.class_name",'c.class_title_img','c.type','c.type','c.play_count','c.live_time')
                 ->paginate(6)->toArray();
        $course =  objectToArray($courseobj['data']);
        foreach ($course as $key => &$value) {
            $value['live_time'] = date("Y-m-d",$value['live_time']);
            $value['play_count'] = getNumW( $value['play_count']);
        }
        if (!empty($this->is_leader)) {
            if(self::$is_show){
                return $course;
            }else{
                return [];
            }
        }
        if(!self::$is_show){
            $course = [];
        }
        return returnJson(2,'success',$course);
    }
    /**
     * [tutorArticle 3.0 留学情报局-文章]
     * @param string country_id   国家id
     * @param string user_id   用户id
     * @param string page   分页
     * @return [type] [description]
     */
    public function tutorArticle() {
        $param = $this->requests->getQueryParams();
        $country_id = empty($param["country_id"]) ? 0 : $param["country_id"] ;
        $limit = self::$pageNum;
        $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;

        $country_id = 1;
        // $user_id = 15;
        if(empty($user_id)) {
            return returnJson(-1,'缺参数');
        }
        if(!empty($this->is_article)) {
            $limit = 4;
        }
        $where = ['a.status'=>1,'t.status' =>1];
        if(isset($param["country_id"]) && !empty($param["country_id"])) {
            $where['t.tutor_country'] = $param['country_id'];
        }
        $articleObj =  DB::table('lgp_home_article as a')
                                ->leftJoin('lgp_home_tutor as t', 't.id', '=', 'a.tutor_id')
                                ->select('a.id','a.article_name','a.article_img','a.article_content','a.browse_count','a.praised_count','t.tutor_follow_count','t.tutor_name','t.tutor_profile','a.tutor_id')
                                ->orderBy("a.sort",'asc')
                                ->where($where)
                                ->paginate($limit)->toArray();
        $article =  objectToArray($articleObj['data']);
        $gz_list = array_column(objectToArray(DB::table('lgp_home_follow')->where('user_id', $user_id)->get(['tutor_id'])),'tutor_id');

        foreach ($article as $key => &$value) {
           
            if(in_array($value['tutor_id'], $gz_list) ) {
                $value['is_follow'] = true;
            }else{
                 $value['is_follow'] = false;
            }
            $value['article_content'] = strip_tags($value['article_content']);
            $value['browse_count'] = getNumW( $value['browse_count']);
            $value['praised_count'] = getNumW( $value['praised_count']);
            $value['tutor_follow_count'] = getNumW( $value['tutor_follow_count']);
        }
      
        if( !empty($this->is_article) ) {
            return $article;
        }
        return returnJson(2,'success',$article);
    }
     /**
     * [demand 3.0 留学情报局-视频（点播课）]
     * @param string country_id   国家id
     * @param string user_id   用户id
     * @param string page   分页
     * @return [type] [description]
     */
    public function demand() {
        $param = $this->requests->getQueryParams();
        $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;
        $param["country_id"] = 1;
        if(empty($user_id)) {
            return returnJson(-1,'缺参数');
        }
        $limit = self::$pageNum;
        if(!empty($this->is_video)) {
            $limit =  4;
        }
        $where = [
                            'c.type'=> 1,
                            'c.status'=>1,
                            't.status' =>1
                        ];
        if(isset($param["country_id"]) && !empty($param["country_id"])) {
            $where['t.tutor_country'] = $param['country_id'];
        }
        $courseobj= DB::table('lgp_home_course as c')
                          ->leftJoin('lgp_home_tutor as t', 'c.tutor_id', '=', 't.id')
                          ->where($where)
                          ->select('c.class_video','c.id',"c.class_name",'c.class_title_img','forward_count','c.type','c.type','c.praised_count','c.live_time','c.tutor_id','t.tutor_follow_count','t.tutor_name','t.tutor_profile')
                         ->paginate($limit)->toArray();
        $course =  objectToArray($courseobj['data']);
        $gz_list = array_column(objectToArray(DB::table('lgp_home_follow')->where('user_id', $user_id)->get(['tutor_id'])),'tutor_id');
        foreach ($course as $key => &$value) {
            $value['is_follow'] = false;
            if( in_array($value['tutor_id'], $gz_list) ) {
                $value['is_follow'] = true;
            }
            $value['praised_count'] = getNumW( $value['praised_count']);
            $value['forward_count'] = getNumW( $value['forward_count']);
            $value['tutor_follow_count'] = getNumW( $value['tutor_follow_count']);
            // 临时
            $value['type'] = 2;
        }
        if (!empty($this->is_video)) {
            if(self::$is_show){
                return $course;

            }else{
             return [];
            }
        }
          if(!self::$is_show){
              $course = [];
          }
        return returnJson(2,'success',$course);
    }

    /*首页时时提醒*/
    public function play (){


        $file = fopen('play.csv','r');
        $arr = [];
        $num = 0;
        while ($data = fgetcsv($file)) {    //每次读取CSV里面的一行内容
            $arr[ $num ]['title'] = $this->strToUtf8($data[0]);
            $arr[ $num ]['time'] = $data[1];
            $num++;
        }
        fclose($file);
        return returnJson(2,'success',$arr);
    }


    // 编码转成utf8
    function strToUtf8($str){
        $encode = mb_detect_encoding($str, array("ASCII",'UTF-8',"GB2312","GBK",'BIG5'));
        if($encode == 'UTF-8'){
            return $str;
        }else{
            return mb_convert_encoding($str, 'UTF-8', $encode);
        }
    }


   public   function getAdv(){
        $param = $this->requests->getQueryParams();
        $type = empty($param['type']) ? "" : $param['type'] ;
        if( empty($type) ){
            return returnJson(-1,'缺少参数');
        }
         $list =  array();
        if($type == 1){
            $list = objectToArray(DB::table('lgp_home_advert')->where('status',1)->whereIn('type',[8,9,10])->get()) ;
          
        }else if($type == 2){
             $list = objectToArray(DB::table('lgp_home_advert')->where('status',1)->whereIn('type',[12,11])->get()) ;
        }
        $data['advert']  = $list;

        return returnJson(2,'success',$data);
        
    }


     


   
}
