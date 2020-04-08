<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Http\Models\Tutor;
use App\Http\Models\Users;
use App\Tools\WeixinPay;
use App\Http\Models\Follow;
use App\Http\Models\MyaskOrder;

class OrderController extends Controller
{
    /*
        订单状态
    */   
    public static $orderStatus = [
        '已确定',
        '进行中',
        '已完成',
        '待支付',
        '申请退款',
        '退款完成',
        '已取消',
    ];


    /**
    *创建订单
    *@param 
    *
    **/
    public function create(){
        $data = $this->requests->getQueryParams();
        writelog($data,'order','create_order');
    	// ----------测试数据开始-------------
        // $data['user_id'] = 15;
        // $data['tutor_id'] = 1;
        // $data['create_time'] = time();
        // $data["order_type"] = 1; //订单类型： 1 微信支付订单/2,鸽蛋订单/3活动订单
        // $data["pay_type"] = 1; //支付方式
        // $data["pay_status"] = 1; //支付状态
        // $data["grade"] = 1;
        // $data["connect_date"] = '2020-01-15'; //沟通日期
        // $data["connect_time"] = '21:00'; //沟通时间
        // $data["price"] = '0.01'; //沟通时间
        // $data["name"] = "测试";
        // $data["phone"] = "15210978147";
        // $data["form_id"] = "15210978147";
        // $data["issue_content"] = "这是咨询的问题。。。。。。。。";
        // ----------测试数据结束-------------
         $date = empty($data["connect_date"]) ? 0 : $data["connect_date"] ;
         $time = empty($data["connect_time"]) ? 0 : $data["connect_time"] ;

    	 if(empty($data["user_id"]) || empty($data["tutor_id"]) || empty($date) || empty($time)){
    	 	return returnJson(-1,'缺少参数');
    	 }
          if(empty($data["name"])){
            return returnJson(-1,'姓名不能为空');
         }

         if(empty($data["phone"])){
            return returnJson(-1,'手机号不能为空');
         }

         if(empty($data["issue_content"]) ){
             return returnJson(-1,'咨询问题不能为空');
         }
    	 // 判断用户是否存在
    	  $users = objectToArray(Users::where(['user_id'=>$data["user_id"]])->select("username",'openid')->first());
    	 if(empty($users)){
    	 	return returnJson(-1,'用户不存在');
    	 }
    	 if(empty($users["openid"])){
    	 	return returnJson(-1,'缺少必要参数,很抱歉您目前无法支付，请退出在重新进入！');
    	 }

    	 // 查询导师信息
    	 $tutor = objectToArray(Tutor::where(['id'=>$data["tutor_id"]])->select("tutor_price",'tutor_name')->first());
    	 if(empty($tutor)){
    	 	return returnJson(-1,'该导师不存在哦');
    	 }
        
    	 if($tutor["tutor_price"] != $data["price"]){
    	 	return returnJson(-1,'价格错误！');
    	 }

          // =-----------------
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

     	
      	// 订单编号
        $addData['order_sn'] =  md5(time().rand(000,999).rand(000,999));
        $addData['user_id'] = $data['user_id'];
        $addData['tutor_id'] = $data['tutor_id'];
        $addData['create_time'] = time();
        $addData["order_type"] = 1; //订单类型： 1 微信支付订单/2,鸽蛋订单/3活动订单
        $addData["pay_type"] = 1; //支付类型 1 微信/2鸽蛋'
        $addData["pay_status"] = 1; //支付状态 １未支付／２已支付',
        $addData["grade"] = $data['grade']; //年级
        $addData["name"] = $data["name"]; //姓名
        $addData["issue_content"] = $data["issue_content"]; //咨询问题
        $addData["phone"] = $data["phone"];//时间
        // $addData["connect_date"] = strtotime($data['connect_date']); //沟通日期
        $addData["connect_time"] = strtotime($times); //沟通时间
        $addData['order_money'] = $tutor["tutor_price"]; // 价格
        $addData["form_id"] = $data['form_id'];
         writelog($addData,'order','create_order');
     	 // 生成订单
        $order_id = DB::table("lgp_myask_order")->insertGetId($addData);
         writelog('订单id:'.$order_id,'order','create_order');
        if($order_id){
        	  $returnData = $this->pay( $users['openid'], $addData['order_sn'], $tutor);
        	  writelog($$returnData,'order','create_order');
            return $returnData ;
        }else{
            return json_msg(-2,'订单生成失败');
        }

    }

     /**
     * @function [支付方法]
     * @Author   JFY
     * @DateTime 2018-12-06T10:39:20+0800
     * @return   [type]                   [description]
     */
    public function three_pay( $openid, $order_id, $resources_info ,$pay_price){
        $data = $this->requests->getQueryParams();
        // 小程序appid
        $appid =$this->appid; // config('APPID')
        // 用户openid
        $openid = $openid;
        // 商户号
        $mch_id = $this->shanghu;//config('SHANGHU');

        $key = $this->key;//config('KEY');
       

        // 商户订单号
        $out_trade_no = $order_id;

        // 商品描述
        $body = $resources_info['tutor_name'];

        // 价格(分)
        $total_fee = $pay_price * 100;
        $three_notify = 'https://www.highschool86.com/order_three_notify';

        
        $pay_class = new WeixinPay( $appid, $openid, $mch_id, $key, $out_trade_no , $body, $total_fee , $three_notify);
        $res = $pay_class->pay();
         writelog('----------统一订单生成返回结果------------------','three_pay','three_pay');
         writelog($res,'three_pay','three_pay');
          $res['order_sn'] = $order_id;
           writelog($res,'three_pay','three_pay');
          Db::table('lgp_order_again_pay')->insert($res);
        if($res){
            // ============需要在支付成功的回调函数里面执行=====================
            // $editData['status'] = 1;
            // $editData['price'] = $resources_info['price'];
            // $this->order_model->save($editData,['id'=>$order_id]);
            // ============需要在支付成功的回调函数里面执行=====================
            return returnJson(2,'支付成功',$res);
        }else{
            return returnJson(-1,'支付失败');
        }
    }

   /**
     * @function [支付方法]
     * @Author   JFY
     * @DateTime 2018-12-06T10:39:20+0800
     * @return   [type]                   [description]
     */
    public function pay( $openid, $order_id, $resources_info ){
        $data = $this->requests->getQueryParams();
        // 小程序appid
        $appid =$this->appid; // config('APPID')
        // 用户openid
        $openid = $openid;
        // 商户号
        $mch_id = $this->shanghu;//config('SHANGHU');

        $key = $this->key;//config('KEY');
       

        // 商户订单号
        $out_trade_no = $order_id;

        // 商品描述
        $body = $resources_info['tutor_name'];

        // 价格(分)
        $total_fee = $resources_info['tutor_price'] * 100;

      	
        $pay_class = new WeixinPay( $appid, $openid, $mch_id, $key, $out_trade_no , $body, $total_fee );
        $res = $pay_class->pay();
         writelog('----------统一订单生成返回结果------------------','pay','notify');
         writelog($res,'pay','notify');
        if($res){
            // ============需要在支付成功的回调函数里面执行=====================
            // $editData['status'] = 1;
            // $editData['price'] = $resources_info['price'];
            // $this->order_model->save($editData,['id'=>$order_id]);
            // ============需要在支付成功的回调函数里面执行=====================
            return returnJson(2,'支付成功',$res);
        }else{
            return returnJson(-1,'支付失败');
        }
    }

     /**
     * @function [支付回调方法]3.0
     * @Author   JFY
     * @DateTime 2018-12-06T10:39:20+0800
     * @return   [type]                   [description]
     */
    public  function three_notify(){
        writelog('----------支付回调------------------','three_notify','three_notify');
        $postXml = file_get_contents('php://input');
        writelog($postXml,'three_notify','three_notify');
        if (empty($postXml)) {
            return false;
        } 
        $attr = $this->xmlToArray($postXml);
        writelog($attr,'three_notify','three_notify');

        // // 说明支付成功，可以修改订单状态了
        if($attr['result_code']=='SUCCESS'){
            $editData["pay_status"] = 2; //订单改为
            $editData['pay_time'] = time();
            $editData['order_status'] = 1;
            // $editData["transaction_id"] = $attr['transaction_id'];
            $editData["pay_price"] = $attr['total_fee'] / 100; //实际支付金额
            $order_sn = $attr['out_trade_no'];
            // 查询订单信息
            $order_info  = objectToArray(DB::table('lgp_home_order as o')
                ->leftJoin('lgp_home_users as u', 'u.user_id', '=', 'o.user_id')
                ->where('o.order_sn',$order_sn)
                ->first(['o.order_sn','o.order_type_title','o.type','u.mobile','u.username','o.serve_content_id','o.pay_price'])
            );
             writelog($order_info,'three_notify','three_notify');
             // 判断是否是彬享计划 如果是彬享计划订单为已完成
             if(!empty($order_info) && $order_info['type'] == 4){
                 $editData['order_status'] = 2;
             }

            writelog($editData,'three_notify','three_notify');
            DB::table("lgp_home_order")->where('order_sn',$attr['out_trade_no'])->update($editData);
           

             if(!empty( $order_info )){
                  // 发送邮件
                $email_param = array(
                    'type'  =>  '用户订单',
                    'subject'   => '小灰鸽订单', // 主题
                    'body'  => "订单号：{$order_info['order_sn']}，订单类型：{$order_info['order_type_title']}，姓名：{$order_info['username']}，电话：{$order_info['mobile']}，价格：{$order_info['pay_price']}", // 内容
                    'userArr' => $this->email_user_list, // 用户
                    'title' => '小灰鸽订单', // 标题
                    'user'  => $order_info['username'], // 用户
                    'mobile'    => $order_info['mobile'], // 电话
                );
                $res = \App\Http\Controllers\ToolController::SendMail( $email_param );
                writelog($email_param,'three_notify','three_notify');
                writelog($res,'three_notify','three_notify');
                $this->delAgainPay($order_info['order_sn']);
             }
             $str = '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
             writelog('返回数据','three_notify','three_notify');
             return $str;

        }

    }

