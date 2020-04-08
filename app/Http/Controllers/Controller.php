<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Psr\Http\Message\ServerRequestInterface;	//第三方插件 获取参数
use Illuminate\Support\Facades\DB;
class Controller extends BaseController
{

	// 小程序微信授权配置
    protected $appid = "wx195807511cf1b076";
    protected $secret = "99e35e798beef9e12376c72a4d56a229";
    protected $grant_type = "authorization_code";
    protected $shanghu =  "1515549301";
    protected $key = "7bed71f67b6399ac6f2bffbb4b539368";// 商户号的支付秘钥 md5(binbin520)
    public static $OK = 0;
    public static $IllegalAesKey = -41001;
    public static $IllegalIv = -41002;
    public static $IllegalBuffer = -41003;
    public static $DecodeBase64Error = -41004;
    public static $pageNum = 5;
    // 首单折扣
    public static $Discount = 1;
    public static $timeNum = 48*3600; //订单详情与订单列表中 导师回复后 赠送问答时效  为48小时
    public static $unpaytime = 3600;// 未支付时效  秒 3600 为一个小时
    public static $Infinitetime = 24*3600;//无限问答 导师回复后24小时随便提问 
    public static $rapidly_price = 9.9; //急速问答价格
    public static $is_show = false;  //小程序开启视频 （学长学姐说，攻略，我的赞中视频） false关闭 true开启
    public static $lgp_url = 'https://www.highschool86.com';
    // 发送邮件用户列表
    protected $email_user_list = ['jiangfuyou@beliwin.com','yangzihui@beliwin.com','liuchong@beliwin.com'];
    protected static $give = '选择 PS 服务 赠  价值1888元头脑风暴1次';
    // 1:一问一答,2:在线指导，3：远程指导A，4：远程指导B  ,5:预约监理导师，首次沟通\r\n6:帮我监理留学中介,7,头脑风暴，8无线问答，9撰写文书，10修改文书,11:直播院校（1v1）,12:院校问答,13:录取对比问询
    public static $service_process=[
        // 一问一答
        1=>'1.下单后，灰鸽将在10分钟内极速为您确认学鸽。 
            2.确认成功后12小时内学鸽给予答复。 
            3.订单结束后，可额外获得1次提问机会，有效时间48小时。',
        //在线指导 
        2=>'1.下单后，灰鸽将在10分钟内极速为您确认学鸽。 
            2.确认成功后12小时内学鸽给予答复。 
            3.从学鸽第一次回复起，24小时内可不限制次数进行问询。
            4.订单结束后，可额外获得1次提问机会，有效时间48小时。',
        // 远程指导A
        3=>'1.下单后，灰鸽将在20分钟内极速为您确认学鸽。 
            2.确认成功后，客服介入匹配时间，进行1v1视频通话15分钟讲解。 
            3.订单结束后，可额外获得1次提问机会，有效时间48小时。',
        //远程指导B   
        4=>'1.下单后，灰鸽将在20分钟内极速为您确认学鸽。 
            2.确认成功后，客服介入匹配时间，进行1v1视频通话60分钟讲解。 
            3.订单结束后，可额外获得1次提问机会，有效时间48小时。',
        // 预约监理导师，首次沟通
        5=>'1.下单后，灰鸽将在20分钟内极速为您确认学鸽。 
            2.确认成功后，客服介入预约时间，进行1v1视频通话15分钟。 
            3.确认选择当前学鸽监理后，退还本次沟通费用。
            4.监理周期为1个申请季。',
        // 帮我监理留学中介
        6=>'1.下单后，灰鸽将在20分钟内极速为您确认学鸽。 
            2.确认成功后，客服介入预约时间，进行1v1视频通话确认信息。 
            3.监理期间可随时与学鸽沟通。
            4.监理周期为1个申请季。',
        // 头脑风暴
        7=>'1.下单后，灰鸽第一时间为您确认学鸽。 
            2.确认成功后，客服介入匹配时间，进行头脑风暴60分钟。 
            3.订单结束后，可额外获得1次提问机会，有效时间48小时。',
        // 9撰写文书
        9=>'1.下单后，灰鸽第一时间为您确认学鸽。 
            2.确认订单后，客服介入，学鸽开始撰写文书。
            3.文书不限次数修改，直到满意为止并在指定时间完成文书撰写 。
            4.订单中如有PS撰写，额外赠送头脑风暴一次。',
        // 10修改文书
        10=>'1.下单后，灰鸽第一时间为您确认学鸽。 
             2.确认订单后，客服介入，学鸽开始修改文书。
             3.直到满意为止并在指定时间完成文书修改。',
        // 11:直播院校（1v1）
        11=>'1.下单后，灰鸽将在20分钟内极速为您确认学鸽。 
             2.确认成功后，客服介入预约时间，进行1v1视频直播50分钟带你了解院校并进行讲解。 
             3.订单结束后，可额外获得1次提问机会，有效时间48小时',
        // 12:院校问答
        12=>'',
        // 13:录取对比问询
        13=>'',
        // 急速问答
        22 =>'1.下单后，8分钟内急速匹配最符合您的学鸽。 
              2.确认成功后12小时内学鸽给予答复。'

    ];

     
 



