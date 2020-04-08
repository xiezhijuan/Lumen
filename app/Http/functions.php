<?php

/**
 * [ceshi 测试函数]
 * @return [type] [description]
 */
function ceshi($res)
{
	print_r($res);
}


/**
 * [json 统一返回格式]
 * @param  [type] $code    [  2 : 成功 ,-1 :  操作失败（申请，预约，咨询,评价，编辑,缺少参数......）]
 * @param  string $message [成功 ： success ,失败 :  （申请失败，预约失败,缺少参数......）]
 * @param  array  $data    [array()]
 * @return [type]          [json]
 */
function returnJson($code, $message = '', $data = array()) {
        
	if(!is_numeric($code)) {
	    return '';
	}

	$result = array(
	    'code' => $code,
	    'message' => $message,
	    'data' => $data
	);

	echo json_encode($result);
	exit;
}



/**
 * [objectToArray 先编码成json字符串，再解码成数组]
 * @param  [type] $object [description]
 * @return [type]         [description]
 */
function objectToArray($object) {
    return json_decode(json_encode($object), true);
}



/**
 * [is_judge 判断是否未为空 或假]
 * @param  [type]  $res [description]
 * @return boolean      [description]
 */
function is_judge($res)
{
	if (empty($res)) {
        return false;
    }else{
        return true;
    }
}
/**
 * [getNumW 获取超过万的带w表示，并保留两位小数 或假]
 * @param  [type]  $res [description]
 * @return boolean      [description]
 */
function getNumW($number){
    if(intval($number)/10000 >=1 ){ //过万
        $number =floatval(sprintf("%.2f", intval($number)/10000)).'w';
              
    }
    return $number;
}


/*
*   测试网站开发
*/
function imgTailor($img_url,$width='100',$height='100',$host='http://admin.highschool86.com')
{

    // print_r($img_url);
    // 文件名处理
    $last_path_num = strrpos($img_url,'/') + 1;
    $img_url_path = substr($img_url, 0,$last_path_num);
    $img_url_name = substr($img_url, $last_path_num);

    $new_url_array = explode('.',$img_url_name); 

    $new_url_array_name = $new_url_array[0];
    $new_url_array_format = $new_url_array[1]; 

    $new_img_url = $new_url_array_name . '_' .$width. '_'.$height . '.' . $new_url_array_format;

    //判断文件是否存在
    if( file_exists('./static/qiantai/' . $new_img_url)){
        return '/static/qiantai/' . $new_img_url;

    }else{
        // 下载文件
        $img_flow = file_get_contents($host . $img_url);
        $is_down_load = file_put_contents('./static/qiantai/' . $img_url_name , $img_flow);

        // 下载成功
        if (!$is_down_load) {
            // 返回图片
            return $host . $img_url;
        }else{
            // 裁剪图片
            thrum('./static/qiantai/' . $img_url_name,'./static/qiantai/' . $new_img_url,$width,$height);

            return '/static/qiantai/' . $new_img_url;
        }


    }
    

}


/*
 * 图片裁剪工具
 * 将指定文件裁剪成正方形
 * 以中心为起始向四周裁剪
 * @param $src_path string 源文件地址
 * @param $des_path string 保存文件地址
 * @param $des_w double 目标图片宽度
 * */
function img_cut_square($src_path,$des_path,$des_w=200,$des_h=200){

    //创建源图的实例, 从字符串中的图像流新建一副图像
    $src = imagecreatefromstring(file_get_contents($src_path));
     
    //裁剪开区域左上角的点的坐标
    $x = 300;
    $y = 80;
    //裁剪区域的宽和高
    $width = $des_w;
    $height = $des_h;
    //最终保存成图片的宽和高，和源要等比例，否则会变形
    $final_width = $des_w;
    $final_height = $des_h; 
    //将裁剪区域复制到新图片上，并根据源和目标的宽高进行缩放或者拉升
    $des_path1 = imagecreatetruecolor($final_width, $final_height);
    imagecopyresampled($des_path1, $src, 0, 0, $x, $y, $final_width, $final_height, $width, $height);
    //输出图片
    header('Content-Type: image/jpg');
    imagejpeg($des_path1,$des_path);
    imagedestroy($src);
    imagedestroy($des_path1);
}


function thrum($src_file,$des_path,$des_w,$des_h){

    //封装一个图片处理函数（等比例缩放）
    // 传入的第一个参数为图片的地址，第二和第三个元素为目的图片的宽高
    error_reporting(E_ALL^E_NOTICE^E_WARNING);
    //获取图片的类型
    $srcarr = getimagesize($src_file);
    //处理图片创建函数和图片输出函数
    switch($srcarr[2]){
    case 1://gif
    $imagecreatefrom = 'imagecreatefromgif';
    $imageout = 'imagegif';
    break;
    case 2://jpg
    $imagecreatefrom = 'imagecreatefromjpeg';
    $imageout = 'imagejpeg';
    break;
    case 3://png
    $imagecreatefrom = 'imagecreatefrompng';
    $imageout = 'imagepng';
    break;
    }
    // 创建原图资源
    $src_img = $imagecreatefrom($src_file);
    //获取原图的宽高
    $src_w = imagesx($src_img);
    $src_h = imagesy($src_img);
    // 计算缩放比例（用原图片的宽高分别处以对应目的图片的宽高，选择比例大的作为基准进行缩放）
        // $scale = ($src_w/$des_w)>($src_h/$des_h)?($src_w/$des_w):($src_h/$des_h);
        // $scale = ($src_h/$des_h);
        // //计算实际缩放时目的图的宽高（向下取整）
        // $des_w = floor($src_w/$scale);
        // $des_h = floor($src_h/$scale);
    //创建画布
    $des_img = imagecreatetruecolor($des_w, $des_h);
    //设置缩放起点
    $des_x = 0;
    $des_y = 0;
    $src_x = 0;
    $src_y = 0;
    //缩放
    imagecopyresampled($des_img, $src_img, $des_x, $des_y, $src_x, $src_y, $des_w, $des_h, $src_w, $src_h);
    //输出图片
    //header('content-type:image/jpeg');
    //获取源文件的文件名
    $t_file = basename($src_file);
    // 获取源文件的路径名
    $t_dir = dirname($src_file);
    // 生成保存文件的文件路径名
    $s_file = $t_dir .'/'.'t_'.$t_file;
    $imageout($des_img,$des_path);
}




/**
 * 写入操作日志  
 *存储路径： pulbic/data/log/
 * @param $str string/array 存储记录
 * @param $dir string 存储的目录名字
 * @param $file string 存储的文件名字
 * */
function writelog($str,$dir='log',$file='log')
{
    $path = __DIR__.'/../../public/data/log/'.date('Ym'). '/' . $dir;
    if(!file_exists($path)){
         mkdir($path, 0777, true);
    }
   $str = is_array($str) ? var_export($str,true):$str;
   file_put_contents($path.'/' . $file .'_' . date('Ymd') . '.log', date('Y-m-d H:i:s') .' ' . $str . "\r\n", FILE_APPEND);

} 


// curl模拟http请求
function https_request($url,$data=null){
    $curl = curl_init();//初始化
    //curl模拟get请求
    curl_setopt($curl,CURLOPT_URL,$url);
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,false);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,false);
    //curl模拟post请求
    if(!empty($data)){
      curl_setopt($curl,CURLOPT_POST,1);
      curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
    }
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}