    public  function delAgainPay($order_sn){
     $id  = DB::table('lgp_order_again_pay')->where('order_sn',$order_sn)->value('id');
     if(!empty($id)){
        DB::table('lgp_order_again_pay')->where('id',$id)->delete();
     }


    }

    /**
     * @function [支付回调方法]
     * @Author   JFY
     * @DateTime 2018-12-06T10:39:20+0800
     * @return   [type]                   [description]
     */
    public  function notify(){
        writelog('----------支付回调------------------','pay','notify');
        $postXml = file_get_contents('php://input');
        writelog($postXml,'pay','notify');
        if (empty($postXml)) {
            return false;
        } 
        $attr = $this->xmlToArray($postXml);
        writelog($attr,'pay','notify');
        // // 说明支付成功，可以修改订单状态了
        if($attr['result_code']=='SUCCESS'){
            $editData["pay_status"] = 2; //订单改为
            $editData['pay_time'] = time();
            $editData["transaction_id"] = $attr['transaction_id'];
            $editData["price"] = $attr['total_fee'] / 100; //实际支付金额
            writelog($editData,'pay','notify');
            DB::table("lgp_myask_order")->where('order_sn',$attr['out_trade_no'])->update($editData);
            return true;

        }

    }


     //xml转换成数组
    private function xmlToArray($xml) {
        //禁止引用外部xml实体 
        libxml_disable_entity_loader(true);
        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $val = json_decode(json_encode($xmlstring), true);
        return $val;
    }

    /**
    *订单列表
    *@param
    *@param
    **/
    public  function order_list(){
        $data = $this->requests->getQueryParams();
        // $data["user_id"] = 15;
        if(empty($data["user_id"])){
        	return returnJson(-1,'缺少参数');
        }

     	// 查询已经支付的订单
        $orderTutor = MyaskOrder::leftJoin('lgp_home_tutor as t', 't.id', '=', 'lgp_myask_order.tutor_id')
         		   ->leftJoin('lgp_home_education as e', 't.education_id', '=', 'e.id')
         		   ->where(["is_del"=>1,'user_id'=>$data["user_id"],'t.status'=>1])
         		   ->where("pay_status",2)
         		   ->orderBy("lgp_myask_order.create_time",'DESC')
         		   ->select('lgp_myask_order.id','lgp_myask_order.connect_time','t.tutor_name','t.id as tutor_id','t.tutor_profile','t.tutor_school','t.tutor_major','t.tutor_price','t.tutor_label','e.education_name')
         		   ->paginate(5)->toArray();

         $timeDate = time();
         foreach ($orderTutor["data"] as $key => &$value) {
                if(!empty($value["tutor_label"])){
                  $value["tutor_label"] = str_replace('，', ',', $value["tutor_label"]);
                  $value["tutor_label"] = explode(',', $value["tutor_label"]);
                }
                // 判断是否关注
                $value["is_follow"] = Follow::where(["user_id"=>$data["user_id"],"tutor_id"=>$value["tutor_id"]])->exists();
                // 是否评价
                $value["is_evaluate"] =  DB::table("lgp_myask_evaluate")->where("order_id",$value["id"])->exists();
                // 是否退款 
				$value["refund_status"] =  DB::table("lgp_myask_refund_order")->where("order_id",$value["id"])->value("is_handle");
				if(empty($value["refund_status"])){
					$value["refund_status"] = 0;
				}

                $value["date_time"] = date("Y-m-d H:i",$value["connect_time"]);
                // 判断预约时间是否过期 过期为true 未过期false
                $value["is_overtime"] = true;
                // 判断当前日期是否小于预约日期
                if($value["connect_time"] > $timeDate){
                	// $value["date_time"] = date("Y-m-d",$value["connect_date"])." ".date("H:i",$value["connect_time"]);
                	$value["is_overtime"] = false;
                }
                // else if($value["connect_time"] == $timeDate){
                // 	// 预约日期等于当前日期
                // 	// $time = strtotime(date("H:i"));
                // 	// // 判断时间是否大于等于当前日期
                // 	// if($time >= $value["connect_time"]){
                // 		// $value["date_time"] = date("Y-m-d",$value["connect_date"])." ".date("H:i",$value["connect_time"]);
                // 		$value["is_overtime"] = true;
                // 	// }
                // }
        }

          return returnJson(2,'success',$orderTutor); 
    }

     /**
    *订单详情
    *@param
    *@param
    **/
    public  function order_details(){
    	$data = $this->requests->getQueryParams();
        if(empty($data["order_id"])){
        	return returnJson(-1,'缺少参数');
        }
        $order = objectToArray(MyaskOrder::where("id",$data["order_id"])->select('id',"name",'phone','grade','connect_time','issue_content')->first());
        if(empty($order)){
        	return returnJson(-1,'订单不存在,请刷新页面！');
        }

        $order["connect_date"] =   date('Y-m-d',$order["connect_time"]);
        $order["connect_times"] =  date("H:i",$order["connect_time"]);
         // 是否退款 
		$order["refund_status"] =  DB::table("lgp_myask_refund_order")->where("order_id",$order["id"])->value("is_handle");
		if(empty($order["refund_status"])){
			$order["refund_status"] = 0;
		}
        $reDate = array();
        // 所在年级[1无2小学3初中4高中5本科6研究生7硕士8博士9中学]
        $stage = array(9=>"中学",5=>"本科",7=>"硕士",8=>"博士");
        $reDate["grade"] = $stage;
        $reDate["order"] = $order;
        return returnJson(2,'success',$reDate); 
    }

     /**
    *订单修改
    *@param
    *@param
    **/
    public  function order_update(){
    	$data = $this->requests->getQueryParams();
    	 writelog($data ,'order','order_update');
        // $data["order_id"] =4;
        // $data['user_id'] = 15;
        // $data['tutor_id'] = 1;
        // $data['update_time'] = time();
        // $data["grade"] = 1;
        // $data["connect_date"] = '2020-01-15'; //沟通日期
        // $data["connect_time"] = '21:00'; //沟通时间
        // $data["name"] = "测试122";
        // $data["phone"] = "15210978147";
        // $data["issue_content"] = "这是咨询的问题。。。。。。。。";
         $date = empty($data["connect_date"]) ? 0 : $data["connect_date"] ;
         $time = empty($data["connect_time"]) ? 0 : $data["connect_time"] ;
         if(empty($data["user_id"]) || empty($data["tutor_id"]) || empty($data["phone"]) || empty($data["name"])  || empty($data["issue_content"]) || empty($date) || empty($time)){
            return returnJson(-1,'缺少参数');
         }
        $order = objectToArray(MyaskOrder::where("id",$data["order_id"])->select('id',"name",'phone','grade','connect_time','issue_content')->first());
        if(empty($order)){
        	return returnJson(-1,'订单不存在,请刷新页面！');
        }

       
        // =-----------------
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
        // --------------


        $updateData['user_id'] = $data['user_id'];
        $updateData['tutor_id'] = $data['tutor_id'];
        $updateData['update_time'] = time();
        $updateData["grade"] = $data['grade']; //姓名
        $updateData["name"] = $data["name"]; //姓名
        $updateData["issue_content"] = $data["issue_content"]; //咨询问题
        $updateData["phone"] = $data["phone"];//时间
        // $updateData["connect_date"] = strtotime($data['connect_date']); //沟通日期
        $updateData["connect_time"] = strtotime($times);  //沟通时间

        writelog($updateData ,'order','order_update');
        $res = DB::table('lgp_myask_order')->where('id', $data["order_id"])->update($updateData);

        writelog('修改结果：'.$res ,'order','order_update');
        if($res){
         	 return returnJson(2,'success');
        }else{
         	return returnJson(-1,'修改失败！');
        }
       
    }


    /**
    *订单申请退款
    *@param
    *@param
    **/
    public  function order_refund(){
        writelog('=============订单申请退款==================' ,'order','order_refund');
    	$data = $this->requests->getQueryParams();
    	// $data["order_id"] = 4;
        writelog($data ,'order','order_refund');
    	if(empty($data["order_id"])){
    		return returnJson(-1,'缺少参数');
    	}
    	$order = objectToArray(MyaskOrder::where("id",$data["order_id"])->select('id',"name",'price')->first());
        if(empty($order)){
        	return returnJson(-1,'订单不存在,请刷新页面！');
        }
        $refundData = array();
        $refundData['add_time'] = time();
        $refundData['order_id'] = $data["order_id"];
        $refundData['is_handle'] = 1; //是否处理: 1 退款中（未处理） /2 已退款/3取消退款/4拒绝退款
        writelog($refundData ,'order','order_refund');
        $exists =  DB::table("lgp_myask_refund_order")->where(["order_id"=>$order["id"],'is_handle'=>1])->exists();
        if($exists){
        	return returnJson(-1,'您已申请退款，请不要重复操作！');
        }
        // 退款id
        $res = DB::table("lgp_myask_refund_order")->insert($refundData);
         writelog($res ,'order','order_refund');
      	if($res){
         	 return returnJson(2,'success');
        }else{
         	return returnJson(-1,'申请失败！');
        }

    }

     /**
    *获取年级
    *@param
    *@param
    **/
    public function getGrade(){
    	$grade = array(9=>"中学",5=>"本科",7=>"硕士",8=>"博士");
        $data["grade"] = $grade;
        return returnJson(2,'success',$data);
    }

