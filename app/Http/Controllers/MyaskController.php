<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Http\Models\Myask;
use App\Http\Models\Myaskproblem;
use App\Http\Models\Follow;
// 用户咨询表(预约咨询)
class MyaskController extends Controller
{




    /**
     * [index 咨询列表]
     * @return [type] [description]
     */
    public function index(){
        $param = $this->requests->getQueryParams();
        $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;
        if(empty($user_id)){
            $res =  returnJson(-1,'缺少参数');
            return $res;
        }

        $myask = Myask::leftJoin('lgp_home_tutor as t', 't.id', '=', 'lgp_home_myask.tutor_id')
          ->leftJoin('lgp_home_education as e', 't.education_id', '=', 'e.id')
          ->where(["lgp_home_myask.is_del"=>1,'t.status'=>1,'lgp_home_myask.user_id'=>$user_id])
          ->orderBy('lgp_home_myask.communication_time','asc')
          ->select('t.id as tutor_id',"t.tutor_name","t.tutor_label","t.tutor_profile","t.tutor_school","t.tutor_major","e.education_name",'lgp_home_myask.id as myask_id','lgp_home_myask.communication_time','lgp_home_myask.add_time')
          ->get();
          $myask = objectToArray($myask);
          $time = time()- 2*3600;
          $ywhere = array(
              "is_del" => 1,
              "user_id" => $user_id
          );
        
          foreach ($myask as $key => $value) {
            if(!empty($value["tutor_label"])){
                  $tutor[$key]["tutor_label"] = explode(',', $value["tutor_label"]);
                }

               $myask[$key]["is_follow"] = Follow::where(["user_id"=>$user_id,'tutor_id'=>$value["tutor_id"]])->exists();
               // 判断预约是否过期
               $myask[$key]['is_yuyue'] = Myask::where($ywhere)->where('tutor_id',$value["tutor_id"])->where("communication_time",'>',$time)->exists();
              
               $myask[$key]['yuyue_time']  = '';
               $myaskproblem = objectToArray(Myaskproblem::where("myask_id",$value["myask_id"])->where("communication_time",$value["communication_time"])->orderByDesc('communication_time')->orderByDesc("add_time")->first());
               
               if(!is_judge($myaskproblem)){
                  continue;
               }
              
               // 未过期时 判断是否预约 如有预约判断是否解决
               if($myask[$key]['is_yuyue'] &&  $myaskproblem["is_dispose"] == 1){
                  $myask[$key]['yuyue_time'] = date("Y-m-d H:i",$value["communication_time"]);
               }else{
                // 过期
                  $myask[$key]['is_yuyue'] = false;
                  $myask[$key]["myaskproblem_id"] = $myaskproblem['id'];
                  $myask[$key]["is_evaluate"] = $myaskproblem['is_evaluate'];  //是否评论
               }
     
          }

          
          return returnJson(2,'success',$myask);


    }

