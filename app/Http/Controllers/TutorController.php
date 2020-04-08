<?php

namespace App\Http\Controllers;

use App\Http\Models\Mypraise;
use App\Http\Models\Article;
use App\Http\Models\Users;
use App\Http\Models\Follow;
use App\Http\Models\Myask;
use App\Http\Models\Tutor;
use App\Http\Models\Education;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Models\MyaskOrder;
class TutorController extends Controller
{


    /**
     * [useropenid 获取用户openid]
     * @return [type] [description]
     */
    public function userOpenid()
    {

        
        // 获取code
        $post_res = $this->requests->getQueryParams();

        $code = $post_res['code']; 

        // 获取openid
        $curl = curl_init();
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . $this->appid . '&secret=' . $this->secret . '&js_code=' . $code . '&grant_type=' . $this->grant_type;
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($curl);
        curl_close($curl);

        // 保存openid
        $param['openid'] = json_decode($data,true)['openid'];  //数据 openid
        
        // 判断是否存在 unionid
        if (!empty(json_decode($data,true)['unionid'])) {
            $param['unionid'] = json_decode($data,true)['unionid'];  //数据 unionid
        }

        // 保存用户
        $user_res['save'] = DB::table('lgp_home_users')
            ->updateOrInsert(
                ['openid' => $param['openid']],
                $param
            );



        $user_res['save'] = objectToArray(DB::table('lgp_home_users') ->where(['openid' => $param['openid']])->first());    //查询用户
        
        if (empty($user_res['save'])) {
            return returnJson(-1,'非法用户',[]); 
        }

      
        $user_res['user']['user_id'] =  $user_res['save']['user_id'];   //用户id
        $user_res['user']['is_mobile'] =  $user_res['save']['mobile'] ?  1 : 0;   //是否授权手机号

        unset($user_res['save']);

        $user_res['session_key'] = json_decode($data,true)['session_key'];

        return returnJson(2,'success',$user_res);

    }