    /**
    *评价
    *@param
    *@param
    **/
    public function evaluate(){

     	$data = $this->requests->getQueryParams();
    	// $data["order_id"] = 4;
    	if(empty($data["order_id"]) ||empty($data["user_id"])){
    		return returnJson(-1,'缺少参数');
    	}
    	$addData["order_id"] = $data["order_id"];
    	$addData["evaluate"] = $data["evaluate"];
    	$addData["total_evaluate"] = $data["total_evaluate"];
    	$addData["solve_problem"] = $data["solve_problem"];
    	$addData["unsolved_problem"] = $data["unsolved_problem"];
    	$addData["add_time"] = time();
    	$res = DB::table("lgp_myask_evaluate")->insert($addData);
    	if($res){
         	 return returnJson(2,'success');
        }else{
         	return returnJson(-1,'申请失败！');
        }

    }


    /**
    *删除订单
    *@param
    *@param
    **/
    public function delorder(){
    	$data = $this->requests->getQueryParams();
    	if(empty($data["order_id"])){
    		return returnJson(-1,'缺少参数');
    	}
    	$order = objectToArray(MyaskOrder::where("id",$data["order_id"])->select('id','is_del')->first());
        if(empty($order)){
        	return returnJson(-1,'订单不存在,请刷新页面！');
        }
        if($order["is_del"] == 2){
        	 return returnJson(2,'success');
        }

       $res =  DB::table('lgp_myask_order')->where('id', $data["order_id"])->update(['is_del' => 2]);
       if($res){
       	 return returnJson(2,'success');
       }else{
         return returnJson(-1,'申请失败！');
       }



    }

    // 获取导师价格
    public function getTutorPrice(){
        $data = $this->requests->getQueryParams();
        // $data["tutor_id"] = 1;
        // $data["user_id"] = 17;
        // $data["order_id"] = 31;
        if(empty($data["tutor_id"]) ||empty($data["user_id"])){
            return returnJson(-1,'缺少参数');
        }
        $tutor = objectToArray(Tutor::where("id",$data["tutor_id"])->select('id',"tutor_price",'tutor_name')->first());
        if(empty($tutor)){
            return returnJson(-1,'数据错误，请刷新界面！');
        }
        $grade = array(9=>"中学",5=>"本科",7=>"硕士",8=>"博士");
        $returnDate["grade"] = $grade;
        $returnDate["tutor"] = $tutor;
        $returnDate["order"] = array();
        if(!empty($data["order_id"])){
             $order =  objectToArray(DB::table('lgp_myask_order')->where(['id' => $data["order_id"],'tutor_id'=>$data["tutor_id"]])->select("id as order_id",'price','name','phone','grade','connect_time','issue_content')->first());
             if(empty($order)){
               return returnJson(-1,'订单不存在，请刷新界面！'); 
             }
             $order["connect_times"] = date("H:i",$order['connect_time']);
             $order["connect_date"] = date("Y-m-d",$order['connect_time']);
            
              $returnDate["order"] = $order;
        }
       
        return returnJson(2,'success',$returnDate);

    }


    // 2.0 开始
    // 创建订单
    public function  createtwo(){
           $data = $this->requests->getQueryParams();
           // writelog('========创建订单=============','order','createtwo');
           // writelog($data,'order','createtwo');
            // ----------测试数据开始-------------
            $data['user_id'] = 15;
            $data['tutor_id'] = 275;
            $data['create_time'] = time();
            $data["order_type"] = 1; //订单类型： 1 微信支付订单/2,鸽蛋订单/3活动订单
            $data["pay_type"] = 1; //支付方式
            $data["pay_status"] = 1; //支付状态
            $data["grade"] = 1;
            $data["connect_date"] = '2020-02-21'; //沟通日期
            $data["connect_time"] = '21:00'; //沟通时间
            $data["price"] = '100'; //沟通时间
            $data["name"] = "测试";
            $data["phone"] = "15210978147";
            $data["form_id"] = "15210978147";
            $data["issue_content"] = "这是咨询的问题。。。。。。。。";
            $data['type'] = 3; // type = 1(留学咨询 )  type = 2(文书)  type=3(语言 )
            $data['remark_id'] = 1; 
            // 1.留学咨询 remark_id  为0
            // 2. 文书 remark_id : 托福 1 ， 雅思 2（也就是筛序条件中的托福和雅思的yuyan_id）
            // 3.语言培训 : 也就是筛选条件中wenshu_id

        // ----------测试数据结束-------------

         $date = empty($data["connect_date"]) ? 0 : $data["connect_date"] ;
         $time = empty($data["connect_time"]) ? 0 : $data["connect_time"] ;

         if(empty($data["user_id"]) || empty($data["tutor_id"]) || empty($date) || empty($time)){
            return returnJson(-1,'缺少参数');
         }
          if(empty($data["name"])){
            return returnJson(-1,'姓名不能为空');
         }

         if(empty($data["phone"])){
            return returnJson(-1,'手机号不能为空');
         }

         if(empty($data["issue_content"]) ){
             return returnJson(-1,'咨询问题不能为空');
         }

         // 判断用户是否存在
          $users = objectToArray(Users::where(['user_id'=>$data["user_id"]])->select("username",'openid')->first());
        if(empty($users)){
            return returnJson(-1,'用户不存在');
         }
         if(empty($users["openid"])){
            return returnJson(-1,'缺少必要参数,很抱歉您目前无法支付，请退出在重新进入！');
         }
       
          // 查询导师信息
         $tutor = objectToArray(Tutor::where(['id'=>$data["tutor_id"]])->select("tutor_price",'tutor_name')->first());
         if(empty($tutor)){
            return returnJson(-1,'该导师不存在哦');
         }
       
         if($data['type'] == 1){ //留学咨询
             if($tutor["tutor_price"] != $data["price"]){
                return returnJson(-1,'价格错误！');
             }
         }else if($data['type'] == 2){ //文书
            // 查询文书价格
             $wenshu= objectToArray(DB::table('lgp_home_webshu_t')->where(['wenshu_t_tid'=>$data["tutor_id"],'wenshu_t_wid'=>$data['remark_id']])->first());
            if($wenshu['wenshu_t_money'] != $data["price"]){
                return returnJson(-1,'价格错误！');
             }
             $tutor["tutor_price"] = (double)$wenshu['wenshu_t_money'];
         }else if($data['type'] == 3){ //语言培训
                $yuyan_name  = '';
                if($data['remark_id'] == 1){
                    $yuyan_name = '托福';
                }else if($data['remark_id'] == 2){
                    $yuyan_name = '雅思';
                }
                $yuyan_t_money =DB::table('lgp_home_yuyan_t')->where('yuyan_t_tid',$data["tutor_id"])->where('yuyan_t_name', 'like', '%'.$yuyan_name.'%')->value('yuyan_t_money');
               
                if($yuyan_t_money != $data["price"]){
                   return returnJson(-1,'价格错误！');
                }
               $tutor["tutor_price"] = (double)$yuyan_t_money;

         }



          // =-----------------
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

            // 订单编号
        $addData['order_sn'] =  md5(time().rand(000,999).rand(000,999));
        $addData['user_id'] = $data['user_id'];
        $addData['tutor_id'] = $data['tutor_id'];
        $addData['create_time'] = time();
        $addData["order_type"] = 1; //订单类型： 1 微信支付订单/2,鸽蛋订单/3活动订单
        $addData["pay_type"] = 1; //支付类型 1 微信/2鸽蛋'
        $addData["pay_status"] = 1; //支付状态 １未支付／２已支付',
        $addData["grade"] = $data['grade']; //年级
        $addData["name"] = $data["name"]; //姓名
        $addData["issue_content"] = $data["issue_content"]; //咨询问题
        $addData["phone"] = $data["phone"];//时间
        // $addData["connect_date"] = strtotime($data['connect_date']); //沟通日期
        $addData["connect_time"] = strtotime($times); //沟通时间
        $addData['order_money'] = $tutor["tutor_price"]; // 价格
        $addData["form_id"] = $data['form_id'];
        $addData["type"] = $data['type'];
        $addData["remark_id"] = $data['remark_id'];
        echo "<pre>";
        var_dump($addData);
        die;
           writelog($addData,'order','createtwo');
        $order_id = DB::table("lgp_myask_order")->insertGetId($addData);
        if($order_id){
              $returnData = $this->pay( $users['openid'], $addData['order_sn'], $tutor);
              writelog($returnData,'order','createtwo');
            return $returnData ;
        }else{
            return json_msg(-2,'订单生成失败');
        }
    }
   

