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
use App\Http\Models\Mypraise;

/**
 * Class 名鸽堂
 * @package App\Http\Controllers
 */
class CelebrityController extends Controller
{

//    更多筛选条件
    public static $more = 
        [
                [
                    "title" => "学鸽问询",
                    "child" => [
                        [
                            "初识留学",
                            "申请院校",
                            "申请专业"
                        ]
                    ]
                ],
                [
                    "title" => "学鸽执笔",
                    "child" => [
                        [
                            "头脑风暴",
                            "撰写文书",
                            "修改文书"
                        ]
                    ]
                ],
                [
                    "title" => "秒识鸽校",
                    "child" => [
                        [
                            "直播院校",
                            "院校问答",
                            "录取对比问询"
                        ]
                    ]
                ]
        ];

    /*
        初始化接口
    */
    public function onshow(){
        $this->onshow = 1;
        $this->index();
    }

    /**
     * Describe: [名鸽堂列表]
     * User: jfy
     * Date: 2020/2/26
     * Time: 12:46
     */
    public function index()
    {
        // $pageNum = 6;
        $data = $this->requests->getQueryParams();
        $user_id = empty($data["user_id"]) ? 0 : $data["user_id"];
        // $tutor_country = !empty( $data['tutor_country'] ) ? $data['tutor_country'] : 1;
        $init_tutor_country = !empty( $data['tutor_country'] ) ? $data['tutor_country'] : '';
        $country_id = !empty( $data['country_id'] ) ? $data['country_id'] : '';
        $tutor_country = 1;
        // $user_id = 15;
        // $tutor_country =  1;
        if( empty($user_id) ){
            return returnJson(-1,'缺参数');
        }
        if(!empty($init_tutor_country)){
                $tutor_country = $init_tutor_country;
        }
        if(!empty($country_id)){
                $tutor_country = $country_id;
        }


        $where = [];
        $whereIn = [];
        // 搜索
        if( !empty($data['keyword']) ){
            $data['keyword'] = trim($data['keyword']);
            array_push( $where, ['tutor_name', 'like', '%'.$data['keyword'].'%', 'OR'] );// 导师名字
            array_push( $where, ['school_name', 'like', '%'.$data['keyword'].'%', 'OR'] );// 学校名字
            array_push( $where, ['tutor_major', 'like', '%'.$data['keyword'].'%', 'OR'] );// 专业
        }
        // 学历
        if( !empty($data['education_id']) ){
            array_push( $where, ['education_id', $data['education_id']] );
        }
        // 学校
        if( !empty($data['school_id']) ){
            array_push( $where, ['tutor_school_id', $data['school_id']] );
        }
        // 学科
        if( !empty($data['subject_name']) ){
            $data['subject_name'] = trim( $data['subject_name'] );
            array_push( $where, ['subject_name', 'like' , '%' . $data['subject_name'] . '%'] );
        }

        // 选择文书导师的时候需要查找一下包含这个服务的导师
        // $data['more'] = '1-0';
        $tutor_ids = [];
        if( !empty($data['more']) ){
            if( !strpos( $data['more'], '-') ){
                return returnJson(-1,'more参数格式错误，请检查！');
            }
            $more_arr = explode('-', $data['more']);
            switch ( $more_arr[0] ) {
                // 学鸽问询
                case 0:
                    array_push( $where, ['tutor_is_firstS', '=', 1]);
                    // 初识留学,申请院校,申请专业
                    array_push( $where, ['tutor_ask_firstS', 'like', '%' . self::$more[0]['child'][0][$more_arr[1]] . '%' ]);
                    break;
                // 学鸽执笔
                case 1:
                    array_push( $where, ['tutor_is_wenshu', '=', 1 ]);
                    array_push( $where, ['tutor_wenshu_title', 'like', '%' . self::$more[1]['child'][0][$more_arr[1]] . '%' ]);
                    break;
                // 秒识鸽校
                case 2:
                    array_push( $where, ['tutor_is_second_school', '=', 1 ]);
                    // 直播院校,院校问答,录取对比问询
                    array_push( $where, ['tutor_second_school', 'like', '%' . self::$more[2]['child'][0][$more_arr[1]] . '%' ]);
                    break;
                default:
                    break;
            }
        }
        $tutor_where = array(
            't.status' =>1,
            't.tutor_checked_status' =>2,
            't.tutor_country' => $tutor_country,
    );
        
        // 国家，默认是美国
        // array_push( $where, ['t.status', 1] );
        // array_push( $where, ['t.tutor_checked_status', 2] );
        // array_push( $where, ['tutor_country', $tutor_country] );
        $field = ['t.id', 'tutor_name', 'tutor_school_id', 'tutor_major', 'education_id', 'tutor_label', 'tutor_profile' ,'tutor_introduction', 'tutor_help_count', 'e.education_name', 's.school_name', 'tutor_is_firstS', 'tutor_is_wenshu'];
        $data = DB::table('lgp_home_tutor as t')
                -> leftJoin( 'lgp_home_education as e', 't.education_id', 'e.id' )
                -> leftJoin( 'lgp_home_school as s', 't.tutor_school_id', 's.id' )
                ->where($tutor_where)
                -> where( $where )
                -> select( $field )
                ->paginate(self::$pageNum )->toArray();
        if( isset($this->onshow) ){
            //阶段
            $data['education_list'] = DB::table('lgp_home_education')
                -> where([ ['status',1] ])
                -> get( ['id', 'education_name'] );
            //学校
            $data['school_list'] = DB::table('lgp_home_school')
                -> where([ ['status',1], ['country',$tutor_country] ])
                -> orderBy('sort','asc')
                -> get( ['id', 'school_name', 'describe'] );
            //学科
            $data['subject_list'] = DB::table('lgp_home_subject')
                -> where([ ['status', 1] ])
                -> get( ['id','subject_name'] );
            //更多
            $data['more'] = self::$more;
        }


        // 是否关注关注
        $gz_list = array_column(objectToArray(DB::table('lgp_home_follow')->where('user_id', $user_id)->get(['tutor_id'])),'tutor_id');
        $data = objectToArray($data);
        foreach ($data['data'] as $key => &$val) {
            if( in_array($val['id'], $gz_list) ){
                $val['is_follow'] = true;
            }else{
                $val['is_follow'] = false;
            }
            $val['tutor_label'] = explode(',', $val['tutor_label']);
            // $val['tutor_profile'] = $val['tutor_profile'];
             if(!empty($val['tutor_profile'])){
                    // $value['tutor_profile'] = imgTailor($value['tutor_profile'],100,100);
                    $val['tutor_profile'] = self::$lgp_url.imgTailor($val['tutor_profile'],100,100);
                }
            
            if( !empty($val['tutor_is_firstS']) ){
                $val['tutor_type'] = 1; // 识校导师
            }else if( !empty($val['tutor_is_wenshu']) ){
                $val['tutor_type'] = 2; // 文书导师
            }else if( !empty($val['tutor_is_yuyan']) ){
                $val['tutor_type'] = 3; // 语培导师
            }else if( !empty($val['tutor_is_yuyan']) ){
                $val['tutor_type'] = 4; // 大厂导师
            }
            unset($val['tutor_school_id']);
            unset($val['education_id']);
            unset($val['tutor_is_firstS']);
            unset($val['tutor_is_wenshu']);

        }

        return returnJson(2,'success',$data);

    }

