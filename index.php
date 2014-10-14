<?php
	/*
		This file is part of DuoLuo.
	
	    DuoLuo is free software: you can redistribute it and/or modify
	    it under the terms of the GNU General Public License as published by
	    the Free Software Foundation, either version 3 of the License, or
	    (at your option) any later version.
	
	    DuoLuo is distributed in the hope that it will be useful,
	    but WITHOUT ANY WARRANTY; without even the implied warranty of
	    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	    GNU General Public License for more details.
	
	    You should have received a copy of the GNU General Public License
	    along with DuoLuo.  If not, see <http://www.gnu.org/licenses/>.
	*/
	
	error_reporting(0);
	
	session_cache_expire(30);
	session_cache_limiter("private");
	session_start();
	session_regenerate_id(true);
	
	ob_start();
	
	define('DCRM',true);
	define('ROOT_PATH', dirname(__FILE__));
	
	date_default_timezone_set('Asia/Shanghai');
	
	require_once('include/function.php');
	require_once('include/config.inc.php');
	
	header('Content-Type: text/html; charset=UTF-8');
	header("Cache-Control: max-age=0");
	
	if (isset($_GET['type']) && $_GET['type'] == '1') {
		$admin = true;
	}
	
	if (isset($_GET['method']) && $_GET['method'] == 'submit') {
		if (isset($_POST['username']) && isset($_POST['password'])) {
			if (!isset($_POST['authcode']) || empty($_POST['authcode'])) {
				$error = 1;
			} elseif (strtolower($_POST['authcode']) != $_SESSION['VCODE']) {
				$error = 2;
			} else {
				$connection = mysql_connect(DB_HOST, DB_USER, DB_PASS);
				$connection_names = mysql_query('SET NAMES utf8');
				$connection_select = mysql_select_db(DB_NAME);
				if (!$connection || !$connection_names || !$connection_select) {
					httpinfo(500);
					exit();
				}
				$check_query = mysql_query("SELECT * FROM `users` WHERE `username` = '".mysql_real_escape_string($_POST['username'])."' LIMIT 1");
				if (!$check_query) {
					httpinfo(500);
					exit();
				} else {
					$user_check = mysql_fetch_assoc($check_query);
				}
				if (!$user_check) {
					$error = 6;
				} else {
					if (strtoupper(sha1($_POST['password'])) == strtoupper($user_check['password'])) {
						$_SESSION['uid'] = $user_check['username'];
						$_SESSION['connected'] = true;
						header('Location: admin.php');
						exit();
					} else {
						$error = 7;
					}
				}
			}
			$admin = true;
		} else {
			if (!isset($_POST['authcode']) || empty($_POST['authcode'])) {
				$error = 1;
			} elseif (strlen($_POST['authcode']) != 4 || strtolower($_POST['authcode']) != $_SESSION['VCODE']) {
				$error = 2;
			} elseif (!isset($_POST['tid']) || empty($_POST['tid'])) {
				$error = 3;
			} elseif (!ctype_digit($_POST['tid']) || strlen($_POST['tid']) != 15) {
				$error = 4;
			} else {
				$_SESSION['connected'] = true;
				header('Location: jump.php?stok='.authcode($_POST['tid'], 'ENCODE', $_SERVER['REMOTE_ADDR'], 3600));
				exit();
			}
			$admin = false;
		}
	} elseif (isset($_GET['method']) && $_GET['method'] == 'auth') {
		$_vc = new ValidateCode();
		$_vc->doimg();
		$_SESSION['VCODE'] = $_vc->getCode();
		exit();
	} elseif (isset($_GET['method']) && $_GET['method'] == 'logout') {
		setcookie('stok', '', time() - 3600);
		session_unset();
		session_destroy();
		$error = 5;
	} else {
		if (isset($_SESSION['connected']) && $_SESSION['connected'] === true) {
			if (isset($_SESSION['uid'])) {
				header('Location: admin.php');
				exit();
			} elseif (isset($_SESSION['tid'])) {
				header('Location: panel.php');
				exit();
			}
		} else {
			if (isset($_COOKIE['stok'])) {
				$_SESSION['connected'] = true;
				header('Location: jump.php?stok='.$_COOKIE['stok']);
				exit();
			}
		}
	}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>堕落工作室 - 登录</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<meta name="robots" content="index, deny" />
		<base target="_top">
		<link rel="shortcut icon" href="favicon.ico">
		<link rel="stylesheet" href="http://apps.bdimg.com/libs/bootstrap/2.3.2/css/bootstrap.min.css">
		<link rel="stylesheet" href="/css/misc.min.css" media="screen">
		<script src="http://libs.baidu.com/jquery/2.0.0/jquery.min.js" type="text/javascript"></script>
	</head>
	<body>
		<div class="well" style="width: auto;">
			<p><a href="/"><img src="CydiaIcon.png" style="width: 72px; height: 72px; border-radius: 6px;" /></a></p>
			<p>堕落工作室 - 登录</p>
			<hr />
