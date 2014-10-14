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
	
	$connection = mysql_connect(DB_HOST, DB_USER, DB_PASS);
	$connection_names = mysql_query('SET NAMES utf8');
	$connection_select = mysql_select_db(DB_NAME);
	if (!$connection || !$connection_names || !$connection_select) {
		httpinfo(500);
	} else {
		if (!ctype_digit($_SESSION['tid']) || strlen($_SESSION['tid']) != 15) {
			httpinfo(400);
		}
		$query_tid = mysql_real_escape_string(intval($_SESSION['tid']));
		$user_query = mysql_query("SELECT * FROM `tickets` WHERE `tid` = '".$query_tid."' LIMIT 1");
		if (!$user_query) {
			httpinfo(500);
		}
		$user_info = mysql_fetch_assoc($user_query);
		if (!$user_info) {
			$empty_info = true;
		} else {
			$empty_info = false;
			$stat = intval($user_info['stat']);
		}
	}
	
	if (isset($_GET['method']) && $_GET['method'] == 'set') {
		if ($stat == 0 || $stat == 1) {
			if ($stat == 0) {
				$vaild = array('card','password','subaccount','camp','section','server','charactername','occupation','gold','mobile','qq','aliim','requirements','description');
				mysql_query("UPDATE `tickets` SET `createtime` = '".date('Y-m-d H:i:s')."' WHERE `tid` = '".$query_tid."'");
			} elseif ($stat == 1) {
				if ($_POST['password'] == $user_info['password']) {
					$vaild = array('subaccount','camp','section','server','charactername','occupation','gold','mobile','qq','aliim','requirements','description');
				} elseif ($manage) {
					$vaild = array('card','password','subaccount','camp','section','server','charactername','occupation','gold','mobile','qq','aliim','requirements','description');
				} else {
					echo("ERR01");
					exit();
				}
			}
			foreach ($vaild as $value) {
				if (!empty($_POST[$value])) {
					//$safe_value = mysql_real_escape_string(strip_tags($_POST[$value]));
					$safe_value = mysql_real_escape_string($_POST[$value]);
					mysql_query("UPDATE `tickets` SET `".$value."` = '".$safe_value."' WHERE `tid` = '".$query_tid."'");
				}
			}
			mysql_query("UPDATE `tickets` SET `stat` = '2' WHERE `tid` = '".$query_tid."'");
			if ($stat == 0) {
				if ($manage) {
					mysql_query("INSERT INTO `works`(`tid`,`type`,`statments`) VALUES('".$query_tid."','1','管理员 ".mysql_real_escape_string($_SESSION['uid'])." 创建了此工单信息')");
				} else {
					mysql_query("INSERT INTO `works`(`tid`,`type`,`statments`) VALUES('".$query_tid."','1','".$_SERVER["REMOTE_ADDR"]." 工单信息创建完成。')");
				}
			} elseif ($stat == 1) {
				if ($manage) {
					mysql_query("INSERT INTO `works`(`tid`,`type`,`statments`) VALUES('".$query_tid."','1','管理员 ".mysql_real_escape_string($_SESSION['uid'])." 修改了此工单信息')");
				} else {
					mysql_query("INSERT INTO `works`(`tid`,`type`,`statments`) VALUES('".$query_tid."','1','".$_SERVER["REMOTE_ADDR"]." 工单信息修改完成。')");
				}
			} else {
				echo("ERR02");
				exit();
			}
			echo("SUCCESS");
			exit();
		} else {
			echo("ERR02");
			exit();
		}
	} elseif (isset($_GET['method']) && $_GET['method'] == 'unlock') {
		if ($manage && $stat != -1 && $stat != 1) {
			mysql_query("UPDATE `tickets` SET `stat` = '1' WHERE `tid` = '".$query_tid."'");
			mysql_query("INSERT INTO `works`(`tid`,`type`,`statments`) VALUES('".$query_tid."','1','管理员 ".mysql_real_escape_string($_SESSION['uid'])." 解锁了此工单。')");
		} else {
			httpinfo(403);
		}
		exit();
	} elseif (isset($_GET['method']) && $_GET['method'] == 'lock') {
		if ($manage && $stat != -1 && $stat != 2) {
			mysql_query("UPDATE `tickets` SET `stat` = '2' WHERE `tid` = '".$query_tid."'");
			mysql_query("INSERT INTO `works`(`tid`,`type`,`statments`) VALUES('".$query_tid."','1','管理员 ".mysql_real_escape_string($_SESSION['uid'])." 锁定了此工单。')");
		} else {
			httpinfo(403);
		}
		exit();
	} elseif (isset($_GET['method']) && $_GET['method'] == 'finish') {
		if ($manage && $stat != -1) {
			mysql_query("UPDATE `tickets` SET `stat` = '-1' WHERE `tid` = '".$query_tid."'");
			mysql_query("UPDATE `tickets` SET `password` = '' WHERE `tid` = '".$query_tid."'");
			mysql_query("INSERT INTO `works`(`tid`,`type`,`statments`) VALUES('".$query_tid."','1','管理员 ".mysql_real_escape_string($_SESSION['uid'])." 结算了此工单。')");
		} else {
			httpinfo(403);
		}
		exit();
	} elseif (isset($_GET['method']) && $_GET['method'] == 'start') {
		if ($manage && $stat != -1 && $stat != 3) {
			mysql_query("UPDATE `tickets` SET `stat` = '3' WHERE `tid` = '".$query_tid."'");
			mysql_query("INSERT INTO `works`(`tid`,`type`,`statments`) VALUES('".$query_tid."','1','管理员 ".mysql_real_escape_string($_SESSION['uid'])." 正在处理此工单。')");
		} else {
			httpinfo(403);
		}
		exit();
	} elseif (isset($_GET['method']) && $_GET['method'] == 'stop') {
		if ($manage && $stat != -1 && $stat != 4) {
			mysql_query("UPDATE `tickets` SET `stat` = '4' WHERE `tid` = '".$query_tid."'");
			mysql_query("INSERT INTO `works`(`tid`,`type`,`statments`) VALUES('".$query_tid."','1','管理员 ".mysql_real_escape_string($_SESSION['uid'])." 暂停处理此工单。')");
		} else {
			httpinfo(403);
		}
		exit();
	} elseif (isset($_GET['method']) && $_GET['method'] == 'drecord') {
		if ($manage && isset($_GET['rid']) && ctype_digit($_GET['rid'])) {
			mysql_query("DELETE FROM `works` WHERE `id` = '".$_GET['rid']."'");
		} else {
			httpinfo(403);
		}
		exit();
	} elseif (isset($_GET['method']) && $_GET['method'] == 'add') {
		if ($manage && isset($_POST['statment']) && isset($_POST['type'])) {
			if ($stat == 3) {
				switch ($_POST['type']) {
					case 1:
						$nstat = '已达成';
						break;
					case 2:
						$nstat = '已忽略';
						break;
					case 3:
						$nstat = '意外事件';
						break;
					case 4:
						$nstat = '已终止';
						break;
					default:
						$nstat = '';
						break;
				}
				mysql_query("INSERT INTO `works`(`tid`,`type`,`statments`) VALUES('".$query_tid."','1','进度：".$nstat." ".mysql_real_escape_string($_POST['statment'])."')");
			} else {
				httpinfo(403);
			}
		} else {
			httpinfo(400);
		}
	}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>堕落工作室 - 查询</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<meta name="robots" content="index, deny" />
		<base target="_top">
		<link rel="shortcut icon" href="favicon.ico">
		<link rel="stylesheet" href="http://apps.bdimg.com/libs/bootstrap/2.3.2/css/bootstrap.min.css">
		<link rel="stylesheet" href="/css/misc.min.css" media="screen">