   /**
     * Describe: [导师详情]
     * User: jfy
     * Date: 2020/2/26
     * Time: 17:37
     * @return string|void
     */
    public function detail(){
        $param = $this->requests->getQueryParams();
        // $param['tutor_id'] = 33;
        // $param['user_id'] = 15;
        if( empty($param['tutor_id']) || empty($param['user_id']) ){
            return returnJson(-1,'缺少必要参数');
        }
        $where = array('t.id'=>$param['tutor_id']);
        $tutor = objectToArray(DB::table('lgp_home_tutor as t')
                              ->leftJoin('lgp_home_education as e', 't.education_id', '=', 'e.id')
                              ->leftJoin('lgp_home_school as s', 't.tutor_school_id', '=', 's.id')
                              ->where($where )
                              ->select('t.id','t.tutor_name','t.tutor_profile','s.school_name','t.tutor_major','t.tutor_label','t.tutor_help_count','t.tutor_introduction','e.education_name','t.tutor_is_wenshu','t.tutor_is_yuyan','t.tutor_is_experience','t.tutor_is_firstS','t.tutor_is_second_school','t.tutor_anli','t.tutor_education_prove','t.tutor_major_success','t.tutor_follow_count','t.tutor_job_experience')
                              ->first());
        // 优势标签
         $tutor['tutor_label'] = str_replace('，', ',', $tutor['tutor_label']);
         $tutor['tutor_label'] =explode(',', $tutor['tutor_label']);
         // 学历证明(材料证明)
         if (!empty($tutor['tutor_education_prove'])) {     //判断是否存在
            $tutor['tutor_education_prove'] = explode(',', $tutor['tutor_education_prove']);
        }else{
            $tutor['tutor_education_prove'] = [];
        }
        // 成功案例
         if (!empty($tutor['tutor_anli'])) {     //判断是否存在
            $tutor['tutor_anli'] = explode(',', $tutor['tutor_anli']);
        }else{
            $tutor['tutor_anli'] = [];
        }
        // 是否关注该导师
         $tutor['is_follow'] = Follow::where(['user_id' => $param['user_id'],'tutor_id'=>$param['tutor_id']])->exists();
        
         // 统计关注老师人数
         $tutor['guanzhu_count'] = getNumW($tutor['tutor_follow_count']);

         // 统计老师帮助人数
        $tutor['tutor_help_count'] = getNumW($tutor['tutor_help_count']);

        // 服务信息
        // 1：学鸽问询，2：学鸽执笔，3：留学培训，4：秒识鸽校
        $server = array( );

        // 判断导师是否开启问询服务
         // 学鸽问询  初识留学 申请院校 申请专业  tutor_is_firstS
         // 学鸽执笔 文书   tutor_is_wenshu 
         // 留学培训 语言   tutor_is_yuyan
         // 秒识鸽校 知学府  tutor_is_second_school
       
       // 问询服务  初识留学 申请院校 申请专业
        if($tutor['tutor_is_firstS'] == 1){
          $server[] =  array('id'=>1,'name'=>'学鸽问询','is_show'=>true,'disc'=>'十分钟解答');
        }else{
             $server[] =  array('id'=>1,'name'=>'学鸽问询','is_show'=>false,'disc'=>'十分钟解答');
        }
        // 学鸽执笔 文书
        if($tutor['tutor_is_wenshu'] == 1){
             $server[] = array('id'=>2,'name'=>'学鸽执笔','is_show'=>true,'disc'=>'精品文书制作');
        }else{
             $server[] = array('id'=>2,'name'=>'学鸽执笔','is_show'=>false,'disc'=>'精品文书制作');
        }
        // 留学培训 语言
        if($tutor['tutor_is_yuyan'] == 1){
           $server[] = array('id'=>3,'name'=>'留学培训','is_show'=>true,'disc'=>'平均提升录取率75%');
        }else{
           $server[] = array('id'=>3,'name'=>'留学培训','is_show'=>false,'disc'=>'平均提升录取率75%');
        }
        // 秒识鸽校 知学府
        if($tutor['tutor_is_second_school'] == 1){
           $server[] = array('id'=>4,'name'=>'秒识鸽校','is_show'=>true,'disc'=>'立即了解院校');
        }else{
            $server[] =array('id'=>4,'name'=>'秒识鸽校','is_show'=>false,'disc'=>'立即了解院校');

        }
         $tutor['server_label'] = $server;
         return returnJson(2,'success',$tutor);

    }

