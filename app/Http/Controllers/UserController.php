<?php

namespace App\Http\Controllers;

use App\Http\Models\Mypraise;
use App\Http\Models\Article;
use App\Http\Models\Users;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
// 发送短信
use App\Tools\ChuanglanSmsHelper\ChuanglanSmsApi;


class UserController extends Controller
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
        $datas =  json_decode($data,true);
        if(!isset($datas["openid"])){
             return returnJson(-1,'获取openid失败 code:'.$datas["errcode"].'errmsg:'.$datas["errmsg"],[]); 
        }

        // 保存openid
        $param['openid'] = json_decode($data,true)['openid'];  //数据 openid
        
        // 判断是否存在 unionid
        if (!empty(json_decode($data,true)['unionid'])) {
            $param['unionid'] = json_decode($data,true)['unionid'];  //数据 unionid
        }
         // $param["add_time"] = time();

        // 保存用户
        // $user_res['save'] = DB::table('lgp_home_users')
        //     ->updateOrInsert(
        //         ['openid' => $param['openid']],
        //         $param
        //     );

            // ============修改==================

                // $user_res = Users::where(['openid' => $param['openid']])->exists();
         //  if($user_res){
         //    $user_res['save'] =  DB::table("lgp_home_users")->where(['openid' => $param['openid']])->update($param);
         //  }else{
         //    // 添加
         //    $param["add_time"] = time();
         //    $user_res['save'] =  DB::table("lgp_home_users")->insert($param);
         //  }
            writelog('-----用户授权--------','openid','openid');
            writelog($param,'openid','openid');
            $user_info = objectToArray(DB::table('lgp_home_users') ->where('openid', $param['openid'])->first());    //查询用户
            writelog($user_info,'openid','openid');
            $user_res['save'] = $user_info;
            // 用户不存在
            if (empty($user_info)) {
                 writelog('-------添加用户信息----------------','openid','openid');
                 $param["add_time"] = time();
                 if(!empty($post_res["type"])){
                    $param["source_type"] = $post_res["type"];
                 }
                 writelog($param,'openid','openid');
                 $user_res['save']["user_id"] =  DB::table("lgp_home_users")->insertGetId($param);
                 $user_res['save']['mobile'] = '';
            }else{
                 // $res = DB::table("lgp_home_users")->where(['openid' => $param['openid']])->update($param);
               
                   writelog('-------更新用户信息----------------','openid','openid');
              // if($user_info['openid'] != $param['openid'] ||  $user_info['unionid'] != $param['unionid']){
                 $user_id = $user_info['user_id'];
                 writelog($param,'openid','openid');
                 writelog($user_id,'openid','openid');
                 $res = DB::table("lgp_home_users")->where(['openid' => $param['openid'],'user_id'=>$user_id])->update($param);
                  writelog($res,'openid','openid');
              // }



            }
            if (empty($user_res['save']["user_id"])) {
                    return returnJson(-1,'非法用户',[]); 
            }

            // -=================修改结束====================



        // $user_res['save'] = objectToArray(DB::table('lgp_home_users') ->where(['openid' => $param['openid']])->first());    //查询用户
        
        // if (empty($user_res['save'])) {
        //     return returnJson(-1,'非法用户',[]); 
        // }

      
        $user_res['user']['user_id'] =  $user_res['save']['user_id'];   //用户id
        $user_res['user']['is_mobile'] =  $user_res['save']['mobile'] ?  1 : 0;   //是否授权手机号
        $user_res['user']['version'] = 1; //版本
        unset($user_res['save']);

        $user_res['session_key'] = json_decode($data,true)['session_key'];

        return returnJson(2,'success',$user_res);

    }



    /**
     * 获取用户手机号
     * @return [type] [description]
     */
    public function userPhone()
    {

        // 获取code
        $post_res = $this->requests->getQueryParams();


        // 保存手机号 成功
        $user_id = $post_res['user_id'];
        $sessionKey = $post_res['session_key'];
        $iv = $post_res['iv'];
        $encryptedData = $post_res['encryptedData'];
        $data = '';

        $errCode = $this->decryptData($encryptedData, $iv, $data,$sessionKey);

        $data = json_decode($data);
        $data = objectToArray($data);


        if (!$data['phoneNumber']) {
            return returnJson(-1,'保存失败1',[]);
        }
        $mobile = DB::table('lgp_home_users') ->where(['user_id' => $user_id])->value('mobile');    //查询用户
        $data_new['mobile'] = $data['phoneNumber'];
       
        if($mobile == $data['phoneNumber']){
            return returnJson(2,'保存成功',[]);
        }

        // // 保存用户
        $user_res['save'] = DB::table('lgp_home_users')
            ->where('user_id' ,$user_id)
            ->update($data_new);


        if ($user_res['save']) {

            $user_info =  objectToArray(DB::table('lgp_home_users') ->where(['user_id' => $user_id])->first());    //查询用户
            // 发送邮件
            $email_param = array(
                'type'  =>  '用户授权',
                'subject'   => '小灰鸽用户授权', // 主题
                'body'  => "姓名：{$user_info['username']}，电话：{$user_info['mobile']}", // 内容
                'title' => '小灰鸽用户授权', // 标题
                'userArr' => $this->email_user_list,
                'user'  => $user_info['username'], // 用户
                'mobile'    => $user_info['mobile'], // 电话
            );
            $res = \App\Http\Controllers\ToolController::SendMail( $email_param );


            return returnJson(2,'保存成功',[]);
        }else{
            return returnJson(-1,'保存失败2',[]);
        }

        
    }


    /**
     * 检验数据的真实性，并且获取解密后的明文.
     * @param $encryptedData string 加密的用户数据
     * @param $iv string 与用户数据一同返回的初始向量
     * @param $data string 解密后的原文
     *
     * @return int 成功0，失败返回对应的错误码
     */
    public function decryptData( $encryptedData, $iv, &$data ,$sessionKey)
    {
        if (strlen($sessionKey) != 24) {
            return self::$IllegalAesKey;
        }
        $aesKey=base64_decode($sessionKey);
        
        if (strlen($iv) != 24) {
            return self::$IllegalIv;
        }
        $aesIV=base64_decode($iv);

        $aesCipher=base64_decode($encryptedData);

        $result = openssl_decrypt( $aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
        $dataObj=json_decode( $result );
        if( $dataObj  == NULL )
        {
            return self::$IllegalBuffer;
        }
        if( $dataObj->watermark->appid != $this->appid )
        {
            return self::$IllegalBuffer;
        }
        $data = $result;
        return self::$OK;
    }

    /**
     * 判断用户信息是否存在
     * @return int 
     */
    public function userInfo(){
      
        $param = $this->requests->getQueryParams();
        $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;
        if(empty($user_id)){
            $res =  returnJson(-1,'缺少参数');
            return $res;
        }
        $is_exists = DB::table('lgp_home_users')->where('user_id',$user_id)->exists();
        if(!$is_exists){
            return  returnJson(-1,'用户不存在');
        }
        // 判断type是否存在，存在为修改，不存在为初始化
        if(isset($param["type"]) && $param["type"] == 1){
                $data["username"] = empty($param["username"]) ? '' : $param["username"] ;
                $data["avatar"] = empty($param["avatar"]) ? '' : $param["avatar"] ;
                // $data["province"] = empty($param["province"]) ? '' : $param["province"] ;
                // $data["city"] = empty($param["city"]) ? '' : $param["city"] ;
                // $data["district"] = empty($param["district"]) ? '' : $param["district"] ;
                // $data["birthday"] = empty($param["birthday"]) ? '' : $param["birthday"] ;
                // $data["sex"] = empty($param["sex"]) ? '' : $param["sex"] ;
                if(empty($user_id)){
                    $res =  returnJson(-1,'缺少参数');
                    return $res;
                }
                if(isset($param["school"])){
                    $data["school"]  = trim($param["school"]);
                }
                if(isset($param["major"])){
                     $data["major"]  = trim($param["major"]); 
                }
                if(isset($param["int_school"])){
                     $data["int_school"]  = trim($param["int_school"]); 
                }
                if(isset($param["int_major"])){
                     $data["int_major"]  = trim($param["int_major"]); 
                }
               
                $res = DB::table("lgp_home_users")->where('user_id',$user_id)->update($data);
                 // if($res){
                 //    return returnJson(2,'success');
                 //  }else{
                 //    return returnJson(-1,'更新失败');
                 //  }
                 return returnJson(2,'success');

        }elseif(isset($param["type"]) && $param["type"] == 2){
             $mobile = empty($param["mobile"]) ? '' : $param["mobile"] ;
             if(empty($mobile)){
                    return  returnJson(-1,'手机号不能为空');
              }
             if(DB::table("lgp_home_users")->where('user_id',$user_id)->update(['mobile'=>$mobile])){
                return returnJson(2,'success');
              }else{
                return returnJson(-1,'更新失败');
              }
        }else{
            $user =  objectToArray(DB::table('lgp_home_users')->where('user_id' ,$user_id)->first());
            if(empty($user)){
                  return returnJson(-1,'数据异常');
            }
             if(empty($user["mobile"])){
                return returnJson(0,'用户未授权');
            }
            // if(empty($user["username"])){
            //     return returnJson(0,'用户未授权');
            // }
            // 查询该用户是否申请成为导师
            $tutor =  objectToArray(DB::table('lgp_home_apply_tutor')->where(['user_id' =>$user["user_id"]])->select("id as tutor_id",'apply_status','tutor_name')->first());

            $user["apply_status"] = 0;
            $user["tutor_name"] = 0;
            if(!empty($tutor)){
                $user["apply_status"]  = $tutor['apply_status'];
                $user["tutor_name"] = $tutor['tutor_name'];
            }
           
            $user["tutor_checked_status"] = 0;
            $user["tutor_name"] = 0;
            // if(!empty($user["tutor_id"])){
            //     $tutor =  objectToArray(DB::table('lgp_home_tutor')->where(['id' =>$user["tutor_id"]])->select("id as tutor_id",'tutor_checked_status','tutor_name')->first());
            //     if(!empty($tutor)){
            //         $user["tutor_checked_status"] = $tutor["tutor_checked_status"];
            //         $user["tutor_name"] = $tutor["tutor_name"];
            //     }
            // }
            unset($user["openid"]);
            unset($user["unionid"]);

           return returnJson(2,"success",$user);

        }
      

    }

    /*
        发送验证码
    */
    public function sendCode(){
        $data = $this->requests->getQueryParams();
        if( empty($data['mobile']) ){
            return returnJson(-1,"手机号为空");
        }
        $clapi  = new ChuanglanSmsApi();
        $code = mt_rand(1000,9999);
        $result = $clapi->sendSMS(
            $data['mobile'], 
            '【小灰鸽】您好！验证码是:' . $code
        );
        // 发送邮件
        $email_param = array(
            'type'  =>  '用户验证码',
            'subject'   => '小灰鸽用户发送验证码', // 主题
            'body'  => "电话：{$data['mobile']}", // 内容
            'userArr' => ['jiangfuyou@beliwin.com'], // 用户
            'title' => '小灰鸽用户发送验证码', // 标题
            'user'  => '', // 用户
            'mobile'    => $data['mobile'], // 电话
        );
        $res = \App\Http\Controllers\ToolController::SendMail( $email_param );
        return returnJson(2,"success",$code);
    }

    /*
        检查用户是否存在
    */
    public static function checkUser( $userId ){

        if( empty($userId) ){
            return returnJson(-1,"用户id不能为空");
        }

        $is_exists = DB::table('lgp_home_users')->where('user_id',$userId)->exists();

        if($is_exists){
            return true;
        }else{
            return false;
        }

    }

}