    // ========================3.0 开始===================
    // 创建订单(普通订单)
    public function createThree(){
        
        $data = $this->requests->getQueryParams();
        writelog('订单创建-----------------------','orderThree','createThree');
        writelog($data,'orderThree','createThree');
        // user_id 用户id,tutor_id导师id,order_type订单类型：1、普通类型，2、极速问答，3、文书订单
        if( empty($data["user_id"]) || empty($data["tutor_id"]) || empty($data["serve_content_id"]) || empty($data["order_type_title"]) || empty($data["order_type"]) ){
            return returnJson(-1,'缺少参数');
        }
    
        // // 判断用户是否存在
        $users = objectToArray(Users::where(['user_id'=>$data["user_id"]])->select("username",'openid')->first());
        if(empty($users)){
            return returnJson(-1,'用户不存在');
        }
        
        // 查询导师信息
        $tutor = objectToArray(Tutor::where(['id'=>$data["tutor_id"]])->select("tutor_price",'tutor_name')->first());
        if(empty($tutor)){
            return returnJson(-1,'该导师不存在哦');
        }
        // 订单类型
        switch ($data['order_type']) {
            case 1:
                $data['type'] = 1;
                break;
            case 2:
                $data['type'] = 2;
                break;
            case 3:
                $data['type'] = 3;
                break;
            default:
                # code...
                break;
        }
        
        $serve_content_idArr = explode( ',', $data['serve_content_id'] );


        unset($data['s']);
        unset($data['order_type']);
        
        //  用户id
        $data['user_id'] = $data['user_id'];
        // $data['user_id'] = 846;
        // 导师id
        $data['tutor_id'] = $data['tutor_id'];
        // $data['tutor_id'] = 1;
        // 服务内容id
        $data['serve_content_id'] = $data['serve_content_id'];
        // $data['serve_content_id'] = 1;
        // 服务内容表查询一下价格
        $serve_content_info =  objectToArray(DB::table("lgp_home_serve_content")->whereIn('id',$serve_content_idArr)->get(['serve_price']));
        // 判断当前用户是否是首单
        // ->where([ 
        //     ['pay_status',2 ],
        //     ['user_id',$data['user_id'] ],
        //     ['type','<>',2],
        // ])
        $order_exists =  objectToArray(DB::table("lgp_home_order")->where(['user_id'=>$data['user_id'] ,'pay_status'=>2])->where('type','<>',2)->exists());
        
        // 总价格
        $sum_price = 0;
        foreach (array_column($serve_content_info, 'serve_price') as $key => $val) {
            $sum_price += (double) $val;
        }

        $data['order_price'] = (double) $sum_price;
        // 首单折扣
        $order_discount =0;



        if( !$order_exists ){
            // 首单打6.9折
            $price = (double) $sum_price * self::$Discount;
            $order_discount = $data['order_price'] - $price;
        }
        
        $pay_price = $data['order_price'] - $order_discount;

        // 订单折扣
        $data['order_discount'] = sprintf("%.2f",$order_discount) ;
        // 产品名字
        $data['order_type_title'] = $data['order_type_title'];
        // $data['order_type_title'] = 111;
        // 目前年级
        if( isset($data['grade']) ){
            $data['grade'] = $data['grade'];
        }
        // $data['grade'] = 11;
        // 主要想沟通内容
        if( isset($data['connect_contents']) ){
            $data['connect_contents'] = $data['connect_contents'];
        }
         if(isset($data['remarks'])){
             $data['remarks'] = $data['remarks'];
        }
        // $data['connect_contents'] = 11111111111;
        // 创建时间
        writelog('------添加数据前-------','orderThree','createThree');
        $data['add_time'] = time();
        writelog($data,'orderThree','createThree');

        $order_id = DB::table("lgp_home_order")->insertGetId($data);
         writelog($order_id,'orderThree','createThree');
       

        // 订单编号
        $order_sn = 'L'.date("Ymd") .substr(date('His'),-4). substr($order_id, -5);
        writelog($order_sn,'orderThree','createThree');
        $editData['order_sn'] = $order_sn;
        $res = DB::table("lgp_home_order")->where('order_id',$order_id)->update($editData);
          writelog('返回结果'.$res,'orderThree','createThree');
        if($order_id){
            if( $pay_price != 0 ){
                $pay_price = sprintf("%.2f",$pay_price);
                // $pay_price = 0.01;
                $returnData = $this-> three_pay( $users['openid'], $order_sn, $tutor , $pay_price );
                // $returnData = $this->pay( $users['openid'], $order_sn, $tutor);
                writelog($returnData,'orderThree','createThree');
                return $returnData ;
            }
        }else{
            return json_msg(-2,'订单生成失败');
        }
        
        // if( $order_id && $res ){
        //     return returnJson(2,'订单生成成功',$order_id);
        // }else{
        //     return returnJson(-1,'订单生成失败');
        // }
    }

     /**
     * [order_rapidly 急速问答创建订单3.0]
     * @param string user_id      用户名id（必传）
     * @param string grade   目前年级（必传）
     * @param string connect_contents   主要想沟通内容
     * @return [type] [description]
     */
    public function order_rapidly(){
         $param = $this->requests->getQueryParams();
          $country_id = empty($param["country_id"]) ? 1 : $param["country_id"] ;
         // $param["user_id"] = 15;
         // $param["grade"] = '一年级';
         // $param['connect_contents'] = '主要相沟通问题';
        // user_id 用户id,tutor_id导师id,order_type订单类型：1、普通类型，2、极速问答，3、文书订单
        if( empty($param["user_id"]) || empty($param["grade"]) || empty($param['connect_contents']) ){
            return returnJson(-1,'缺少参数');
        }
         // // 判断用户是否存在
        $users = objectToArray(Users::where(['user_id'=>$param["user_id"]])->select("username",'openid')->first());
        if(empty($users)){
            return returnJson(-1,'用户不存在');
        }
       
        $data['user_id'] = $param['user_id'];
        $data['grade'] = $param['grade'];
        $data['remarks'] = $param['connect_contents'];
        $pay_price =  self::$rapidly_price;
        $data['order_price'] = $pay_price;
        $data['order_type_title'] = '急速问答';
        $data['add_time'] = time();
        $data['pay_status'] = 1;//待支付
        $data['order_status'] = 0;//待支付
        $data['type'] = 2;//急速问答
        $data['tutor_id'] = DB::table("lgp_home_tutor")->where(['status'=>'1','tutor_checked_status'=>2,'tutor_country'=>$country_id]) 
                            ->orderBy(DB::raw('RAND()')) 
                            ->value('id');
         
        $order_id = DB::table("lgp_home_order")->insertGetId($data);
         // 订单编号
        $order_sn = 'L'.date("Ymd") .substr(date('His'),-4). substr($order_id, -5);
        $editData['order_sn'] = $order_sn;
        $res = DB::table("lgp_home_order")->where('order_id',$order_id)->update($editData);
        if($order_id){
            $tutor['tutor_name'] = '急速问答';
            $returnData = $this-> three_pay( $users['openid'], $order_sn, $tutor , $pay_price );
            writelog($returnData,'order','order_rapidly');
            return $returnData ;
            
        }else{
            return json_msg(-2,'订单生成失败');
        }
    }


    /*
        完善信息初始化
    */
    public function orderinfoShow(){
        $data = $this->requests->getQueryParams();
        // 类型
        if( !isset($data['type']) ){
            return returnJson(-1,'缺少必要参数');
        }
        $type = $data['type'];

        $school_data = 
        [
                [
                    "title" => "小学",
                    "child" => [
                            "一年级",
                            "二年级",
                            "三年级",
                            "四年级",
                            "五年级",
                            "六年级",
                    ]
                ],
                [
                    "title" => "中学",
                    "child" => [
                            "七年级",
                            "八年级",
                            "九年级",
                    ]
                ],
                [
                    "title" => "高中",
                    "child" => [
                            "十年级",
                            "十一年级",
                            "十二年级",
                    ]
                ],
                [
                    "title" => "专科",
                    "child" => [
                            "一年级",
                            "二年级",
                            "三年级",
                    ]
                ],
                [
                    "title" => "本科",
                    "child" => [
                            "一年级",
                            "二年级",
                            "三年级",
                            "四年级",
                            "五年级",
                    ]
                ],
                [
                    "title" => "研究生",
                    "child" => [
                            "一年级",
                            "二年级",
                            "三年级",
                    ]
                ],
                [
                    "title" => "博士",
                    "child" => [
                            "已工作",
                    ]
                ]
        ];

        // 主要沟通内容
        $content_arr = [];
        $rapidly_price = 0;

        switch ( $type ) {
            // 初识留学
            case 1:
                $content_arr = ['申请时间规划', '院校具体情况', '留学未来发展', '留学生活情况', '其他'];
                # code...
                break;
            case 2:
            // 申请院校
                $content_arr = ['申请流程', '申请条件', '申请材料', '申请费用', '其他'];
                # code...
                break;
            case 3:
            // 申请专业
                $content_arr = ['专业录取情况', '专业细分方向', '跨专业申请', '专业就业情况', '其他'];
                # code...
                break;
            case 4:
            // 院校问答
                $content_arr = ['学校申请情况', '学校专业情况', '学校课程情况', '学校生活情况', '其他'];
                # code...
                break;
            case 5:
            // 录取对比问询
                # code...
                break;
            case 6:
            // 留学培训
                $content_arr = ['托福', '雅思', '小托福', 'SLATE', '其他'];
                # code...
                break;
             case 7:
            // 急速问答
                // $content_arr = ['托福', '雅思', '小托福', 'SLATE', '其他'];
                # code...
                 $rapidly_price = sprintf("%.2f",self::$rapidly_price);
                break;
            default:
                # code...
                break;
        }

        $return['school_data'] = $school_data;
        $return['content_arr'] = $content_arr;
        $return['rapidly_price'] = $rapidly_price;

        return returnJson(2,'success',$return);
        
    }