    /**
     * Describe: [问询]
     * @return string|void
     */
    public function serverAsk(){
        $param = $this->requests->getQueryParams();
        // $param['tutor_id'] = 33;
        // $param['user_id'] = 15;
        // $param['type'] = 1;
        if( empty($param['tutor_id']) || empty($param['user_id']) || empty($param['type']) ){
            return returnJson(-1,'缺少必要参数');
        }

        $where = array('t.id'=>$param['tutor_id']);
        $tutor = objectToArray(DB::table('lgp_home_tutor as t')
                              ->leftJoin('lgp_home_education as e', 't.education_id', '=', 'e.id')
                              ->leftJoin('lgp_home_school as s', 't.tutor_school_id', '=', 's.id')
                              ->where($where )
                              ->select('t.id','t.tutor_name','t.tutor_profile','s.school_name','t.tutor_major','t.tutor_label','e.education_name')
                              ->first());
        // 优势标签
         $tutor['tutor_label'] = str_replace('，', ',', $tutor['tutor_label']);
         $tutor['tutor_label'] =explode(',', $tutor['tutor_label']);

         // 服务区分
         //     // 服务信息
        // 1：学鸽问询，2：学鸽执笔，3：留学培训，4：秒识鸽校\
        $server = [];
         if($param['type'] == 1){
             $server = objectToArray(DB::table('lgp_home_serve')->where(['status'=>1,'type'=>1])->select('serve_id','serve_name','serve_describe','server_title','serve_activity','activity_describe')->take(3)->get());
         }else if($param['type'] == 2){
            $server = objectToArray(DB::table('lgp_home_serve')->where(['status'=>1,'type'=>2])->select('serve_id','serve_name','serve_describe','server_title','serve_activity','activity_describe')->take(3)->get());
         }else if($param['type'] == 3){
            $server = objectToArray(DB::table('lgp_home_serve')->where(['status'=>1,'type'=>3])->select('serve_id','serve_name','serve_describe','server_title','serve_activity','activity_describe')->take(3)->get());
         }else if($param['type'] == 4){
            $server = objectToArray(DB::table('lgp_home_serve')->where(['status'=>1,'type'=>4])->select('serve_id','serve_name','serve_describe','server_title','serve_activity','activity_describe')->take(3)->get());
         }

         $data['tutor'] = $tutor;
         $data['server'] = $server;
         return returnJson(2,'success',$data);


    }

