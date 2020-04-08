<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;

require __DIR__ . "/../../Tools/PHPMailer/phpmailer.class.php";
require __DIR__ . "/../../Tools/PHPMailer/smtp.class.php";
/*
	工具类
*/

/*  
	手机号授权 两个 一键授权 自己写的手机号验证登录  极速问答  所有的订单支付  成为导师  

	使用方法
	$param = array(
		'type'	=>	'类型',
		'subject'	=> 111,	// 主题
		'body'	=> 222,	// 内容
		'userArr' => ['jiangfuyou@beliwin.com'], // 用户
		'title'	=> '小灰鸽内容', // 标题
		'user'	=> '用户', // 用户
		'content'	=> '发送内容', // 发送内容
		'mobile'	=> '电话', // 电话
	);
    $res = \App\Http\Controllers\ToolController::SendMail( $param );
*/

Class ToolController extends Controller{
	// 发送短信
	public static function SendMail( $param = [] ){

		if( empty($param) ){
			$param = array(
				'type'	=>	'类型',
				'subject'	=> 111,	// 主题
				'body'	=> 222,	// 内容
				'userArr' => ['jiangfuyou@beliwin.com'], // 用户
				'title'	=> '小灰鸽内容', // 标题
				'user'	=> '用户', // 用户
				'mobile'	=> '电话', // 电话
			);
		}
			
	    // 实例化PHPMailer核心类
	    $mail = new \PHPMailer();
	    // 是否启用smtp的debug进行调试 开发环境建议开启 生产环境注释掉即可 默认关闭debug调试模式
	    $mail->SMTPDebug = 1;
	    // 使用smtp鉴权方式发送邮件
	    $mail->isSMTP();
	    // smtp需要鉴权 这个必须是true
	    $mail->SMTPAuth = true;
	    // 链接qq域名邮箱的服务器地址
	    $mail->Host = 'smtp.qq.com';
	    // 设置使用ssl加密方式登录鉴权
	    $mail->SMTPSecure = 'ssl';
	    // 设置ssl连接smtp服务器的远程服务器端口号
	    $mail->Port = 465;
	    // 设置发送的邮件的编码
	    $mail->CharSet = 'UTF-8';
	    // 设置发件人昵称 显示在收件人邮件的发件人邮箱地址前的发件人姓名
	    $mail->FromName = $param['title'];
	    // smtp登录的账号 QQ邮箱即可
	    $mail->Username = '872723999@qq.com';
	    // smtp登录的密码 使用生成的授权码
	    $email_pwd = https_request("http://reptile.fuyouhome.top/Tool/get_email_pwd.php");
	    $mail->Password = $email_pwd;
	    // 设置发件人邮箱地址 同登录账号
	    $mail->From = '872723999@qq.com';
	    // 邮件正文是否为html编码 注意此处是一个方法
	    $mail->isHTML(true);

	    // 设置收件人邮箱地址
	    if( is_array($param['userArr']) && !empty($param['userArr']) ){
	    	foreach ($param['userArr'] as $key => $val) {
	    		$mail->addAddress($val);
	    	}
	    }
	    // $mail->addAddress('yangzihui@beliwin.com');
	    // 添加多个收件人 则多次调用方法即可
	    // $mail->addAddress('87654321@163.com');
	    // 添加该邮件的主题
	    $mail->Subject = $param['subject'];
	    // 添加邮件正文
	    $mail->Body = $param['body'];
	    // 为该邮件添加附件
	    // $mail->addAttachment('./example.pdf');
	    // 发送邮件 返回状态
	    $status = $mail->send();

	    if( $status == 1 ){

	    	// 发送记录
	    	$addData['type'] = !empty($param['type']) ? $param['type'] : '';
	    	$addData['user'] = !empty($param['user']) ? $param['user'] : '';
	    	$addData['mobile'] = !empty($param['mobile']) ? $param['mobile'] : '';
	    	$addData['content'] = !empty($param['body']) ? $param['body'] : '';
	    	$addData['send_time'] = time();
	    	DB::table("lgp_send_mail")->insert($addData);

	    	return json_encode(['code'=>0,'msg'=>'成功']);
	    }else{
	    	return json_encode(['code'=>-1,'msg'=>'失败']);
	    }

	}
}