<?php
	if (!isset($error)) {
?>
			<p>欢迎访问小店 <a href="http://421563756.taobao.com" target="_blank"><code>http://421563756.taobao.com</code></a></p>
			<p>本系统提供工单自助创建、查询与结算服务。</p>
<?php
	} else {
		if ($error == 1) {
?>
			<p>请填写验证码！</p>
<?php
		} elseif ($error == 2) {
?>
			<p>验证码不正确！</p>
<?php
		} elseif ($error == 3) {
?>
			<p>请填写淘宝订单号！</p>
<?php
		} elseif ($error == 4) {
?>
			<p>淘宝订单号不正确！</p>
<?php
		} elseif ($error == 5) {
?>
			<p>您已经退出登录。</p>
<?php
		} elseif ($error == 6) {
?>
			<p>用户名不存在。</p>
<?php
		} elseif ($error == 7) {
?>
			<p>密码不正确。</p>
<?php
		}
	}
?>
			<hr />
			<form id="common" method="post" action="/?method=submit" hidden="hidden">
				<p><input type="text" name="username" placeholder="用户名" required="required" /></p>
				<p><input type="password" name="password" placeholder="密码" required="required" /></p>
				<p>
					<input type="text" name="authcode" maxlength="4" placeholder="验证码" required="required" style="margin-top: 8px; height: 24px; width:120px;" />
					<img id="authpic1" style="height: 36px; width: 88px; border-radius: 6px;" onclick="refresh(1);" />
				</p>
				<hr />
				<input class="btn btn-success" type="submit" value="立即登录" />
				<input type="button" class="btn btn-warning" onclick="change(1);" value="普通查询" />
			</form>
			<form id="admin" method="post" action="/?method=submit">
				<p><input type="text" name="tid" maxlength="15" placeholder="请填写淘宝订单号" required="required" /></p>
				<p>
					<input type="text" name="authcode" maxlength="4" placeholder="验证码" required="required" style="margin-top: 8px; height: 24px; width:120px;" />
					<img id="authpic2" style="height: 36px; width: 88px; border-radius: 6px;" onclick="refresh(2);" />
				</p>
				<hr />
				<input class="btn btn-success" type="submit" value="立即查询" />
				<input type="button" class="btn btn-warning" onclick="change(2);" value="管理登录" />
			</form>
			<hr />
			<p>© 2014 <a href="http://82flex.com">82Flex</a>. 由 <a href="http://82flex.com/projects">DCRM</a> 强力驱动.</p>
		</div>
		<script type="text/javascript">
			function refresh(ch) {
				if (document.getElementById("authpic"+ch)) {
					document.getElementById("authpic"+ch).src = '/?method=auth&rand=' + new Date().getTime();
				}
			}
			function change(ui) {
				if (ui == 1) {
					refresh(2);
					$("#common").fadeOut("fast",function () {
						$("#admin").fadeIn("fast");
					});
				} else {
					refresh(1);
					$("#admin").fadeOut("fast",function () {
						$("#common").fadeIn("fast");
					});
				}
			}
			refresh(2);
		</script>
	</body>
</html>