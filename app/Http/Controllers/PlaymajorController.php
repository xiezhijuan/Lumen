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
 * Class 申请专业
 * @package App\Http\Controllers
 */
class PlaymajorController extends Controller
{

    /**
     * [index 申请专业初始化列表]
     * @param string user_id      用户名id（必传）
     * @param string country_id   国家id（默认1）
     * @param int    type         轮播图类型id  1:初识留学,2:申请院校,3:申请专业
     * @return [type] [description]
     */
    public function index(){
        $param = $this->requests->getQueryParams();
        // $param['type'] = 1;
        // $param['country_id']=1;
         $country_id = empty($param["country_id"]) ? 1 : $param["country_id"] ;
        if(empty($param['type']) ){
             return returnJson(-1,'缺少参数');
         }
        $data['title_banner'] =  self::getAdvert($param['type']); //获取banner图
        $this->is_playmajor = 1;  //调用申请专业列表
        // 学历
        $data['education']  = objectToArray(DB::table('lgp_home_education')->where('status',1)->orderBy("sort",'asc')->get());
        // 学科
        $data['subject']  = objectToArray(DB::table('lgp_home_subject')->where('status',1)->orderBy("sort",'asc')->get());
        // 院校
        $data['school']  = objectToArray(DB::table('lgp_home_school')->where(["status"=>1,'country'=>$country_id])->orderBy("sort",'asc')->select('id','school_name','describe')->get());
        // 导师
        $data['tutor'] =  $this->tutorList();
        
        return returnJson(2,'success',$data);
    }
    /**
     * [index 申请专业导师列表（初识留学,申请院校,申请专业 ）]
     * @param int    user_id      用户名id（必传）
     * @param int    country_id   国家id（必传）
     * @param string  type_name      类型  初识留学,申请院校,申请专业(必传)
     * @param string keyword      搜索关键字（导师名字(搜索),学历(搜索),院校(搜索),专业(搜索)  ）        
     * @param int    education_id         阶段id
     * @param int    tutor_school_id      学校id
     * @param string subject_name         专业名
     * @param int    page         分页
     * @return array [导师信息]
     */
    public function tutorList(){
        $param = $this->requests->getQueryParams();
        $user_id = empty($param["user_id"]) ? 0 : $param["user_id"] ;
        $country_id = empty($param["country_id"]) ? 1 : $param["country_id"] ;
        // $param["user_id"] = 15;
        // $country_id = 1;
        // $param["type_name"] = '申请专业';
        if(empty($param["user_id"])|| empty($param["type_name"])){
             return returnJson(-1,'缺少参数');
        }
        $where = [];
        // 阶段（学历）
        if(isset($param["education_id"]) && !empty($param["education_id"])){
            $where[] = ['e.id','=',$param["education_id"]];
        }
        // 学校
        if(isset($param["tutor_school_id"]) && !empty($param["tutor_school_id"])){
            $where[] = ['lgp_home_tutor.tutor_school_id','=',$param["tutor_school_id"]];
        }
         // 学科
        if(isset($param["subject_name"]) && !empty($param["subject_name"])){
            $where[] = ['lgp_home_tutor.subject_name','like','%'.trim($param["subject_name"]).'%'];
        }
        if(isset($param["type_name"]) && !empty($param["type_name"])){
             $where[] = ['lgp_home_tutor.tutor_ask_firstS','like','%'.trim($param["type_name"]).'%'];
        }
        $keyword = [];
         // 关键字
        if(isset($param["keyword"]) && !empty($param["keyword"])){
            $keyword[] = ['lgp_home_tutor.tutor_name','like','%'.$param["keyword"].'%', 'OR'];
            $keyword[] = ['lgp_home_tutor.tutor_major','like','%'.$param["keyword"].'%', 'OR'];
            $keyword[] = ['lgp_home_tutor.subject_name','like','%'.$param["keyword"].'%', 'OR'];
            $keyword[] = ['e.education_name','like','%'.$param["keyword"].'%', 'OR'];
            $keyword[] = ['s.school_name','like','%'.$param["keyword"].'%', 'OR'];
         }

         // 导师文书必要条件
         $tutor_where = array(
                "lgp_home_tutor.status" => 1,//是否展示该导师
                "lgp_home_tutor.tutor_checked_status" => 2,//是否审核通过
                "lgp_home_tutor.tutor_is_firstS" => 1,//是否开启学鸽问询:1是/2否
                'lgp_home_tutor.tutor_country' => $country_id
                );
          $tutor = Tutor::orderBy('lgp_home_tutor.sort','asc')
                    ->leftJoin('lgp_home_education as e', 'education_id', '=', 'e.id')
                    ->leftJoin('lgp_home_school as s' ,'tutor_school_id', '=', 's.id')
                    ->where($tutor_where)
                    ->where($where)
                    ->where($keyword)
                    ->select('lgp_home_tutor.tutor_name','lgp_home_tutor.id','lgp_home_tutor.tutor_profile','lgp_home_tutor.tutor_major','lgp_home_tutor.tutor_label','lgp_home_tutor.tutor_help_count','lgp_home_tutor.tutor_introduction','e.education_name','s.school_name','lgp_home_tutor.tutor_is_wenshu','lgp_home_tutor.status','lgp_home_tutor.tutor_country','lgp_home_tutor.tutor_wenshu_price','lgp_home_tutor.tutor_firstS_price','lgp_home_tutor.tutor_second_school_price')
                    ->paginate(self::$pageNum)->toArray();
            
            foreach ($tutor['data'] as $key => &$value) {
                $value['tutor_label'] = explode(',', $value['tutor_label']);
                 if(!empty($value['tutor_profile'])){
                    // $value['tutor_profile'] = imgTailor($value['tutor_profile'],100,100);
                    $value['tutor_profile'] = self::$lgp_url.imgTailor($value['tutor_profile'],100,100);
                }
            }
            if (!empty($this->is_playmajor)) {
                return $tutor;
            }

           return returnJson(2,'success',$tutor);

    }
  
 

 



  
}