<?php
	if ($manage) {
?>
		<script src="/css/ZeroClipboard.min.js" type="text/javascript"></script>
		<script type="text/javascript">
			var clip = null;
			function init() {
				ZeroClipboard.setMoviePath('/css/ZeroClipboard.swf');
				clip = new ZeroClipboard.Client();
				clip.setHandCursor(true);
				clip.addEventListener('load', my_load);
				clip.addEventListener('mouseOver', my_mouse_over);
				clip.addEventListener('complete', my_complete);
				if (document.getElementById('d_clip_button')) {
					clip.glue('d_clip_button');
				}
			}
			function my_mouse_over(client) {
				var str = "工单号：" + document.getElementById("tid").innerHTML +"\n";
				str += "[战网信息]\n";
				str += "战网通行证：" + document.getElementsByName("card")[0].value + "\n";
				if (document.getElementsByName("password")[0]) {
					str += "战网密码：" + document.getElementsByName("password")[0].value + "\n";
				}
				str += "[角色信息]\n";
				str += "游戏子帐号：" + document.getElementsByName("subaccount")[0].value + "\n";
				var select = document.getElementsByName("section")[0];
				str += "大区：" + select.options[select.selectedIndex].text + "\n";
				str += "服务器：" + document.getElementsByName("server")[0].value + "\n";
				str += "角色名：" + document.getElementsByName("charactername")[0].value + "\n";
				select = document.getElementsByName("camp")[0];
				str += "阵营：" + select.options[select.selectedIndex].text + "\n";
				select = document.getElementsByName("occupation")[0];
				str += "职业：" + select.options[select.selectedIndex].text + "\n";
				str += "初始金币数量：" + document.getElementsByName("gold")[0].value + "\n";
				str += "[联系方式]\n";
				str += "手机：" + document.getElementsByName("mobile")[0].value + "\n";
				str += "QQ：" + document.getElementsByName("qq")[0].value + "\n";
				str += "阿里旺旺：" + document.getElementsByName("aliim")[0].value + "\n";
				str += "[代练需求]\n";
				str += document.getElementsByName("requirements")[0].innerHTML + "\n";
				str += "[备注]\n";
				str += document.getElementsByName("description")[0].innerHTML + "\n";
				str += "[系统信息]\n";
				str += "提交时间：" + document.getElementsByName("createtime")[0].value + "\n";
				str += "最后修改时间：" + document.getElementsByName("timestamp")[0].value + "\n";
				str += "操作员：" + document.getElementById("admin").innerHTML + "\n";
				clip.setText(str);
			}
			function my_load(client) {
				document.getElementById("d_clip_button").disabled = "";
			}
			function my_complete(client, text) {
				alert("工单信息成功复制到剪贴板。");
			}
		</script>
<?php
	}