   /**
     * [index 订单列表]
     * @param string user_id      用户名id（必传）
     * @param string type      订单状态类型  1：未支付 2：已完成 3：进行中 4：已取消 全部订单传0或者不用传
     * @return [type] [description]
     */
    public function order_list_three(){
        $param = $this->requests->getQueryParams();
        // $param['user_id'] = 15;
        // $param["type"] = 4;
        if( !isset($param['user_id']) ){
            return returnJson(-1,'用户id不能为空');
        }
        // 判断用户是否存在
        if( !\App\Http\Controllers\UserController::checkUser( $param['user_id'] ) ){
            return returnJson(-1,'该用户不存在');
        }
        $field = [
                  'o.order_id',
                  'o.add_time',
                  'o.pay_price',
                  'o.order_type_title',
                  'o.serve_content_id',
                  'o.order_status', //订单状态：0、已确定（订单生成，默认）1、进行中，2、已完成，3、待支付，4、申请退款，5、退款完成，6、已取消
                  'o.pay_status', //支付状态：１待支付，２已支付
                  'o.tutor_reply_time', //导师回复时间type
                  'o.type', //订单类型：1、普通订单，2、极速问答,3文书订单
                  'o.user_id',
                  'o.bx_phone',
                  'o.bx_name',
                  'o.bx_class_num',
                  'o.bx_servers_learning',
                  'o.bx_servers_envir',
                  'o.bx_servers_job',
                  'o.bx_admit_to',
                  'o.order_price',
                  'tutor_name',
                  'tutor_school_id',
                  'tutor_profile',
                  'tutor_major',
                  'education_id',
                  's.school_name',
                  'e.education_name',
                  't.id as tutor_id'
              ];
         $where = [];
         $whereIn = [];
        if(isset($param['order_id']) && !empty($param['order_id'])){
            $where[] = ['o.order_id','=' ,$param['order_id']];
        }
         //未支付
        if(isset($param["type"]) && $param["type"] == 1){ 
            $where[] = ['o.pay_status','=', 1];
            $where[] = ['o.order_status','=', 0];
        }
       //已完成
        if((isset($param["type"]) && $param["type"]== 2)){ 
            $where[] = [ 'o.pay_status' ,'=', 2];
             $where[] = [ 'o.order_status' ,'=',2 ];
        }
         //进行中
         if(isset($param["type"]) && $param["type"] == 3){
            $where[] = ['o.pay_status','=', 2];
            $where[] =  [ 'o.order_status' ,'=',1 ];
        }
        // 已取消
        if(isset($param["type"]) && $param["type"] == 4){
            $where[] =  [ 'o.order_status' ,'=',6 ];
        }
        $where[] = ['o.user_id','=',$param['user_id']];
        $orderList  = objectToArray(DB::table('lgp_home_order as o')->where(function ($query) use($where) {
                                if ($where) {
                                    $query->Where($where);
                                }
                            }) 
                            ->leftJoin('lgp_home_tutor as t', 't.id', '=', 'o.tutor_id')
                            ->leftJoin('lgp_home_school as s', 's.id', '=', 't.tutor_school_id')
                            ->leftJoin('lgp_home_education as e', 'e.id', '=', 't.education_id')
                            ->orderBy("o.add_time",'DESC')
                            // ->where('o.pay_status',2)
                            ->select( $field)
                            ->paginate(self::$pageNum)->toArray());
        $orderList = objectToArray($orderList['data']);
       
        foreach ($orderList as $key => &$value) {
            $value['is_evaluate'] = false; //是否评论 false未评论 true已评论 （已完成）
            $value['is_tutor_questions'] = false; //学鸽是否回复 false （等待学鸽回复） true 免费问询1次
            $value['end_time'] = 0; //无限问答，与未支付倒计时 时间为结束时间  格式：时间戳（未支付，无限问答）
            $value["is_ask"] =  false; //用户是否使用赠送问答 false 未使用 true已使用 (已完成)
            $value['is_user_questions'] = false; //用户是否能继续提问(无限问答) false 不能继续提问 true能继续提问（无限问答）
            if($value['type'] != 4){

         
                $serve_content_id = explode(',', $value['serve_content_id'] );
              // 服务内容类型：1:一问一答,2:在线指导，3：远程指导A，4：远程指导B  ,5:预约监理导师，首次沟通 6:帮我监理留学中介,7,头脑风暴，8无线问答，9撰写文书，10修改文书
                $type = DB::table('lgp_home_serve_content')->whereIn("id",$serve_content_id)->value('serve_type');
                $value['order_type'] = $type;
                // 未支付
                if($value['pay_status'] == 1 ){
                    $value['end_time'] = $value['add_time'] + self::$unpaytime; //订单支付结束时间 时效一个小时
                }
                // 已完成
                if($value['pay_status'] == 2 && $value['order_status'] == 2){
                     $value['is_tutor_questions'] = true;
                      // 没有订单中问询的信息  1:一问一答,2:在线指导，3：远程指导A，4：远程指导B  ,5:预约监理导师，首次沟通\r\n6:帮我监理留学中介,7,头脑风暴，8无线问答，9撰写文书，10修改文书,11:直播院校（1v1）,12:院校问答,13:录取对比问询',
                    if($type == 9 ||  $type == 10 ||  $type == 6 || $type==7  ||  $type==11 ){
                       $value['is_tutor_questions'] = false;
                    }
                    // 判断是否评价
                     $value["is_evaluate"] = DB::table('lgp_home_order_evaluate')->where("order_id",$value['order_id'])->exists();
                     // 除 9撰写文书，10修改文书 急速问答  6:帮我监理留学中介之外判断是否使用免费询问
                     // if( ($type != 9 ||  $type != 10) && $value['type'] != 2){
                     if( ($type != 9 ||  $type != 10 ||  $type != 6)  &&  $value['type'] != 2){  
                        // 判断是否超过赠送时效
                        // $endtime = $value['tutor_reply_time'] + self::$timeNum;
                         // 判断是否超过赠送时效
                        if($type == 2){ //急速問答 為回復后24小時后+48小小時
                             $endtime = $value['tutor_reply_time'] + self::$Infinitetime + self::$timeNum;
                        }else{
                             $endtime = $value['tutor_reply_time'] + self::$timeNum;

                        }

                        $value['end_time'] = $endtime;
                        if(time() < $endtime){
                            // 判断用户是否提问 type=1 为赠送问答
                            $value["is_ask"] = DB::table('lgp_home_order_enquire')->where(["order_id"=>$value['order_id'],'type'=>1])->exists();
                        }else{
                            $value["is_ask"] = true;
                        }
                    }
                }

                // 进行中 
                if($value['pay_status'] == 2 && $value['order_status'] == 1){

                     // 判断学鸽是否回复 如果回复 // 判断学鸽是否回复 如果回复 
                    if(!empty($value['tutor_reply_time']) ){
                         $value['is_tutor_questions'] = true;
                    //------------------ 普通订单和文书订单start----------------------------
                        if($value['type'] == 1 || $value['type'] == 3 ){
                                 //无限问答订单
                                 if($type== 2){
                                     // 获取无限订单结束时间 导师回复后24小时内可以随便提问
                                    $Infinitetime = $value['tutor_reply_time'] + self::$Infinitetime; 
                                    //  无限问答的订单需要前台页面展示倒计时
                                    $value['end_time'] = $Infinitetime;
                                   // 判断无限问答订单导师回复时间+24小时是否大于当前时间 如果大于可以继续问答 否则订单完成
                                    if(time() < $Infinitetime){
                                       $value['is_user_questions'] = true;
                                     }else{
                                        // 如果无限问答订单导师回复时间小于当前时间，修改为订单已完成
                                          $res = self::updateOrder($value['order_id'],2);
                                          $value['order_status'] = 2;
                                     }
                                 }else{
                                    // 不是无限问答的订单则在导师回复后修改订单完成
                                    // 订单导师回复后已经超过48小时 订单自动修改已完成  导师回复之后订单完成（除：无限问答）
                                    $res = self::updateOrder($value['order_id'],2);
                                    $value['order_status'] = 2;
                                 }
                          //------------------ 普通订单和文书订单end----------------------------
                        }elseif($value['type'] == 2){//急速问答订单  导师回复订单完成
                                $res = self::updateOrder($value['order_id'],2);
                                $value['is_tutor_questions'] = true;
                                $value['order_status'] = 2;
                        }
                    }
                }
          }
            $value['add_time'] = date('Y-m-d H:i:s' , $value['add_time']);
        }

        $data['order'] = $orderList;
        return returnJson(2,'success',$data);
    }
     /**
     * [index 海外鸽服 留学培训添加]
     * @param int user_id     用户名id（必传）
     * @param int mobilr      手机号（必传）
     * @param int type        海外鸽服:1,接送机服务 2,校园探访 3,寄宿家庭 .  留学培训： 4,留学培训（必传）
     * @return [type] [description]
     */
    public  function addServe(){
         $param = $this->requests->getQueryParams();
         // $param['type'] = 4;
         // $param['user_id'] = 15;
         // $param['mobile'] = 15210978147;
         // -------接送机服务----------、
         // $param['is_receive'] = '1';
         // $param['city_name'] = '接送机服务';
         // $param['airport_name'] = '接送机服务';
           // -------校园探访----------、
         // $param['visit_school'] = '需要探访的院校';
         // $param['school_address'] = '学校所在位置';
          // ------- 寄宿家庭----------、
         // $param['now_lodging_family'] = '目前寄宿家庭';
         // $param['hope_lodging_family'] = '希望寄宿家庭所在地区';
          // --------留学培训---------、
         // $param['grade'] = '年级';
         // $param['class'] = '主修课程';
         // $param['education'] = '阶段';
         // ================
         unset($param['s']);
         if(empty($param['mobile']) || empty($param['user_id']) || empty($param['type'])){
             return returnJson(-1,'缺少必要参数');
         }
         $param['serve_order_type'] = 2; //海外鸽服
         $param['add_time'] = time(); //订单创建时间
         if($param['type'] == 1 ){//1,接送机服务
                // if(empty($param['is_receive'])){
                //      return returnJson(-1,'请选择接机或者送机');
                // }
                // if(empty($param['city_name'])){
                //      return returnJson(-1,'请填写城市名');
                // }
                // if(empty($param['airport_name'])){
                //      return returnJson(-1,'请填写机场名');
                // }

              $res =  DB::table("lgp_home_service_order")->insert($param);
              if($res){
                return returnJson(2,'success');
              }else{
                return json_msg(-2,'提交失败');
              }
         }elseif($param['type'] == 2){//2,校园探访
             // if(empty($param['visit_school'])){
             //         return returnJson(-1,'请填写需要探访的院校');
             //    }
             // if(empty($param['school_address'])){
             //         return returnJson(-1,'请填写学校所在位置');
             //    }
              $res =  DB::table("lgp_home_service_order")->insert($param);
              if($res){
                return returnJson(2,'success');
              }else{
                return json_msg(-2,'提交失败');
              }

         }elseif($param['type'] == 3){ //3,寄宿家庭
             // if(empty($param['now_lodging_family'])){
             //         return returnJson(-1,'请填写目前寄宿家庭');
             //    }
             // if(empty($param['hope_lodging_family'])){
             //         return returnJson(-1,'请填写希望寄宿家庭所在地区');
             //    }
              $res =  DB::table("lgp_home_service_order")->insert($param);
              if($res){
                return returnJson(2,'success');
              }else{
                return json_msg(-2,'提交失败');
              }
         }elseif($param['type'] == 4){ //4,留学培训
             $param['serve_order_type'] = 1; //留学培训
             // if(empty($param['education'])){
             //    return returnJson(-1,'请选择阶段');
             // }
             // if(empty($param['grade'])){
             //         return returnJson(-1,'请选择年级');
             //    }
             // if(empty($param['class'])){
             //         return returnJson(-1,'请选择主修课程');
             //    }
              $res =  DB::table("lgp_home_service_order")->insert($param);
              if($res){
                return returnJson(2,'success');
              }else{
                return json_msg(-2,'提交失败');
              }

         }else{
            return json_msg(-1,'缺少订单类型');

         }

    }
    