    public function shequn()
    {

        return returnJson(2,'success',['is_shequn'=>true,'shequn_name'=>'小灰鸽']);
    }

    
    /**
     * [index 导师文书详情页]
     * @return [type] [description]
     */
    public function wenshu_detail()
    {

         // 获取code
        $post_res = $this->requests->getQueryParams();
        $tutor_id = empty($post_res['tutor_id']) ? '' : $post_res['tutor_id'] ;
        $user_id = empty($post_res['user_id']) ? '' : $post_res['user_id'] ;
        $type_name = empty($post_res['type_name']) ? '' : $post_res['type_name'] ;  //文书名称

        if (!$tutor_id || !$user_id) {
            return returnJson(-1,'参数错误',[]); 
        }


        // 查询导师 信息
        $tutor_res['tutor'] = objectToArray(DB::table('lgp_home_tutor') ->where(['id' => $tutor_id])->first());

        $tutor_res['tutor']["tutor_wenshu"] = json_decode($tutor_res['tutor']['tutor_wenshu'],true);

        // foreach($tutor_res['tutor']["tutor_wenshu"] as $wenshu_key => $wenshu_value){
        //     if ($wenshu_value['wenshu_name'] == $type_name ) {
        //         $tutor_res['tutor']['tutor_price'] = $wenshu_value['money'];
        //         break;
        //     }
        // }

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
      
        // -----------------------订单结束-----------------
       
        
        // 统计关注老师人数
        // $tutor_res['guanzhu_count'] = DB::table('lgp_home_follow') ->where(['tutor_id'=>$tutor_id ])->count();
        $tutor_res['guanzhu_count'] = getNumW($tutor_res['tutor']['tutor_follow_count']);

        $tutor_res['tutor']['tutor_follow_count'] = $tutor_res['guanzhu_count'];


        // 统计老师帮助人数
        // $tutor_res['bangzhu_count'] = DB::table('lgp_home_myask') ->where(['tutor_id'=>$tutor_id ])->count();
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
     * [index 导师文书详情页]
     * @return [type] [description]
     */
    public function yuyue_detail()
    {

         // 获取code
        $post_res = $this->requests->getQueryParams();
        $tutor_id = empty($post_res['tutor_id']) ? '' : $post_res['tutor_id'] ;
        $user_id = empty($post_res['user_id']) ? '' : $post_res['user_id'] ;
        $type_name = empty($post_res['type_name']) ? '' : $post_res['type_name'] ;  //文书名称

        if (!$tutor_id || !$user_id) {
            return returnJson(-1,'参数错误',[]); 
        }


        // 查询导师 信息
        $tutor_res['tutor'] = objectToArray(DB::table('lgp_home_tutor') ->where(['id' => $tutor_id])->first());

        $tutor_res['tutor']["tutor_yuyan"] = json_decode($tutor_res['tutor']['tutor_yuyan'],true);

        // foreach($tutor_res['tutor']["tutor_yuyan"] as $wenshu_key => $wenshu_value){
        //     if ($wenshu_value['yuyan_name'] == $type_name ) {
        //         $tutor_res['tutor']['tutor_price'] = $wenshu_value['money'];
        //         break;
        //     }
        // }

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
      
        // -----------------------订单结束-----------------
       
        
        // 统计关注老师人数
        // $tutor_res['guanzhu_count'] = DB::table('lgp_home_follow') ->where(['tutor_id'=>$tutor_id ])->count();
        $tutor_res['guanzhu_count'] = getNumW($tutor_res['tutor']['tutor_follow_count']);

        $tutor_res['tutor']['tutor_follow_count'] = $tutor_res['guanzhu_count'];


        // 统计老师帮助人数
        // $tutor_res['bangzhu_count'] = DB::table('lgp_home_myask') ->where(['tutor_id'=>$tutor_id ])->count();
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
     * [index 导师详情页]
     * @return [type] [description]
     */
    public function index()
    {

         // 获取code
        $post_res = $this->requests->getQueryParams();
        $tutor_id = empty($post_res['tutor_id']) ? '' : $post_res['tutor_id'] ;
        $user_id = empty($post_res['user_id']) ? '' : $post_res['user_id'] ;

        if (!$tutor_id || !$user_id) {
            return returnJson(-1,'参数错误',[]); 
        }


        // 查询导师 信息
        $tutor_res['tutor'] = objectToArray(DB::table('lgp_home_tutor') ->where(['id' => $tutor_id])->first());
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
      
        // -----------------------订单结束-----------------
       
        
        // 统计关注老师人数
        // $tutor_res['guanzhu_count'] = DB::table('lgp_home_follow') ->where(['tutor_id'=>$tutor_id ])->count();
        $tutor_res['guanzhu_count'] = getNumW($tutor_res['tutor']['tutor_follow_count']);

        $tutor_res['tutor']['tutor_follow_count'] = $tutor_res['guanzhu_count'];


        // 统计老师帮助人数
        // $tutor_res['bangzhu_count'] = DB::table('lgp_home_myask') ->where(['tutor_id'=>$tutor_id ])->count();
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
     * [education 学历]
     * @return [type] [description]
     */
    public function education(){
        $param = $this->requests->getQueryParams();
        $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;
        if(empty($user_id)){
            return returnJson(-1,'缺少参数');
        }
        $Education = objectToArray(Education::where("status",1)->orderBy("sort",'desc')->get());

        if(empty($Education)){
             return returnJson(-1,'暂无数据');
        }
         $users = objectToArray(Users::where('user_id',$user_id)->first());
        if(empty($users)){
            return returnJson(-1,'用户不存在');
        }
         $tutor = objectToArray(Tutor::where("id",$users["tutor_id"])->select("id as tutor_id","tutor_checked_status as checked_status")->first()) ;
         $data["education"] = $Education;
         $data["tutor"] = $tutor;
         
        return returnJson(2,'success',$data);
    }


    /**
     * [applay 申请成为导师]
     * @return [type] [description]
     */
    public function applay(){
        $param = $this->requests->getQueryParams();
        unset($param['s']);
        unset($param['emaill']);
        unset($param['emaill']);

        $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;
        if(empty($user_id)){
            return returnJson(-1,'缺少参数');
        }
        $users = objectToArray(Users::where('user_id',$user_id)->first());
        if(empty($users)){
            return returnJson(-1,'用户不存在');
        }
       
        unset($param['user_id']);
        if(empty($users["tutor_id"])){
             $param["tutor_crate_time"] = time();
             unset($param["emaill"]);
             $tutor_id = DB::table('lgp_home_tutor')->insertGetId($param);
             if($tutor_id){

                // 发送邮件
                $email_param = array(
                    'type'  =>  '申请导师',
                    'subject'   => '小灰鸽申请导师', // 主题
                    'body'  => "姓名：{$users['username']}，电话：{$users['mobile']}", // 内容
                    'userArr' => $this->email_user_list, // 用户
                    'title' => '小灰鸽申请导师', // 标题
                    'user'  => $users['username'], // 用户
                    'mobile'    => $users['mobile'], // 电话
                );
                $res = \App\Http\Controllers\ToolController::SendMail( $email_param );

                DB::table('lgp_home_users')
                ->where('user_id', $users["user_id"])
                ->update(['tutor_id' => $tutor_id]);
                
                return returnJson(2,'success',$tutor_id);
            }else{
                return returnJson(-1,'申请失败');
            }

        }else{
            $param["tutor_checked_status"] = 1;
            $res =  DB::table('lgp_home_tutor')->where("id",$users["tutor_id"])->update($param);
            if($res){
                  return returnJson(2,'success');
            }else{
                 return returnJson(-1,'申请失败');
            }
        }
        
    }



       /**
     * [applay 申请成为导师 3.0]
     * @return [type] [description]
     */
    public function applay_tutor(){
         $param = $this->requests->getQueryParams();
         unset($param['s']);
         $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;
         // $param['tutor_name'] ='导师名';
         // $param['tutor_profile'] ='头像';
         // $param['tutor_phone'] ='15210978147';
         // $param['tutor_advantage'] ='优势';
         // $param['wechat'] ='微信';
         // $param['tutor_emile'] ='邮箱';
         // $param['tutor_country'] ='留学国家';
         // $param['tutor_address'] ='目前所在地';
         // $param['study_abroad_school'] ='留学国家';
         // $param['highest_education'] ='最高学历';
         // $param['tutor_major'] ='所学专业';
         if(empty($user_id)){
            return returnJson(-1,'缺少参数');
         }
         $users = objectToArray(Users::where('user_id',$user_id)->first());
         if(empty($users)){
            return returnJson(-1,'用户不存在');
         }
         if(empty($param['tutor_phone']) || empty($param['tutor_emile']) || empty($param['wechat']) || empty($param['tutor_name']) || empty($param['tutor_country']) || empty($param['tutor_address'])  || empty($param['study_abroad_school']) || empty($param['highest_education']) || empty($param['tutor_major'])){
             return returnJson(-1,'必填项不能为空');
         }
         // 判断该用户是否申请导师
         $apply_tutor = objectToArray(DB::table('lgp_home_apply_tutor')->where('user_id',$user_id)->first());
         $param['apply_status'] =1;
         $res = false;
         if(!empty($apply_tutor)){
             // 修改
             unset($param['user_id']);
             $res = DB::table('lgp_home_apply_tutor')->where("id",$apply_tutor["id"])->update($param);
         }else{
            // 添加
            $param['add_time'] = time();
            $res = DB::table('lgp_home_apply_tutor')->insertGetId($param);
         }
          if($res){
                // // 发送邮件
                // $email_param = array(
                //     'type'  =>  '申请导师',
                //     'subject'   => '小灰鸽申请导师', // 主题
                //     'body'  => "姓名：{$users['username']}，电话：{$users['mobile']}", // 内容
                //     'userArr' => $this->email_user_list, // 用户
                //     'title' => '小灰鸽申请导师', // 标题
                //     'user'  => $users['username'], // 用户
                //     'mobile'    => $users['mobile'], // 电话
                // );
            // $res = \App\Http\Controllers\ToolController::SendMail( $email_param );
            return returnJson(2,'success');
        }else{
            return returnJson(-1,'申请失败');
        }

      

    }


}