?>
	</head>
<?php
	if ($manage) {
?>
	<body onload="javascript:init();">
<?php
	} else {
?>
	<body>
<?php
	}
?>
		<div class="well" style="width: auto;">
			<p><a href="/"><img src="CydiaIcon.png" style="width: 72px; height: 72px; border-radius: 6px;" /></a></p>
			<p>堕落工作室 - 查询</p>
			<hr />
			<p>淘宝订单号：<code id="tid"><?php echo($query_tid); ?></code> <a href="/?method=logout">退出登录</a></p>
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
	if ($empty_info) {
		if ($manage) {
			mysql_query("INSERT INTO `tickets`(`tid`,`stat`) VALUES('".$query_tid."','0')");
			mysql_query("INSERT INTO `works`(`tid`,`type`,`statments`) VALUES('".$query_tid."','1','管理员 ".mysql_real_escape_string($_SESSION['uid'])." 创建了此工单。')");
?>
			<p id="tips">订单创建成功，请联系用户进行信息填写！</p>
			<hr />
			<h4>管理操作</h4>
			<br />
			<div id="operations">
				<a class="btn btn-info" href="javascript:history.go(0);">手动填写</a>
			</div>
<?php
		} else {
?>
			<p id="tips">数据库中查询不到该订单号。<br />请拍下后联系管理员创建订单，然后刷新本页面。</p>
<?php
		}
	} elseif ($stat == -1) {
		if ($manage) {
?>
			<p id="lock">该工单已经结算。</p>
<?php
		} else {
			$hour = (time()-strtotime($user_info['createtime']))/3600;
			if ($hour > 336) {
?>
			<p id="lock">该工单已过期。</p>
<?php
				$outofdate = true;
			} else {
?>
			<p id="lock">该工单已经完成。</p>
<?php
				$outofdate = false;
			}
		}
	} elseif ($stat == 0) {
?>
			<p id="tips">请认真填写如下信息，提交后将不可随意修改！</p>
<?php
	} elseif ($stat == 1) {
?>
			<p id="tips">请谨慎修改如下信息。</p>
<?php
	} elseif ($stat == 2) {
?>
			<p id="lock">该工单已经提交并锁定。</p>
<?php
	} elseif ($stat == 3) {
?>
			<p id="lock">此工单正在处理中，请等待代练完成。</p>
<?php
	} elseif ($stat == 4) {
?>
			<p id="lock">此工单已暂停处理，请等待代练继续。</p>
<?php
	}
	if (!$empty_info && ($stat != -1 || $manage || ($stat == -1 && $outofdate == false))) {
		if ($stat == 2 || $stat == 3 || $stat == 4 || $manage || ($stat == -1 && $outofdate == false)) {
?>
			<hr />
<?php
			if ($manage && $stat != -1) {
?>
			<h4>管理操作</h4>
			<br />
<?php
				if ($stat == 3 || $stat == 4) {
?>
			<table class="table">
				<thead>
					<tr>
						<th>操作</th>
						<th>代练需求</th>
					</tr>
				</thead>
				<tbody>
<?php
					$require_array = explode("\n", $user_info['requirements']);
					$r = 0;
					foreach ($require_array as $value) {
?>
					<tr>
						<td><a href="javascript:fill(<?php echo($r); ?>);">填充</a></td>
						<td><?php echo(htmlspecialchars($value)); ?></td>
					</tr>
<?php
						$r++;
					}
?>
				</tbody>
			</table>
<?php
				}
?>
			<form method="post" action="?method=add">
<?php
				if ($stat == 3) {
?>
				<p>
					<select name="type" style="width: 100px;">
						<option value="1">已达成</option>
						<option value="2">已忽略</option>
						<option value="3">意外事件</option>
						<option value="4">异常终止</option>
					</select>
					<input type="text" name="statment" placeholder="进度详情" style="width: 300px;" />
				</p>
<?php
				}
				if ($stat == 1 || $stat == 2) {
?>
				<a class="btn btn-success" onclick="javascript:opt(1);">开始代练</a>
<?php
				} elseif ($stat == 3) {
?>
				<input type="submit" class="btn btn-info" onclick="javascript:opt(6);" value="添加进度" />
				<a class="btn btn-success" onclick="javascript:opt(5);">暂停代练</a>
<?php
				} elseif ($stat == 4) {
?>
				<a class="btn btn-success" onclick="javascript:opt(1);">继续代练</a>
<?php
				}
				if ($stat != 0 && $stat != 1) {
?>
				<a class="btn btn-warning" onclick="javascript:opt(2);">解锁工单信息</a>
<?php
				} else {
?>
				<a class="btn btn-warning" onclick="javascript:opt(3);">锁定工单信息</a>
<?php
				}
?>
				<a class="btn btn-danger" onclick="javascript:opt(4);">结算工单</a>
				<button id="d_clip_button" class="btn" disabled>复制信息</button>
			</form>
			<hr />
<?php
			}
?>
			<table class="table">
				<thead>
					<tr>
<?php
			$stat_query = mysql_query("SELECT * FROM `works` WHERE `tid` = '".$query_tid."'");
			if (!$stat_query) {
				httpinfo(500);
				exit();
			}
			if ($manage) {
?>
						<th>操作</th>
<?php
			}
?>
						<th width="150px">时间</th>
						<th>状态</th>
					</tr>
				</thead>
				<tbody>
<?php
			while ($record_assoc = mysql_fetch_assoc($stat_query)) {
?>
					<tr>
<?php
				if ($manage) {
?>
						<td><a href="javascript:opt(6,<?php echo($record_assoc['id']); ?>);">删除</a></td>
<?php
				}
?>
						<td><?php echo(htmlspecialchars($record_assoc['timestamp'])); ?></td>
						<td><?php echo(htmlspecialchars($record_assoc['statments'])); ?></td>
					</tr>
<?php
			}
?>
				</tbody>
			</table>
<?php
		}
		if ($manage || $stat != -1) {
?>
			<hr />
			<div class="row">
				<form id="mainform" class="form-horizontal" method="POST" onsubmit="return confirm_submit();" onreset="return confirm_reset();">
					<h4>战网信息</h4>
					<br />
					<div class="group-control">
						<label class="control-label">* 战网通行证</label>
						<div class="controls">
<?php
			if ($stat == 0 || $manage) {
?>
							<input type="email" name="card" value="<?php if(!empty($user_info['card'])){if(($stat==2||$stat==-1) && !$manage){echo(htmlspecialchars(starhide($user_info['card'])));}else{echo(htmlspecialchars($user_info['card']));}} ?>" required />
<?php
			} else {
?>
							<input type="email" name="card" value="<?php if(!empty($user_info['card'])){echo(htmlspecialchars(starhide($user_info['card'])));} ?>" required readonly />
<?php
			}
?>
						</div>
					</div>
					<br />
<?php
			if ($stat == 0 || $stat == 1 || ($manage && $stat != -1)) {
?>
					<div class="group-control">
						<label class="control-label">* 战网密码</label>
						<div class="controls">
<?php
				if ($manage) {
?>
							<input type="text" pattern="[\S]{8,16}" id="password" name="password" value="<?php if(!empty($user_info['password'])){echo(htmlspecialchars($user_info['password']));} ?>" maxlength="16" required />
<?php
				} else {
?>
							<input type="password" pattern="[\S]{8,16}" id="password" name="password" maxlength="16" required />
<?php
				}
				if ($stat != 0 && !$manage) {
?>
							<p class="help-block">请输入创建工单时输入的密码。<br /><div style="color: red;">如不匹配，则无法修改工单信息。</div></p>
<?php
				}
?>
						</div>
					</div>
					<br />
<?php
				if ($stat == 0 && !$manage) {
?>
					<div class="group-control">
						<label class="control-label">* 确认战网密码</label>
						<div class="controls">
							<input type="password" pattern="[\S]{8,16}" id="confirmpassword" name="confirmpassword" maxlength="16" required />
							<p class="help-block">请再输入一次战网密码，确认无误。</p>
						</div>
					</div>
					<br />
<?php
				}
			}
?>
					<hr />
					<h4>角色信息</h4>
					<br />
					<div class="group-control">
						<label class="control-label">* 游戏子帐号</label>
						<div class="controls">
							<input type="text" pattern="WoW[0-9]{1,2}" name="subaccount" value="<?php if(!empty($user_info['subaccount'])){echo(htmlspecialchars($user_info['subaccount']));}else{echo('WoW1');} ?>" placeholder="WoW1" required />
						</div>
					</div>
					<br />
					<div class="group-control">
						<label class="control-label">* 大区</label>
						<div class="controls">
							<select name="section">
<?php
			function getzmethod($opt) {
				switch ($opt) {
					case 1:
						$opt_text = "一区";
						break;
					case 2:
						$opt_text = "三区";
						break;
					case 3:
						$opt_text = "五区";
						break;
					case 4:
						$opt_text = "十区";
						break;
					default:
						$opt_text = "";
				}
				return $opt_text;
			}
			for ($opt = 1; $opt <= 4; $opt++) {
				if (intval($user_info['section']) == $opt) {
					echo '<option value="' . $opt . '" selected="selected">' . htmlspecialchars(getzmethod($opt)) . '</option>\n';
				}
				else {
					echo '<option value="' . $opt . '">' . htmlspecialchars(getzmethod($opt)) . '</option>\n';
				}
			}
?>
							</select>
						</div>
					</div>
					<br />
					<div class="group-control">
						<label class="control-label">* 服务器</label>
						<div class="controls">
							<input type="text" name="server" value="<?php if(!empty($user_info['server'])){echo(htmlspecialchars($user_info['server']));} ?>" required />
						</div>
					</div>
					<br />
					<div class="group-control">
						<label class="control-label">* 角色名</label>
						<div class="controls">
							<input type="text" name="charactername" value="<?php if(!empty($user_info['charactername'])){echo(htmlspecialchars($user_info['charactername']));} ?>" required />
						</div>
					</div>
					<br />
					<div class="group-control">
						<label class="control-label">* 阵营</label>
						<div class="controls">
							<select name="camp">
<?php
			if (empty($user_info['camp']) || intval($user_info['camp']) != 2) {
?>
								<option value="1" selected>联盟</option>
								<option value="2">部落</option>
<?php
			} else {
?>
								<option value="1">联盟</option>
								<option value="2" selected>部落</option>
<?php
			}
?>
							</select>
						</div>
					</div>
					<br />
					<div class="group-control">
						<label class="control-label">* 职业</label>
						<div class="controls">
							<select name="occupation">
<?php
			function getzmethod_2($opt) {
				switch ($opt) {
					case 1:
						$opt_text = "战士";
						break;
					case 2:
						$opt_text = "圣骑士";
						break;
					case 3:
						$opt_text = "萨满祭司";
						break;
					case 4:
						$opt_text = "潜行者";
						break;
					case 5:
						$opt_text = "牧师";
						break;
					case 6:
						$opt_text = "法师";
						break;
					case 7:
						$opt_text = "德鲁伊";
						break;
					case 8:
						$opt_text = "猎人";
						break;
					case 9:
						$opt_text = "术士";
						break;
					default:
						$opt_text = "";
				}
				return $opt_text;
			}
			for ($opt = 1; $opt <= 9; $opt++) {
				if (intval($user_info['occupation']) == $opt) {
					echo '<option value="' . $opt . '" selected>' . htmlspecialchars(getzmethod_2($opt)) . '</option>\n';
				}
				else {
					echo '<option value="' . $opt . '">' . htmlspecialchars(getzmethod_2($opt)) . '</option>\n';
				}
			}
?>
							</select>
						</div>
					</div>
					<br />
					<div class="group-control">
						<label class="control-label">初始金币数量</label>
						<div class="controls">
							<input type="number" pattern="[0-9]{1,18}" name="gold" value="<?php if(!empty($user_info['gold'])){echo(htmlspecialchars($user_info['gold']));} ?>" />
							<p class="help-block">请填写当前角色背包现有金币数。<br /><div style="color: red;">交单时按照此数量为准。</div></p>
						</div>
					</div>
					<hr />
					<h4>联系方式</h4>
					<br />
					<div class="group-control">
						<label class="control-label">* 手机</label>
						<div class="controls">
							<input type="tel" pattern="1[0-9]{10}" name="mobile" maxlength="11" value="<?php if(!empty($user_info['mobile'])){if($stat!=0&&$stat!=1&&!$manage){echo(htmlspecialchars(starhide($user_info['mobile'])));}else{echo(htmlspecialchars($user_info['mobile']));}} ?>" required />
						</div>
					</div>
					<br />
					<div class="group-control">
						<label class="control-label">QQ</label>
						<div class="controls">
							<input type="text" pattern="[0-9]{5,11}" name="qq" value="<?php if(!empty($user_info['qq'])){if($stat!=0&&$stat!=1&&!$manage){echo(htmlspecialchars(starhide($user_info['qq'])));}else{echo(htmlspecialchars($user_info['qq']));}} ?>" />
						</div>
					</div>
					<br />
					<div class="group-control">
						<label class="control-label">阿里旺旺</label>
						<div class="controls">
							<input type="text" name="aliim" value="<?php if(!empty($user_info['aliim'])){echo(htmlspecialchars($user_info['aliim']));} ?>" />
						</div>
					</div>
					<hr />
					<h4>代练需求</h4>
					<br />
					<div class="group-control">
						<label class="control-label">代练需求</label>
						<div class="controls">
							<textarea name="requirements" style="height: 80px; width: 400px;" maxlength="1024" required><?php if(!empty($user_info['requirements'])){echo(htmlspecialchars($user_info['requirements']));} ?></textarea>
							<p class="help-block">一行一个需求，以便查看进度。</p>
						</div>
					</div>
					<hr />
					<h4>备注</h4>
					<br />
					<div class="group-control">
						<label class="control-label">备注</label>
						<div class="controls">
							<textarea name="description" style="height: 80px; width: 400px;" maxlength="1024"><?php if(!empty($user_info['description'])){echo(htmlspecialchars($user_info['description']));} ?></textarea>
						</div>
					</div>
					<hr />
					<h4>附加信息</h4>
					<br />
					<div class="group-control">
						<label class="control-label">提交时间</label>
						<div class="controls">
							<input type="text" id="createtime" name="createtime" value="<?php if(!empty($user_info['createtime'])){echo(htmlspecialchars($user_info['createtime']));} ?>" readonly required />
						</div>
					</div>
					<br />
					<div class="group-control">
						<label class="control-label">最后修改时间</label>
						<div class="controls">
							<input type="text" id="timestamp" name="timestamp" value="<?php if(!empty($user_info['timestamp'])){echo(htmlspecialchars($user_info['timestamp']));} ?>" readonly required />
						</div>
					</div>
<?php
			if ($stat == 0 || $stat == 1) {
?>
					<hr />
<?php
				if ($manage) {
?>
					<h4>提交信息</h4>
					<br />
					<input type="submit" id="submit_now" class="btn btn-success" value="提交表单" />
					<input type="reset" class="btn btn-warning" value="重置表单" />
<?php
				} else {
?>
					<h4>提交信息</h4>
					<br />
					<p><input type="checkbox" name="agreement" onclick="javascript:tick(this);" /> 我同意 <a href="/agreement.html">堕落工作室服务协议</a></p>
					<br />
					<input type="submit" id="submit_now" class="btn btn-success" value="提交表单" disabled />
					<input type="reset" class="btn btn-warning" value="重置表单" />
<?php
				}
			}
?>
				</form>
			</div>
<?php
		}
	}