     /**
     * [order_details_three  3.0订单详情]
     * @param int user_id     用户名id
     * @param int order_id     订单id
     * @return [type] [description]
     */
    public function order_details_three(){
        $param = $this->requests->getQueryParams();
        // $param['order_id'] =5;
        // $param['user_id'] = 15;
        if(empty($param['order_id']) || empty($param['user_id']) ){
            return returnJson(-1,'缺少必要参数');
        }
        // 判断用户是否存在
        if( !\App\Http\Controllers\UserController::checkUser( $param['user_id'] ) ){
            return returnJson(-1,'该用户不存在');
        }
        /*
            order_status : 订单状态：0、已确定（订单生成，默认）1、进行中，2、已完成，3、待支付，4、申请退款，5、退款完成，6、已取消
            pay_status :   支付状态：１待支付，２已支付
            未支付  pay_status=1 && order_status = 0
            已完成  pay_status'= 2 && order_status = 2
            进行中  pay_status= 2 && order_status = 1
            已取消  order_status = 6
            退款中  pay_status= 2 && order_status = 4
            退款完成 order_status = 5
        */
        $field = [
            'order_id',
            'order_sn',
            'user_id',
            'tutor_id',
            'serve_content_id',
            'order_price',
            'pay_price',
            'order_discount',
            'order_status',
            'tutor_reply',
            'pay_status',
            'order_type_title',
            'grade',
            'type',
            'connect_contents',
            'tutor_reply_time',
            'tutor_reply', 
            'remarks', 
            'add_time',
        ];

        // 订单信息
        $order_info =   objectToArray(DB::table('lgp_home_order')->where('order_id',$param['order_id'])->get( $field )->first());
        if(empty($order_info)){
            return returnJson(-1,'订单不存在,请刷新界面！');
        }
        if( $order_info['pay_status'] ==1 && $order_info['order_status'] == 0 ){
            // 未支付
            $order_info['msg'] = '未支付';
        }elseif( $order_info['pay_status']==2 && $order_info['order_status'] == 2 ){
            // 已完成
            $order_info['msg'] = '已完成';
        }elseif( $order_info['pay_status']==2 && $order_info['order_status'] == 1 ){
            // 进行中
            $order_info['msg'] = '进行中';
        }elseif( $order_info['order_status'] == 6 ){
            // 已取消
            $order_info['msg'] = '已取消';
        }elseif( $order_info['pay_status']==2 && $order_info['order_status'] == 4 ){
            // 退款中
            $order_info['msg'] = '退款中';
        }elseif( $order_info['order_status']==5 ){
            // 退款完成
            $order_info['msg'] = '退款完成';
        }
         // 服务内容类型：1:一问一答,2:在线指导，3：远程指导A，4：远程指导B  ,5:预约监理导师，首次沟通\r\n6:帮我监理留学中介,7,头脑风暴，8无线问答，9撰写文书，10修改文书,11:直播院校（1v1）,12:院校问答,13:录取对比问询
        $serve_type = array(
            1=>'一问一答',
            2=>'在线指导',
            3=>'远程指导A',
            4=>'远程指导B',
            5=>'视频指导',
            6=>'监理留学中介',
            7=>'选择的文书',//头脑风暴
            8=>'无限问答', //无限问答
            9=>'选择的文书',
            10=>'选择的文书',//修改文书
            11=>'直播院校（1v1',
            12=>'院校问答',
            13=>'录取对比问询',
        );
        $order_info['is_tutor_questions'] = false; //订单中导师是否回复 false 导师未回复  true导师已回复 (进行中)
        $order_info['end_time'] = 0;   //无限问答，与未支付倒计时 时间为结束时间  格式：时间戳(未支付，无限问答)
        $order_info["is_ask"] = false; //判断用户是否已经使用赠送的一次提问  false 未使用 true已经使用 （已完成）
        $order_info['is_evaluate'] = false; //是否评论 false未评论 true已评论 （已完成）
        $value['is_questions'] = false; //无限问答订单是否可以继续问询 false不可以问询  true可以问询（进行中）
        $serve_content_id = explode(',', $order_info['serve_content_id'] );

        // 获取订单类型服务内容类型
        $type = DB::table('lgp_home_serve_content')->whereIn("id",$serve_content_id)->value('serve_type');
      
        if($order_info['type'] == 2){
            $order_info['serve_type'] = '急速问答';
        }else{

            $order_info['serve_type'] = $serve_type[$type]; //服务方式
        }

        $order_info['order_type'] = $type;
       
        // 已完成
        if($order_info['pay_status'] == 2 && $order_info['order_status'] == 2){
            $order_info['is_tutor_questions'] = true;

             // 没有订单中问询的信息  1:一问一答,2:在线指导，3：远程指导A，4：远程指导B  ,5:预约监理导师，首次沟通\r\n6:帮我监理留学中介,7,头脑风暴，8无线问答，9撰写文书，10修改文书,11:直播院校（1v1）,12:院校问答,13:录取对比问询',
             // if($type == 9 ||  $type == 10 ||  $type == 6 || $type==7  ||  $type==11 ){
             //       $order_info['is_tutor_questions'] = false;
             //    }
            // 判断是否评价
            $order_info["is_evaluate"] = DB::table('lgp_home_order_evaluate')->where("order_id",$order_info['order_id'])->exists();
             // 除 9撰写文书，10修改文书 急速问答 之外判断是否已经使用一次提问（文书类型： 头脑风暴送赠送一次提问  其他的送头脑风暴 线下服务线上不用管）
             // if( ($type != 9 ||  $type != 10) && $order_info['type'] != 2){
            if( ($type != 9 ||  $type != 10 ||  $type != 6)  &&  $order_info['type'] != 2){  
                // 判断是否超过赠送时效
                // $endtime = $order_info['tutor_reply_time'] + self::$timeNum;
                 // 判断是否超过赠送时效
                if($type == 2){ //急速問答 為回復后24小時后+48小小時
                     $endtime = $order_info['tutor_reply_time'] + self::$Infinitetime + self::$timeNum;
                }else{
                     $endtime = $order_info['tutor_reply_time'] + self::$timeNum;

                }
                if(time() < $endtime){
                    // 判断用户是否提问 type=1 为赠送问答
                    $order_info["is_ask"] = DB::table('lgp_home_order_enquire')->where(["order_id"=>$order_info['order_id'],'type'=>1])->exists();
                }else{
                     $order_info["is_ask"] = true; 
                }
            }

        } 
        // 进行中
        if($order_info['pay_status'] == 2 && $order_info['order_status'] == 1){
             // 学鸽已经回复
            if(!empty($order_info['tutor_reply_time'])){
                $order_info['is_tutor_questions'] =  true;//学鸽已经回复
                // 普通订单和文书订单start
                if($order_info['type'] == 1 || $order_info['type'] == 3 ){
                    // 无限问答订单
                     if($type== 2){
                         // 获取无限订单结束时间 导师回复后24小时内可以随便提问
                           $Infinitetime = $order_info['tutor_reply_time'] + self::$Infinitetime; 
                         //  无限问答的订单需要前台页面展示倒计时
                          $order_info['end_time'] = $Infinitetime;
                         // 判断无限问答订单有效时间是否大于当前时间
                         if(time() < $Infinitetime){
                            $order_info['is_questions'] = true;
                         }else{
                            // 如果无限问答订单导师回复后+24小时时间小于当前时间，修改为订单已完成
                             $res = self::updateOrder($order_info['order_id'],2);
                             $order_info['order_status'] = 2;
                         }
                     }else{
                        // 不是无限问答的订单则在导师回复后修改订单完成（除：无限问答）
                         $res = self::updateOrder($order_info['order_id'],2);
                         $order_info['order_status'] = 2;
                     }
                   

                }elseif($order_info['type'] == 2){//急速问答订单  导师回复订单完成
                     $res = self::updateOrder($order_info['order_id'],2);
                     $order_info['order_status'] = 2;
                }
            }
        }
        $data['order'] = $order_info;
        $order_info['add_time'] = date('Y-m-d H:i:s' , $order_info['add_time']);
        // 服务内容
        $data['serve_content_list']  = objectToArray(DB::table('lgp_home_serve_content')->whereIn('id', explode(',', $order_info['serve_content_id']) )->get(['serve_title','serve_little_title','serve_content','serve_price','id']));
        $data['tutor'] = $this->getInfo( $order_info['tutor_id'], 1 );
        return returnJson(2,'success', $data);
    }