    /**
     * [evaluate 预约咨询评价]
     * @return [type] [description]
     */
    public function evaluate(){
      $param = $this->requests->getQueryParams();
      unset($param['s']);
      
      $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;
      $id = empty($param["myaskproblem_id"]) ? 0 : $param["myaskproblem_id"] ;
      $data['total_evaluate'] = empty($param["total_evaluate"]) ? 0 : intval($param["total_evaluate"]) ; 
      $data['solve_problem'] = empty($param["solve_problem"]) ? 0 : intval($param["solve_problem"]) ; 
      $data["text_evaluate"] = empty($param["text_evaluate"]) ? 0 : $param["text_evaluate"] ; 
      $data["unsolved_problem"] = empty($param["unsolved_problem"]) ? 0 : $param["unsolved_problem"] ; 

      if(empty($user_id) || empty($id) || empty($data['total_evaluate']) || empty($data['solve_problem'])) {
          $res =  returnJson(-1,'缺少参数');
          return $res;
      }
       $data["is_evaluate"] = 1;
      $res = DB::table("lgp_home_myask_problem")->where('id',$id)->update($data);
      if($res){

        return returnJson(2,'success');
      }else{
        return returnJson(-1,'评价失败');
      }
   
  }


    
    /**
     * [add 添加咨询]
     * @return [type] [description]
     */
    public function add()
    {
            $param = $this->requests->getQueryParams();
            $myask['user_id'] = empty($param["user_id"]) ? 0 : $param["user_id"] ;
            $myask['tutor_id'] = empty($param["tutor_id"]) ? 0 : $param["tutor_id"] ;
            $myask['name'] = empty($param["name"]) ? 0 : $param["name"] ;
            $myask['phone'] = empty($param["phone"]) ? 0 : $param["phone"] ;
            $data['grade'] = empty($param["grade"]) ? 0 : $param["grade"] ;
            $data['issue'] = empty($param["issue"]) ? 0 : $param["issue"] ;
            $date = empty($param["date"]) ? 0 : $param["date"] ;
            $time = empty($param["time"]) ? 0 : $param["time"] ;
            if(empty($myask['user_id']) || empty($myask['tutor_id']) || empty($myask['name']) || empty($myask['phone']) || empty($data['grade']) || empty($date) || empty($time) || empty($data['issue'])){
                 return returnJson(-1,'缺少参数');
            }
            $times = $date.' '. $time ;

            $nowdate = date('Y-m-d',time());
            // 当前日期的时间戳
            $out_date = strtotime($nowdate);
            // 传过来的日期时间戳
            $is_date = strtotime($date);

            // 判断客户选日的日期是否大于等于当前日期
              // 这是日期小于当前日期
            if( $out_date  >  $is_date){
               return returnJson(-1,'预约日期不能小于当前日期');
            }

            // 当选择的日期为当前日期时 判断预约时间是否大于当前时间
             if( $is_date == $out_date){
                // 获取当前时间的时，分
                $nowtime = date('H:i',time());
                // 获取当前时间的时分的时间戳
                $out_time = strtotime($nowtime);
                // 获取客户选择的时分的时间戳
                $is_time = strtotime($time);
                //判断预约时间是否大于当前时间
                if($out_time >= $is_time ){
                   return returnJson(-1,'预约时间必须大于当前时间');
                }
            }
          
           
            $myask['communication_time']  = strtotime($times);
            $myask['add_time'] = time();

            $data['communication_time']  = strtotime($times);
            $data['name'] = empty($param["name"]) ? 0 : $param["name"] ;
            $data['phone'] = empty($param["phone"]) ? 0 : $param["phone"] ;
            $data['add_time'] = time();
            $data['form_id'] = $param["form_id"];
           $is_exit = objectToArray(Myask::where(['user_id'=>$myask["user_id"],'tutor_id'=>$myask["tutor_id"]])->first());

           if(is_judge($is_exit)){
               unset($myask['user_id']);
               $myask["is_del"] = 1;
              // 修改myask表为
              DB::table('lgp_home_myask')->where('id', $is_exit["id"])->update($myask);
            // 存在更新
              $data['myask_id'] = $is_exit["id"];
              // $data['form_id'] = $param["form_id"];
              
              $res = DB::table("lgp_home_myask_problem")->insert($data);
              if($res){
                  return returnJson(2,'success');
              }else{
                 return returnJson(-1,'预约失败');
              }
           }else{
              // 不存在
              $data['myask_id'] = DB::table("lgp_home_myask")->insertGetId($myask);
              if($data['myask_id']){
                     DB::table("lgp_home_myask_problem")->insert($data);
                     return returnJson(2,'success');
                }else{
                     return returnJson(-1,'预约失败');
                }
           }
    }


    /**
     * [delAsk 我的预约删除]
     * @return [type] [description]
     */
    public function delAsk(){
        $param = $this->requests->getQueryParams();
        $data['user_id'] = empty($param["user_id"]) ? 0 : $param["user_id"] ;
        $data['tutor_id'] = empty($param["tutor_id"]) ? 0 : $param["tutor_id"] ;
        if(empty($data['user_id']) && empty( $data['tutor_id'] )){
            $res =  returnJson(-1,'缺少参数');
            return $res;
        }
       
        $is_exit = objectToArray(DB::table("lgp_home_myask")->where($data)->first());
        // 判断是否存在
        if(is_judge($is_exit)){
          // 存在判断是否已删除
          if($is_exit["is_del"] == 2){
             return returnJson(2,'success');
          }else{
            // 未删除更新为已删除
             if(DB::table("lgp_home_myask")->where($data)->update(["is_del"=>2])){
                   return returnJson(2,'success');
              }else{
                   return returnJson(-1,'删除失败');
              }
          }
        }else{
          // 不存在直接返回成功
          return returnJson(2,'success');

        }

    }





}