    /**
     * Describe: [问询] 下面的数据价格
     * @param  user_id  用户id
     * @param  tutor_id 导师id
     * @param  serve_id 服务id
     * @return string|void
     */
    public function serverAskPrice(){
        $param = $this->requests->getQueryParams();
        // $param['tutor_id'] = 33;
        // $param['user_id'] = 15;
        // $param['serve_id'] = 6;
        // $param['type'] = 2;
        if( empty($param['tutor_id']) || empty($param['user_id']) || empty($param['serve_id']) ){
            return returnJson(-1,'缺少必要参数');
        }
        // 获取导师信息
        // $tutor = objectToArray(DB::table('lgp_home_tutor')->where('id',$param['tutor_id'])->select('id','education_id','tutor_name')->first());
        // 服务区分
         //     // 服务信息
        // 1：学鸽问询，2：学鸽执笔，3：留学培训，4：秒识鸽校\
        // 1：学鸽问询，2：学鸽执笔，3：留学培训，4：秒识鸽校
        // 判断导师是否开启问询服务
         // 学鸽问询  初识留学 申请院校 申请专业  tutor_is_firstS
         // 学鸽执笔 文书   tutor_is_wenshu  学鸽执笔
         // 留学培训 语言   tutor_is_yuyan
         // 秒识鸽校 知学府  tutor_is_second_school
         $education = [];
         $server = [];
         $data = [];
         $data['give'] =  '';
         if($param['type'] == 1){
             $server = objectToArray(DB::table('lgp_home_serve_content')->where(['serve_id'=>$param['serve_id'] ,'status'=>1,'tutor_id'=>$param['tutor_id']])->select('id as serve_content_id','serve_type','serve_title','serve_content','serve_price','serve_little_title')->get());
            
         }else if($param['type'] == 2){ //2：学鸽执笔
             $servers = objectToArray(DB::table('lgp_home_serve_content')->where(['serve_id'=>$param['serve_id'] ,'status'=>1,'tutor_id'=>$param['tutor_id']])->select('id as serve_content_id','serve_type','serve_title','serve_content','serve_price','serve_little_title','education_id')->get());
             $middle_server= array();
             $benke_server = array();
             $yjs_server = array();
             foreach ($servers as $key => $value) {
                    if(in_array('1', explode(',', $value['education_id']))){ //中学
                        $middle_server[] = $value;
                    }
                    if(in_array('2', explode(',', $value['education_id']))){ //本科
                           $benke_server[] = $value;
                    }
                    if(in_array('3', explode(',', $value['education_id']))){ //研究生
                           $yjs_server[] = $value;
                    }
             }

             $server[1] =$middle_server; //中学
             $server[2] =$benke_server; //本科
             $server[3] =$yjs_server; //研究生
            
             // 判断是否文书撰写
              $is_change = objectToArray(DB::table('lgp_home_serve')->where('serve_id',$param['serve_id'])->where('serve_name','like','%撰写%')->exists());
              // 判断是否文书撰写 赠送
             if($is_change){
                $data['give'] = self::$give;
             }
             //  if($tutor['education_id'] == 1){ //b本科
             //     $education[] = array('id'=>1,'education_name'=>'中学');
             //     $education[] = array('id'=>2,'education_name'=>'本科');
             //  }else{
             //     $education = array(
             //                array('id'=>1,'education_name'=>'中学'),
             //                array('id'=>2,'education_name'=>'本科'),
             //                array('id'=>3,'education_name'=>'研究生')
             //            );
             // }
         }else if($param['type'] == 3){ //留学培训


           
         }else if($param['type'] == 4){//秒识鸽校
            $server = objectToArray(DB::table('lgp_home_serve_content')->where(['serve_id'=>$param['serve_id'] ,'status'=>1,'tutor_id'=>$param['tutor_id']])->select('id as serve_content_id','serve_type','serve_title','serve_content','serve_price','serve_little_title')->get());
         }
         $data['server'] = $server;
         $data['education'] = $education;

        
         return returnJson(2,'success',$data);

    }

 

}