    /*
        评论
    */
    public function comment(){
        $param = $this->requests->getQueryParams();
        if( empty($param['user_id']) || empty($param['order_id']) ){
            return returnJson(-1,'缺少必要参数');
        }
        // 判断用户是否存在
        if( !\App\Http\Controllers\UserController::checkUser( $param['user_id'] ) ){
            return returnJson(-1,'该用户不存在');
        }

        $is_evaluate = DB::table('lgp_home_order_evaluate')->where("order_id",$param['order_id'])->exists();
        if( $is_evaluate ){
            return returnJson(-1,'已经评论过，不可以再次提问');
        }

        /*
        `order_id` int(11) DEFAULT NULL COMMENT '订单id',
          `multiple_star` varchar(20) DEFAULT NULL COMMENT '综合评价',
          `solve_problem` varchar(20) DEFAULT NULL COMMENT '解决问题星星评价',
          `text_evaluate` text COMMENT '文字评价',
          `unsolved` text COMMENT '未解决问题',
          `evaluate_time` int(11) DEFAULT NULL COMMENT '评论时间',
        */
        $addData['order_id'] = $param['order_id'];
        $addData['multiple_star'] = $param['multiple_star'];
        $addData['solve_problem'] = $param['solve_problem'];
        $addData['text_evaluate'] = $param['text_evaluate'];
        $addData['unsolved'] = $param['unsolved'];
        $addData['evaluate_time'] = time();
        $comment_id = DB::table("lgp_home_order_evaluate")->insertGetId($addData);
        if( $comment_id ){
            return returnJson(2,'评论成功', $comment_id);
        }else{
            return returnJson(-1,'评论失败');
        }
    }

    /*
        取消订单
    */
    public function cancel(){
        $param = $this->requests->getQueryParams();
        if( empty($param['user_id']) || empty($param['order_id']) || empty($param['order_status'])){
            return returnJson(-1,'缺少必要参数');
        }
        // 判断用户是否存在
        if( !\App\Http\Controllers\UserController::checkUser( $param['user_id'] ) ){
            return returnJson(-1,'该用户不存在');
        }
        // 判断当前订单是否存在
        $order_info= objectToArray(DB::table('lgp_home_order')->where("order_id",$param['order_id'])->first());
        if(empty( $order_info)){
             return returnJson(-1,'订单不存在，请刷新界面！');
        }
        if($param['order_status'] == 6){
             if($order_info['order_status'] ==3 && $order_info['pay_status'] ==2 ){
                    return returnJson(-1,'当前状态不可取消');
                }
        }
       
        $editData['order_status'] = $param['order_status'];
        $res = DB::table("lgp_home_order")->where('order_id',$param['order_id'])->update($editData);
        if( $res ){
            return returnJson(2,'订单状态修改成功');
        }else{
            return returnJson(-1,'订单状态修改失败');
        }
    }

    /**
     * [question 问答]
     * @param string user_id    用户名id
     * @param string order_id   订单id
     * @return [type] [description]
     */
    public function question(){
        $param = $this->requests->getQueryParams();
        // $param['user_id'] = 15;
        // $param['order_id'] = 5;
        // $param['question'] = '咨询问题';
        if( empty($param['user_id']) || empty($param['order_id']) || empty($param['question'])){
            return returnJson(-1,'缺少必要参数');
        }
        // // 判断用户是否存在
        // if( !\App\Http\Controllers\UserController::checkUser( $param['user_id'] ) ){
        //     return returnJson(-1,'该用户不存在');
        // }
        $field = ['order_id','serve_content_id','order_status','pay_status','type','tutor_id','user_id','tutor_reply_time'];

        // 服务内容表里面查询一下类型
        $order = objectToArray(DB::table('lgp_home_order')->where('order_id',$param['order_id'])->select($field)->first());
        if( !$order ){
            return returnJson(-1,'订单不存在');
        }

        // 判断修改文书，撰写文书，急速问答不赠送问答   
        if($order['type']==2){
             return returnJson(-1,'该订单类型不赠送提问');
        }
       
        // 服务类型 服务内容类型：1:一问一答,2:在线指导，3：远程指导A，4：远程指导B  ,5:预约监理导师，首次沟通\r\n6:帮我监理留学中介,7,头脑风暴，8无线问答，9撰写文书，10修改文书
        $type = objectToArray(DB::table('lgp_home_serve_content')->whereIn('id',explode(',', $order['serve_content_id']))->value('serve_type'));
         // 9撰写文书，10修改文书,不赠送问答次数
        // 除 9撰写文书，10修改文书 急速问答  6:帮我监理留学中介之外判断是否使用免费询问
        if( $type == 9 || $type == 10 ||  $type == 6){
            return returnJson(-1,'该订单类型不赠送提问');
        }
        // 判断时间订单中导师是否回复
        if(empty($order['tutor_reply_time'])){
            return returnJson(-1,'导师未回复，暂时无法问答！');
        }
        $data['order_id'] = $param['order_id'];
        $data['question'] = $param['question'];//咨询问题
        $data['add_time'] = time();
        // 判断是否是无限问答订单
        if( $type == 2 ){
            // 判断是否是赠送问答
            if($order['order_status'] == 2){
                // 赠送问询
                $data['type'] = 1;
                // 判断是否已经使用赠送问询
                 $is_give =  DB::table('lgp_home_order_enquire')->where(['order_id'=>$order['order_id'],'type'=>1])->exists();
                 if($is_give){
                    return returnJson(-1,'您的免费问询次数已用完！');
                 }else{
                    // 判断导师回复后48小时
                    $end_time = $order['tutor_reply_time'] + self::$timeNum;
                    // 如果时间超过导师回复后48小时后则不能在问询
                    if(time() > $end_time){
                        return returnJson(-1,'您的免费问询时间已过期！');
                    }

                 }

            }else{
                // 回复后24小时无限问答
                $endtime = $order['tutor_reply_time'] + self::$Infinitetime;
                // 判断问答
                if(time() < $endtime){ //用户问询在24小时内 为不是赠送问答
                     $data['type'] = 2;
                }else{
                    //用户问询在已经超过24小时 为赠送问答 并修改状态为 订单已完成
                    $data['type'] = 1;
                    $res = self::updateOrder($order['order_id'],2);
                    
                }
            }
            
        }else{
            // 判断是否已经使用赠送问答
             $is_give =  DB::table('lgp_home_order_enquire')->where(['order_id'=>$order['order_id'],'type'=>1])->exists();
             if($is_give){
                // 已经使用则提示
                return returnJson(-1,'您的免费问询次数已用完！');
             }else{
                // 判断时间是否过期 时效为48小时
                $end_time = $order['tutor_reply_time'] + self::$timeNum;
                if(time() < $end_time){
                    $data['type'] = 1;
                }else{
                    // 时间已经过期提示
                    return returnJson(-1,'您的免费问询时间已过期！');
                }
             }
        }


        if(DB::table("lgp_home_order_enquire")->insert($data)){
            return returnJson(2,'success');
        }else{
            return returnJson(-1,'问询失败');
        }
    }

