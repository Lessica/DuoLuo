<?php
	error_reporting(0);
	
	session_cache_expire(30);
	session_cache_limiter("private");
	session_start();
	
	ob_start();
	
	define('DCRM',true);
	define('ROOT_PATH', dirname(__FILE__));
	
	date_default_timezone_set('Asia/Shanghai');
	
	require_once('include/function.php');
	require_once('include/config.inc.php');
	
	header('Content-Type: text/html; charset=UTF-8');
	header("Cache-Control: max-age=0");
	
	if (!isset($_SESSION['connected']) || $_SESSION['connected'] != true) {
		header('Location: /?method=logout');
		exit();
	}
	if (isset($_SESSION['uid'])) {
		$manage = true;
	} else {
		$manage = false;
	}
	
	if (!isset($_GET['stok'])) {
		httpinfo(400);
	} else {
		$stok = str_replace(' ', '+', $_GET['stok']);
		$query_tid = authcode($stok, 'DECODE', $_SERVER['REMOTE_ADDR']);
		if (ctype_digit($query_tid) && strlen($query_tid) == 15) {
			$_SESSION['tid'] = $query_tid;
			setcookie('stok', $stok, time() + 3600);
			$_SESSION['REFERER'] = $_SERVER['HTTP_REFERER'];
		} else {
			header('Location: /?method=logout');
			exit();
		}
	}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>堕落工作室 - 跳转</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<meta name="robots" content="index, deny" />
		<base target="_top">
		<link rel="shortcut icon" href="favicon.ico">
		<link rel="stylesheet" href="http://libs.baidu.com/bootstrap/3.0.3/css/bootstrap.min.css">
		<link rel="stylesheet" href="/css/misc.min.css" media="screen">
	</head>
	<body>
		<div class="well" style="width: auto;">
			<p><a href="/"><img src="CydiaIcon.png" style="width: 72px; height: 72px; border-radius: 6px;" /></a></p>
			<p>堕落工作室 - 跳转</p>
			<hr />
			<p>淘宝订单号：<code><?php echo($query_tid); ?></code> <a href="/?method=logout">退出登录</a></p>
<?php
	if ($manage) {
?>
			<p>尊敬的管理员：<code id="admin"><?php echo(htmlspecialchars($_SESSION['uid'])); ?></code></p>
<?php
	} else {
?>
			<p>您的 IP 地址：<code><?php echo($_SERVER['REMOTE_ADDR']); ?></code></p>
<?php
	}
?>
			<p id="tips">正在查询，请稍候……</p>
			<hr />
			<p>© 2014 <a href="http://82flex.com">82Flex</a>. 由 <a href="http://82flex.com/projects">DCRM</a> 强力驱动.</p>
		</div>
		<script type="text/javascript">
			var i = 0;
			var intervalid;
			intervalid = setInterval("fun()", 1000);
			function fun() {
			    if (i == 0) {
			        window.location.href = "panel.php";
			        clearInterval(intervalid);
			    }
			    i--; 
			}
		</script>
	</body>
</html>