    //获取参数配置
    public $requests; 


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(ServerRequestInterface $request)
    {

        $this->requests = $request;		//获取参数配置

    }

    // 关注导师
    public function addFollowTutor($tutor_id){
         DB::table('lgp_home_tutor')->where("id",$tutor_id)->increment('tutor_follow_count');
    }
    // 取消关注
    public function reduceFollowTutor($tutor_id){
        DB::table('lgp_home_tutor')->where("id",$tutor_id)->where("tutor_follow_count",'>',0)->decrement('tutor_follow_count');
    }


       /**
     * [addpraiseAction  点赞相关操作]、
     * param:  id   文章id/点播id/直播id/小视频id
     * param:  type     1文章/2点播/3直播/4小视频
     * @return [type] [description]
     */ 
    public function addpraiseAction($id,$type){
        if($type == 1){ //文章
             DB::table('lgp_home_article')->where("id",$id)->increment('praised_count');

        }else if($type == 2 || $type == 3 ){
             DB::table('lgp_home_course')->where("id",$id)->increment('praised_count');

        }else if($type == 4){
            DB::table('lgp_home_video')->where("id",$id)->increment('praised_count');

        }
    }


      /**
     * [cancelpraiseAction  取消点赞相关操作]
     * param:  id   文章id/点播id/直播id/小视频id
     * param:  type     1文章/2点播/3直播/4小视频
     * @return [type] [description]
     */ 
    public function cancelpraiseAction($id,$type){
        if($type == 1){ //文章
             DB::table('lgp_home_article')->where("id",$id)->where("praised_count",'>',0)->decrement('praised_count');

        }else if($type == 2 || $type == 3 ){
             DB::table('lgp_home_course')->where("id",$id)->where("praised_count",'>',0)->decrement('praised_count');

        }else if($type == 4){
            DB::table('lgp_home_video')->where("id",$id)->where("praised_count",'>',0)->decrement('praised_count');
        }
    }


    /** 
     * [getAdvert 获取 初识留学 （轮播） ;申请院校 （轮播）;申请专业 （轮播） ;学鸽监理  (轮播)  ;情报局   （轮播）信息]
     * @param int     type      类型
     * @return [array] [返回一个一位数组数据]
     */
    public static function getAdvert($type){
            $res =  objectToArray( DB::table('lgp_home_advert')->where(['status'=>1,'type'=>$type])->get());
            $return = [];
            if( $res ){
                $return = $res;
            }
            return $return;
    }

    

    /*
        获取导师的必要资料信息（头像、名字、学校、专业、学历、标签、）
        使用方法：
        $a = \App\Http\Controllers\TutorController::getInfo(1);
        echo '<pre>';
        var_dump(json_decode($a,true));die;
    */
    public function getInfo( $tutor_id=0,$type='' ){
        if( empty($tutor_id) ){
            $param = $this->requests->getQueryParams();
        }else{
            $param['tutor_id'] = $tutor_id;
        }
        if( empty($param['tutor_id']) ){
            return returnJson(-1,'导师id不能为空');
        }
        // 导师id
        $tutor_id = $param['tutor_id'];

        // 导师必要条件
        $tutor_where = array(
                "lgp_home_tutor.id"                     => $tutor_id //导师id
                );

        $tutor_info = objectToArray(DB::table('lgp_home_tutor')->Where(function ($query) use($tutor_where,$param) {
                            if ($tutor_where) {
                                $query->Where($tutor_where);
                            }
                    }) 
                    ->leftJoin('lgp_home_school as s', 'tutor_school_id', '=', 's.id')
                    ->leftJoin('lgp_home_education as e', 'education_id', '=', 'e.id')
                    ->select('lgp_home_tutor.id','lgp_home_tutor.tutor_name', 's.school_name', 'lgp_home_tutor.tutor_major', 'lgp_home_tutor.tutor_label', 'lgp_home_tutor.tutor_introduction', 'e.education_name','lgp_home_tutor.tutor_profile')
                    ->first());
        $tutor_info['tutor_label'] = explode(',', $tutor_info['tutor_label']);
        if($type == 1){
            return $tutor_info;
        }
        return returnJson(2,'success',$tutor_info);
    }



    /**
     * [updateOrder  3.0订单修改]
     * @param int order_id     订单id
     * @param int order_status   订单状态
     * @return [type] [description]
     */
    public static function updateOrder($order_id,$order_status){
         $res = DB::table("lgp_home_order")->where("order_id", $order_id)->update(["order_status"=>$order_status]);
        if($res){
            return true;
        }else{
            return false;
        }
    }


}
