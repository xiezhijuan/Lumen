<?php
/**
 * Created by PhpStorm.
 * User: jfy
 * Date: 2020/2/26
 * Time: 12:29
 */
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Http\Models\Course;
use App\Http\Models\Follow;
use App\Http\Models\Tutor;
use App\Http\Controllers\CelebrityController;

/**
 * Class 学鸽监理
 * @package App\Http\Controllers
 */
class ManageController extends Controller
{
  

    public function  initialize_index(){
        $param = $this->requests->getQueryParams();
        $country_id = empty($param["country_id"]) ? 1 : $param["country_id"] ;
        $data['title_banner'] =  self::getAdvert(4); //获取banner图
        $this->is_manage = 1;  //调用学鸽监理列表
         // 学历
        $data['education']  = objectToArray(DB::table('lgp_home_education')->where('status',1)->orderBy("sort",'asc')->get());
        // 学科
        $data['subject']  = objectToArray(DB::table('lgp_home_subject')->where('status',1)->orderBy("sort",'asc')->get());
        // 院校
        $data['school']  = objectToArray(DB::table('lgp_home_school')->where(["status"=>1,'country'=>$country_id])->orderBy("sort",'asc')->select('id','school_name','describe')->get());
        $data['tutor'] = $this->index();
         return returnJson(2,'success',$data);
    }
    /**
     * [index 学鸽监理列表]
     * @param string user_id      用户名id（必传）
     * @param string country_id   国家id（必传）
     * @return [type] [description]
     */
    public function index(){
            // echo "学鸽监理列表<pre>";
            $param = $this->requests->getQueryParams();
            $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;
            $country_id = empty($param["country_id"]) ? 1 : $param["country_id"] ;
            // $user_id =  15;
            if(empty($user_id)){
                 return returnJson(-1,'缺少参数');
            }

             // 导师文书必要条件
         $where = array(
                "lgp_home_tutor.status" => 1,//是否展示该导师
                "lgp_home_tutor.tutor_checked_status" => 2,//是否审核通过
                "lgp_home_tutor.tutor_is_jianli" => 1,//是开启学鸽监理
                'lgp_home_tutor.tutor_country' => $country_id
                );
          $keyword = [];
        // 关键字
        if(isset($param["keyword"]) && !empty($param["keyword"])){
            $keyword[] = ['lgp_home_tutor.tutor_name','like','%'.$param["keyword"].'%', 'OR'];
            $keyword[] = ['lgp_home_tutor.tutor_major','like','%'.$param["keyword"].'%', 'OR'];
            $keyword[] = ['lgp_home_tutor.subject_name','like','%'.$param["keyword"].'%', 'OR'];
            $keyword[] = ['e.education_name','like','%'.$param["keyword"].'%', 'OR'];
            $keyword[] = ['s.school_name','like','%'.$param["keyword"].'%', 'OR'];
         }
       
        $tutor = Tutor::where($where) 
                    ->orderBy('lgp_home_tutor.sort','asc')
                    ->leftJoin('lgp_home_education as e', 'education_id', '=', 'e.id')
                    ->leftJoin('lgp_home_school as s' ,'tutor_school_id', '=', 's.id')
                    ->where($keyword)
                    ->select('lgp_home_tutor.tutor_name','lgp_home_tutor.id','lgp_home_tutor.tutor_profile','lgp_home_tutor.tutor_major','lgp_home_tutor.tutor_label','lgp_home_tutor.tutor_help_count','lgp_home_tutor.tutor_introduction','e.education_name','s.school_name','lgp_home_tutor.tutor_is_wenshu','lgp_home_tutor.status','lgp_home_tutor.tutor_country','lgp_home_tutor.tutor_wenshu_price','lgp_home_tutor.tutor_second_school_price','lgp_home_tutor.tutor_price')
                    ->paginate(self::$pageNum)->toArray();
        foreach ($tutor['data'] as $key => &$val) {
            $val['tutor_label'] = explode(',', $val['tutor_label']);
             if(!empty($val['tutor_profile'])){
                    // $value['tutor_profile'] = imgTailor($value['tutor_profile'],100,100);
                    $val['tutor_profile'] = self::$lgp_url.imgTailor($val['tutor_profile'],100,100);
                }
        }

            // 轮播图
            // $banner = self::getAdvert(4);
            if (!empty($this->is_manage)) {
                return $tutor;
            }
            // $data['tutor'] = $tutor;
            // $data['banner'] = $banner;
            return returnJson(2,'success',$tutor);
    }

    /**
     * [getServeContent  获取服务内容  申请院校，初识留学，申请专业，学鸽监理]
     * @param string user_id      用户名id（必传）
     * @param int    type         类型 学鸽问询 : 1:初识留学,2:申请院校,3:申请专业学鸽监理: 10学鸽监理
     * @param int    tutor_id     导师id
     * @return [array] [返回该导师的服务内容信息]
     */
    public function getServeContent(){
            $param = $this->requests->getQueryParams();
            $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;
            $tutor_id = empty($param["tutor_id"]) ? 0 : $param["tutor_id"] ;
            $type = empty($param["type"]) ? 0 : $param["type"] ;
            // $user_id = 15;
            // $type  = 2;
            // $tutor_id = 33;
            if(empty($user_id) || empty($type) || empty($tutor_id)){
                 return returnJson(-1,'缺少参数');
            }
            
            $serve_type = 1;
            if($type== 1 || $type== 2 || $type== 3){ //初始留学,申请院校,申请专业
                $serve_type =1;  
            }else if($type== 10){ //学鸽监理
                $serve_type =5;
            }
            $where = [
                    'type'=>$serve_type,//服务类型： 1：问询服务，2：文书服务，3：识校服务，4：学鸽服务，5：预约监理\r\n
                    'type_type'=>$type, //服务类型下面的类型：  1:初识留学,2:申请院校,3:申请专业学鸽监理: 10学鸽监理
                    'status'=>1 //状态
                    ];
           
            // 获取服务id
            $serve_id = DB::table('lgp_home_serve')->where($where)->value('serve_id');
            $server = [];
            if($serve_id ){
                // 获取该导师下相对应改的服务内容信息
               $server = objectToArray(DB::table('lgp_home_serve_content')->where(['status'=>1,'serve_id'=>$serve_id,'tutor_id'=>$tutor_id])->select('id as serve_content_id','serve_id','serve_title','serve_type','serve_content','serve_price','serve_little_title','tutor_id')->orderBy('sort','asc')->get());
            }
            return returnJson(2,'success',$server);
           

    }

   
   

 

   

  
}