?>
			<hr />
			<p>© 2014 <a href="http://82flex.com">82Flex</a>. 由 <a href="http://82flex.com/projects">DCRM</a> 强力驱动.</p>
		</div>
		<script src="http://libs.baidu.com/jquery/2.0.0/jquery.min.js" type="text/javascript"></script>
		<script type="text/javascript">
			Date.prototype.Format = function (fmt) {
			    var o = {
			        "M+": this.getMonth() + 1,
			        "d+": this.getDate(),
			        "h+": this.getHours(),
			        "m+": this.getMinutes(), 
			        "s+": this.getSeconds(),
			        "q+": Math.floor((this.getMonth() + 3) / 3),
			        "S": this.getMilliseconds()
			    };
			    if (/(y+)/.test(fmt)) fmt = fmt.replace(RegExp.$1, (this.getFullYear() + "").substr(4 - RegExp.$1.length));
			    for (var k in o)
			    if (new RegExp("(" + k + ")").test(fmt)) fmt = fmt.replace(RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (("00" + o[k]).substr(("" + o[k]).length)));
			    return fmt;
			}
			function tick(obj) {
				if (obj.checked) {
					document.getElementById("submit_now").disabled = "";
				} else {
					document.getElementById("submit_now").disabled = "disabled";
				}
			}
			function confirm_submit() {
				var pass1 = document.getElementById('password');
				var pass2 = document.getElementById('confirmpassword');
				if (pass2) {
					if (pass1.value != pass2.value) {
						alert("您两次填写的密码不一致，请检查输入！");
						return false;
					}
				}
				if (confirm("请确保您填写的信息准确无误，减少不必要的麻烦。\n您确定要提交信息？")) {
					$.ajax({
						url: '?method=set',
						data: $('#mainform').serialize(),
						type: 'POST',
						cache: false,
						beforeSend: function () {
							disable(document.getElementById('mainform'));
						},
						success: function (data) {
							if (data == "ERR01") {
								alert("您输入的密码与创建信息时预留的密码不一致。");
							} else if (data == "ERR02") {
								alert("请求执行失败，请确定您有执行该操作的权限。");
							} else if (data == "SUCCESS") {} else {
								alert("未知错误。");
							}
						},
						error: function () {
							alert("提交表单失败，请检查网络连接。");
						}
					});
					location.reload(false);
					return false;
				} else {
					return false;
				}
			}
			function confirm_reset() {
				if (confirm("您确定要重置表单？")) {
					return true;
				} else {
					return false;
				}
			}
			function disable(o) {
				var d = o;
				for (var i = 0; i < d.childNodes.length; i++) {
					if (d.childNodes[i].disabled != null) {
						d.childNodes[i].disabled = "disabled";
					}
					if (d.childNodes[i].childNodes.length > 0) {
						disable(d.childNodes[i]);
					}
				}
			}