    /*
        回答
    */
    public function answer(){
        $param = $this->requests->getQueryParams();
        if( empty($param['user_id']) || empty($param['order_id']) ){
            return returnJson(-1,'缺少必要参数');
        }
        // 判断用户是否存在
        if( !\App\Http\Controllers\UserController::checkUser( $param['user_id'] ) ){
            return returnJson(-1,'该用户不存在');
        }

        // 服务内容表里面查询一下类型
        $order_info = objectToArray(DB::table('lgp_home_order')->where([ ['order_id',$param['order_id']] ])->first());
        if( !$order_info ){
            return returnJson(-1,'订单不存在');
        }

        $serve_content = objectToArray(DB::table('lgp_home_serve_content')->whereIn('id',explode(',', $order_info['serve_content_id']))->first(['serve_type']));

        // 9撰写文书，10修改文书,不赠送问答次数
        if( $serve_content['serve_type']==9 || $serve_content['serve_type']==10 ){
            $is_enquire = objectToArray(DB::table('lgp_home_order_enquire')->where([ ['order_id',$param['order_id']], ['question','<>',''] ])->exists());
            if( $is_enquire ){
                return returnJson(-1,'已经提问过，不可再次提问');
            }
        }
        // 极速问答
        if( $order_info['type']==2 && $order_info['order_status']==2 ){
            $is_enquire = objectToArray(DB::table('lgp_home_order_enquire')->where([ ['order_id',$param['order_id']], ['question','<>',''] ])->exists());
            if( $is_enquire ){
                return returnJson(-1,'已经提问过，不可再次提问');
            }
        }

        // 8无限问答
        if( $serve_content['serve_type']==8 ){
            // 查看当前问答是否是赠送的
            if( $order_info['order_status']==2 ){
                // 是
                $is_enquire = objectToArray(DB::table('lgp_home_order_enquire')->where([ ['order_id',$param['order_id']], ['question','<>',''], ['type',1] ])->exists());
                if( $is_enquire ){
                    return returnJson(-1,'次数已经用完，不可再次提问');
                }
                $param['type'] = 1;
            }else{
                // 否
                $param['type'] = 2;
            }
        }

        // 普通问答
        if( $order_info['type']==1 && $serve_content['serve_type']!=8 ){
            // 查看当前问答是否是赠送的
            if( $order_info['order_status']==2 ){
                // 是
                $is_enquire = objectToArray(DB::table('lgp_home_order_enquire')->where([ ['order_id',$param['order_id']], ['question','<>',''], ['type',1] ])->exists());
                if( $is_enquire ){
                    return returnJson(-1,'次数已经用完，不可再次提问');
                }
                $param['type'] = 1;
            }else{
                // 否
                $param['type'] = 2;
            }
        }
        $addData['order_id'] = $param['order_id'];
        $addData['question'] = $param['question'];
        $addData['type'] = $param['type'];
        $addData['add_time'] = time();
        
        $comment_id = DB::table("lgp_home_order_enquire")->insertGetId($addData);
        if( $comment_id ){
            return returnJson(2,'提问成功');
        }else{
            return returnJson(-1,'提问失败');
        }
    }

    
    /**
     * [questionList  3.0 问答列表]
     * @param int user_id     用户名id
     * @param int order_id     订单id
     * @return [type] [description]
     */
    public function questionList(){
        $param = $this->requests->getQueryParams();
        // $param['user_id'] = 937;
        // $param['order_id'] = 2;
        $page = !isset($param['page']) ? 1 : $param['page'];

        if( empty($param['user_id']) || empty($param['order_id']) ){
           return returnJson(-1,'缺少必要参数');
        }
        // 判断用户是否存在
        if( !\App\Http\Controllers\UserController::checkUser( $param['user_id'] ) ){
            return returnJson(-1,'该用户不存在');
        }

        // 问题标题，问题内容，问题添加时间，导师回复内容，导师回复时间
        $order_info =objectToArray(DB::table('lgp_home_order')->where([ ['order_id',$param['order_id']] ])->first( [
            'order_type_title', 'connect_contents', 'add_time','remarks' ,'tutor_reply_content', 'tutor_reply_time', 'tutor_id','serve_content_id'] ));

         // tutor_reply
         DB::table('lgp_home_order')->where("order_id",$param["order_id"])->update(['tutor_reply'=>0]);

        // 用户信息
        $user_info = objectToArray(DB::table('lgp_home_users')->where([ ['user_id',$param['user_id']] ])->first( [
            'avatar'] ));

        // 导师信息
        $tutor_info = objectToArray(DB::table('lgp_home_tutor')->where([ ['id',$order_info['tutor_id']] ])->first( [
            'tutor_profile'] ));


        $type = DB::table('lgp_home_serve_content')->whereIn("id",explode(',', $order_info['serve_content_id'] ))->value('serve_type');
         $one_question = [];
        if($type != 11   &&   $type != 7 &&   $type != 9 &&   $type != 10){
              $one_question['title'] = $order_info['order_type_title'];
              $one_question['connect_contents'] = $order_info['connect_contents'];
              $one_question['question'] = $order_info['remarks'];
              $one_question['add_time'] = $order_info['add_time'];
              $one_question['answer'] = $order_info['tutor_reply_content'];
              $one_question['answer_time'] = $order_info['tutor_reply_time'];
              $one_question['user_pic'] = $user_info['avatar'];
              $one_question['tutor_pic'] = $tutor_info['tutor_profile'];
        }

        // $one_question['title'] = $order_info['order_type_title'];
        // $one_question['connect_contents'] = $order_info['connect_contents'];
        // $one_question['question'] = $order_info['remarks'];
        // $one_question['add_time'] = $order_info['add_time'];

        // $one_question['answer'] = $order_info['tutor_reply_content'];
        // $one_question['answer_time'] = $order_info['tutor_reply_time'];
        // $one_question['user_pic'] = $user_info['avatar'];
        // $one_question['tutor_pic'] = $tutor_info['tutor_profile'];

        $enquire_list = objectToArray(DB::table('lgp_home_order_enquire')->where([ ['order_id',$param['order_id']] ])->select(['question','answer','add_time','answer_time'])->get());

        foreach ($enquire_list as $key => &$val) {
            $val['connect_contents'] = $order_info['connect_contents'];
            $val['title'] = null;
            $val['user_pic'] = $user_info['avatar'];
            $val['tutor_pic'] = $tutor_info['tutor_profile'];
        }

        // array_unshift( $enquire_list, $one_question );
          if(!empty($one_question)){
             array_unshift( $enquire_list, $one_question );
          }

        $enquire_list = array_slice($enquire_list, self::$pageNum * ($page-1), self::$pageNum );

        return returnJson(2,'success', $enquire_list );
    }



    public  function againPay(){
          $param = $this->requests->getQueryParams();
          if(empty($param['order_id'])){
             return returnJson(-1,'缺少必要参数');
          }

           $info = objectToArray( DB::table('lgp_home_order as o')->where(['order_id'=>$param['order_id'],'pay_status'=>1,'order_status'=>0])->select('order_sn','add_time')->first());
          if(empty($info) ){
                 return returnJson(-1,'订单不存在，请刷新界面！');
          }

          // 判断支付时间是否过期
          $end_time = $info['add_time'] + self::$unpaytime; 
          
          if($end_time < time()){
                  self::updateOrder($param['order_id'],6);
                 return returnJson(-1,'订单支付超时，请重新下单！');
          }

        $pay_info  = objectToArray( DB::table('lgp_order_again_pay')->where('order_sn',$info['order_sn'])->first());
        unset($pay_info['id']);
        unset($pay_info['order_sn']);
        $pay_info['timeStamp'] = (string)$pay_info['timeStamp'];
       return returnJson(2,'支付成功',$pay_info);
    }

     /**
     * [refundOrder  3.0 申请退款]
     * @param int order_id     订单id
     * @param int refund_reason     退款原因
     * @param int refund_advise     退款提建议
     * @return [type] [description]
     */
    public function refundOrder(){
         $param = $this->requests->getQueryParams();
         // $param['order_id'] = 150;
         // $param['refund_reason'] = 'refund_reason';
         // $param['refund_advise'] = 'refund_advise';
          if(empty($param['order_id']) || empty($param['refund_advise'])|| empty($param['refund_reason'])){
             return returnJson(-1,'缺少必要参数');
          }
           $info = objectToArray( DB::table('lgp_home_order as o')->where('order_id',$param['order_id'])->select('order_sn','add_time','order_status','pay_status')->first());

          if(empty($info) ){
                 return returnJson(-1,'订单不存在，请刷新界面！');
          }

          $data['refund_advise'] = $param['refund_advise'];
          $data['refund_reason'] = $param['refund_reason'];
          $data['order_status'] = 4;
          $data['refund_time'] = time();
       
          $res = DB::table("lgp_home_order")->where('order_id',$param['order_id'])->update($data);
          if( $res ){
             return returnJson(2,'success');
          }else{
             return returnJson(-1,'申请失败！');
          }


    }

     /**彬享订单
     * @Author    XZJ
     * @DateTime  2020-03-26
     * @copyright [copyright]
     * @version   [version]
     * @param     [param]
     * @return    [type]      [description]
     */
    public function createBingxiang(){
        $param = $this->requests->getQueryParams();
        unset($param['s']);
        // $param['user_id'] = 15;//
        // $param['bx_phone'] = '15210978147';//
        // $param['bx_name'] = 'bx_name';//
        // $param['bx_class_num'] = 1;// 课时数量（彬享计划）
        // $param['bx_servers_job'] = 'bx_servers_job,';//选择服务 就业（彬享） 用英文状态下的分割
        // $param['bx_servers_envir'] = 'bx_servers_envir,';//选择服务 环境  用英文状态下的分割
        // $param['bx_servers_learning'] = 'bx_servers_learning';//选择服务 学术（彬享）用英文状态下的分割
        // $param['bx_admit_to'] = '院校安全,交通安全,'; //录取对比 用英文状态下的分割
        // $param['pay_price']  = 120; //总价
        if(empty($param['user_id'])){
             return returnJson(-1,'缺少必要参数');
        }
         // // 判断用户是否存在
        $users = objectToArray(Users::where(['user_id'=>$param["user_id"]])->select("username",'openid')->first());
        if(empty($users)){
            return returnJson(-1,'用户不存在');
        }
        if(empty($param['bx_phone'])){
            return returnJson(-1,'手机号不能为空！');
        }
         if(empty($param['bx_name'])){
            return returnJson(-1,'姓名不能为空！');
        }
        if(empty($param['bx_class_num'])){
            return returnJson(-1,'课时不能为空！');
        }
        if(empty($param['pay_price'])){
         // 创建时间
         return returnJson(-1,'价格不能为空！');
        }
        writelog('------添加数据前-------','createBingxiang','createBingxiang');
        $param['add_time'] = time();
        $pay_price = $param['pay_price'] ;
        $param['order_price'] = $param['pay_price'];
        $param['type']  = 4;
        $param['bx_servers_job'] = trim($param['bx_servers_job'],',');
        $param['bx_servers_envir'] = trim($param['bx_servers_envir'],',');
        $param['bx_servers_learning'] = trim($param['bx_servers_learning'],',');
        $param['bx_admit_to'] = trim($param['bx_admit_to'],',');
        $param['order_type_title'] = 'OFFER优选';
        writelog($param,'createBingxiang','createBingxiang');
        $order_id = DB::table("lgp_home_order")->insertGetId($param);
         writelog($order_id,'createBingxiang','createBingxiang');
      
        // 订单编号
        $order_sn = 'L'.date("Ymd") .substr(date('His'),-4). substr($order_id, -5);
        writelog($order_sn,'createBingxiang','createBingxiang');
        $editData['order_sn'] = $order_sn;
        $res = DB::table("lgp_home_order")->where('order_id',$order_id)->update($editData);
        if($order_id){
            if( $pay_price != 0 ){
                $pay_price = sprintf("%.2f",$pay_price);
                $tutor['tutor_name'] = 'OFFER优选';
                // $pay_price = 0.01;
                $returnData = $this-> three_pay( $users['openid'], $order_sn, $tutor , $pay_price );
                writelog($returnData,'createBingxiang','createBingxiang');
                return $returnData ;
            }
        }else{
            return json_msg(-2,'订单生成失败');
        }
    }


}