<?php
	if ($manage) {
?>
			function dojump(url) {
				$.get(url, function(data,status) {
					if (status != "success") {
						alert("请求执行失败，请确定您有执行该操作的权限。");
					} else {
						location.reload(false);
					}
				});
			}
			function opt(choice, rid) {
				if (choice == 1) {
					if (confirm("您确定要开始代练？")) {
						dojump("?method=start");
					}
				} else if (choice == 2) {
					if (confirm("您确定要解锁该工单信息？\n解锁后用户将可以修改信息内容。")) {
						dojump("?method=unlock");
					}
				} else if (choice == 3) {
					if (confirm("您确定要锁定该工单信息？\n锁定后用户将无法修改信息内容。")) {
						dojump("?method=lock");
					}
				} else if (choice == 4) {
					if (confirm("您确定要结算该工单？\n结算后该工单将无法被用户查询及修改！")) {
						dojump("?method=finish");
					}
				} else if (choice == 5) {
					if (confirm("您确定要暂停代练？")) {
						dojump("?method=stop");
					}
				} else if (choice == 6) {
					if (confirm("您确定要删除此条记录？")) {
						dojump("?method=drecord&rid="+rid);
					}
				}
			}
			function fill(rowIndex) {
				var node = document.getElementsByTagName("table")[0];
				var body = node.getElementsByTagName("tbody")[0];
				var child = body.getElementsByTagName("tr")[rowIndex];
				var text = child.getElementsByTagName("td")[1].innerHTML;
				if (document.getElementsByName("statment")[0]) {
					document.getElementsByName("statment")[0].value = text;
				}
			}
<?php
	}
?>
			if (document.getElementById('createtime')) {
				if (document.getElementById('createtime').value == '') {
					var str = new Date().Format("yyyy-MM-dd HH:mm:ss");
					document.getElementById('createtime').value = str;
				} else {
					if (document.getElementById('lock')) {
						disable(document.getElementById('mainform'));
					}
				}
			}
		</script>
	</body>
</html>