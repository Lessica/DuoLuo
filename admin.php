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
	require_once('include/corepage.php');
	require_once('include/config.inc.php');
	
	header('Content-Type: text/html; charset=UTF-8');
	header("Cache-Control: max-age=0");
	
	if (!isset($_SESSION['connected']) || $_SESSION['connected'] != true) {
		header('Location: /?method=logout');
		exit();
	}
	if (!isset($_SESSION['uid'])) {
		httpinfo(403);
	}
	
	$connection = mysql_connect(DB_HOST, DB_USER, DB_PASS);
	$connection_names = mysql_query('SET NAMES utf8');
	$connection_select = mysql_select_db(DB_NAME);
	if (!$connection || !$connection_names || !$connection_select) {
		httpinfo(500);
	}
	
	if (isset($_GET['method']) && $_GET['method'] == 'dticket') {
		if (isset($_POST['dstrarr'])) {
			$darray = explode(",", $_POST['dstrarr']);
			$success = 0;
			$fail = 0;
			foreach ($darray as $value) {
				$tid = mysql_real_escape_string($value);
				if (!empty($value) && ctype_digit($value)) {
					$result = mysql_query("DELETE FROM `tickets` WHERE `tid` = '".$tid."'");
					$result = mysql_query("DELETE FROM `works` WHERE `tid` = '".$tid."'");
					if (!$result) {
						$fail++;
					} else {
						$success++;
					}
				}
			}
		} else {
			httpinfo(400);
		}
		exit();
	} elseif (isset($_GET['method']) && $_GET['method'] == 'dlock') {
		if (isset($_POST['dstrarr'])) {
			$darray = explode(",", $_POST['dstrarr']);
			$success = 0;
			$fail = 0;
			foreach ($darray as $value) {
				$tid = mysql_real_escape_string($value);
				if (!empty($value) && ctype_digit($value)) {
					$test_query = mysql_query("SELECT `stat` FROM `tickets` WHERE `tid` = '".$tid."' LIMIT 1");
					if (!$test_query) {
						$fail++;
					} else {
						$test_assoc = mysql_fetch_assoc($test_query);
						if (!$test_assoc) {
							$fail++;
						} else {
							if ($test_assoc['stat'] != 2) {
								$result = mysql_query("UPDATE `tickets` SET `stat` = '2' WHERE `tid` = '".$tid."'");
								mysql_query("INSERT INTO `works`(`tid`,`type`,`statments`) VALUES('".$tid."','1','管理员 ".mysql_real_escape_string($_SESSION['uid'])." 锁定了此工单。')");
							} elseif ($test_assoc['stat'] != -1) {
								$result = mysql_query("UPDATE `tickets` SET `stat` = '1' WHERE `tid` = '".$tid."'");
								mysql_query("INSERT INTO `works`(`tid`,`type`,`statments`) VALUES('".$tid."','1','管理员 ".mysql_real_escape_string($_SESSION['uid'])." 解锁了此工单。')");
							}
							if (!$result) {
								$fail++;
							} else {
								$success++;
							}
						}
					}
				}
			}
		} else {
			httpinfo(400);
		}
		exit();
	} elseif (isset($_GET['method']) && $_GET['method'] == 'dfinish') {
		if (isset($_POST['dstrarr'])) {
			$darray = explode(",", $_POST['dstrarr']);
			$success = 0;
			$fail = 0;
			foreach ($darray as $value) {
				$tid = mysql_real_escape_string($value);
				if (!empty($value) && ctype_digit($value)) {
					$test_query = mysql_query("SELECT `stat` FROM `tickets` WHERE `tid` = '".$tid."' LIMIT 1");
					if (!$test_query) {
						$fail++;
					} else {
						$test_assoc = mysql_fetch_assoc($test_query);
						if (!$test_assoc) {
							$fail++;
						} else {
							if ($test_assoc['stat'] != -1) {
								$result = mysql_query("UPDATE `tickets` SET `stat` = '-1' WHERE `tid` = '".$tid."'");
								mysql_query("UPDATE `tickets` SET `password` = '' WHERE `tid` = '".$tid."'");
								mysql_query("INSERT INTO `works`(`tid`,`type`,`statments`) VALUES('".$tid."','1','管理员 ".mysql_real_escape_string($_SESSION['uid'])." 结算了此工单。')");
							}
							if (!$result) {
								$fail++;
							} else {
								$success++;
							}
						}
					}
				}
			}
		} else {
			httpinfo(400);
		}
		exit();
	} elseif (isset($_GET['method']) && $_GET['method'] == 'ref') {
		if (isset($_GET['page'])) {
			if (ctype_digit($_GET['page'])) {
				$page = intval($_GET['page']);
			} else {
				$page = 1;
			}
		} else {
			$page = 1;
		}
		$page_a = $page * 15 - 15;
		if (isset($_GET['order']) && $_GET['order'] == '-3') {
			$q_info = mysql_query("SELECT count(*) FROM `tickets`");
			$ptype = -3;
		} elseif (isset($_GET['order']) && $_GET['order'] == '0') {
			$q_info = mysql_query("SELECT count(*) FROM `tickets` WHERE `stat` = '0'");
			$ptype = 0;
		} elseif (isset($_GET['order']) && $_GET['order'] == '1') {
			$q_info = mysql_query("SELECT count(*) FROM `tickets` WHERE `stat` = '1'");
			$ptype = 1;
		} elseif (isset($_GET['order']) && $_GET['order'] == '2') {
			$q_info = mysql_query("SELECT count(*) FROM `tickets` WHERE `stat` = '2'");
			$ptype = 2;
		} elseif (isset($_GET['order']) && $_GET['order'] == '3') {
			$q_info = mysql_query("SELECT count(*) FROM `tickets` WHERE `stat` = '3'");
			$ptype = 3;
		} elseif (isset($_GET['order']) && $_GET['order'] == '4') {
			$q_info = mysql_query("SELECT count(*) FROM `tickets` WHERE `stat` = '4'");
			$ptype = 4;
		} elseif (isset($_GET['order']) && $_GET['order'] == '-1') {
			$q_info = mysql_query("SELECT count(*) FROM `tickets` WHERE `stat` = '-1'");
			$ptype = -1;
		} elseif (isset($_GET['order']) && $_GET['order'] == '-2') {
			$q_info = mysql_query("SELECT count(*) FROM `tickets` LIMIT 50");
			$ptype = -2;
		}
		$info = mysql_fetch_row($q_info);
		$totalnum = (int)$info[0];
		if ($totalnum != 0) {
?>
					<div id="shape" style="min-height: 560px;">
						<table class="table" style="word-wrap: keep-all; word-break: keep-all; ">
							<thead>
								<tr>
									<th>工单号</th>
									<th>通行证</th>
									<th>服务器</th>
									<th>角色名</th>
									<th>创建时间</th>
									<th>状态</th>
									<th>选择</th>
								</tr>
							</thead>
							<tbody>
<?php
			if (isset($_GET['order']) && ($_GET['order'] == '-2' || $_GET['order'] == '-3')) {
				$list_query = mysql_query("SELECT * FROM `tickets` ORDER BY `ID` DESC LIMIT ".(string)$page_a.", 15");
			} elseif (isset($_GET['order']) && $_GET['order'] == '0') {
				$list_query = mysql_query("SELECT * FROM `tickets` WHERE `stat` = '0' ORDER BY `ID` DESC LIMIT ".(string)$page_a.", 15");
			} elseif (isset($_GET['order']) && $_GET['order'] == '1') {
				$list_query = mysql_query("SELECT * FROM `tickets` WHERE `stat` = '1' ORDER BY `ID` DESC LIMIT ".(string)$page_a.", 15");
			} elseif (isset($_GET['order']) && $_GET['order'] == '2') {
				$list_query = mysql_query("SELECT * FROM `tickets` WHERE `stat` = '2' ORDER BY `ID` DESC LIMIT ".(string)$page_a.", 15");
			} elseif (isset($_GET['order']) && $_GET['order'] == '3') {
				$list_query = mysql_query("SELECT * FROM `tickets` WHERE `stat` = '3' ORDER BY `ID` DESC LIMIT ".(string)$page_a.", 15");
			} elseif (isset($_GET['order']) && $_GET['order'] == '4') {
				$list_query = mysql_query("SELECT * FROM `tickets` WHERE `stat` = '4' ORDER BY `ID` DESC LIMIT ".(string)$page_a.", 15");
			} elseif (isset($_GET['order']) && $_GET['order'] == '-1') {
				$list_query = mysql_query("SELECT * FROM `tickets` WHERE `stat` = '-1' ORDER BY `ID` DESC LIMIT ".(string)$page_a.", 15");
			}
			if (!$list_query) {
				httpinfo(500);
			}
			while ($ticket_info = mysql_fetch_assoc($list_query)) {
				switch ($ticket_info['section']) {
					case '1':
						$sername = '一区';
						break;
					case '2':
						$sername = '三区';
						break;
					case '3':
						$sername = '五区';
						break;
					case '4':
						$sername = '十区';
						break;
					default:
						$sername = '';
						break;
				}
				switch ($ticket_info['stat']) {
					case -1:
						$statname = '已结算';
						break;
					case 0:
						$statname = '创建中';
						break;
					case 1:
						$statname = '修改中';
						break;
					case 2:
						$statname = '已锁定';
						break;
					case 3:
						$statname = '代练中';
						break;
					case 4:
						$statname = '暂停中';
						break;
					default:
						$statname = '';
						break;
				}
?>
								<tr id="t<?php echo(htmlspecialchars($ticket_info['tid'])); ?>">
									<td><nobr><?php echo(htmlspecialchars($ticket_info['tid'])); ?></nobr></td>
									<td><nobr><?php echo(htmlspecialchars($ticket_info['card']." - ".$ticket_info['subaccount'])); ?></nobr></td>
									<td><nobr><?php echo(htmlspecialchars($sername." - ".$ticket_info['server'])); ?></nobr></td>
									<td><nobr><?php echo(htmlspecialchars($ticket_info['charactername'])); ?></nobr></td>
									<td><nobr><?php echo(htmlspecialchars($ticket_info['createtime'])); ?></nobr></td>
									<td><nobr><a href="<?php echo("jump.php?stok=".authcode($ticket_info['tid'], 'ENCODE', $_SERVER['REMOTE_ADDR'], 3600)); ?>" target="_blank"><?php echo(htmlspecialchars($statname)); ?></a></nobr></td>
									<td><input type="checkbox" name="cticket" value="<?php echo(htmlspecialchars($ticket_info['tid'])); ?>" onclick="javascript:tick();" /></td>
								</tr>
<?php
			}
?>
							</tbody>
						</table>
					</div>
<?php
			$params = array('total_rows'=>$totalnum, 'method'=>'html', 'parameter' =>'javascript:ref('.$ptype.',%page,1);', 'now_page'  =>$page, 'list_rows' =>15);
			$page = new Core_Lib_Page($params);
?>
					<ul class="pagination"><?php echo($page->show(2)); ?></ul>
<?php
		} else {
?>
					<div id="shape" style="min-height: 560px;">
						<p>查询不到指定类型的工单</p>
					</div>
<?php
		}
		exit();
	}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>堕落工作室 - 管理面板</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<meta name="robots" content="index, deny" />
		<base target="_top">
		<link rel="shortcut icon" href="favicon.ico">
		<link rel="stylesheet" href="http://libs.baidu.com/bootstrap/3.0.3/css/bootstrap.min.css">
		<link rel="stylesheet" href="/css/misc.min.css" media="screen">
		<script src="http://libs.baidu.com/jquery/2.0.0/jquery.min.js" type="text/javascript"></script>
		<script src="http://libs.baidu.com/bootstrap/3.0.3/js/bootstrap.min.js" type="text/javascript"></script>
	</head>
	<body>
		<div class="well" style="width: auto;">
			<p><a href="/"><img src="CydiaIcon.png" style="width: 72px; height: 72px; border-radius: 6px;" /></a></p>
			<p>堕落工作室 - 管理</p>
			<hr />
			<p>尊敬的管理员：<code><?php echo(htmlspecialchars($_SESSION['uid'])); ?></code> <a href="/?method=logout">退出登录</a></p>
<?php
	if (isset($_GET['method']) && $_GET['method'] == 'create') {
		$q_info = mysql_query("SELECT count(*) FROM `tickets` WHERE (`stat` = '0' OR `stat` = '1' OR `stat` = '2' OR `stat` = '3' OR `stat` = '4')");
		$active_info = mysql_fetch_row($q_info);
		$active_num = (int)$active_info[0];
?>
			<p>当前共有 <code><?php echo($active_num); ?></code> 条活跃工单。</p>
			<hr />
			<h4>创建或定位工单</h4>
			<br />
			<form method="post" action="?method=submit" onsubmit="return check_submit();">
				<p><input type="text" name="newtid" pattern="[0-9]{15}" placeholder="淘宝订单号或工单号" required="required" maxlength="15" style="width: 240px; height: 36px;" /></p>
				<p><input class="btn btn-success" type="submit" value="提交" /></p>
			</form>
<?php
	} elseif (isset($_GET['method']) && $_GET['method'] == 'submit') {
		if (isset($_POST['newtid']) && ctype_digit($_POST['newtid'])) {
			header('Location: jump.php?stok='.authcode($_POST['newtid'], 'ENCODE', $_SERVER['REMOTE_ADDR'], 3600));
			exit();
		} else {
			httpinfo(400);
		}
	} else {
		$q_info = mysql_query("SELECT count(*) FROM `tickets` WHERE (`stat` = '0' OR `stat` = '1' OR `stat` = '2' OR `stat` = '3' OR `stat` = '4')");
		$active_info = mysql_fetch_row($q_info);
		$active_num = (int)$active_info[0];
?>
			<p>当前共有 <code><?php echo($active_num); ?></code> 条活跃工单。</p>
			<hr />
			<h4>管理操作</h4>
			<br />
			<div id="operations">
				<a class="btn btn-success" href="?method=create" target="_blank">创建或定位</a>
				<button class="btn btn-warning" onclick="javascript:dopt(2);" disabled>锁定或解锁</button>
				<button class="btn btn-danger" onclick="javascript:dopt(-1);" disabled>结算</button>
				<button class="btn" onclick="javascript:dopt(1);" disabled>删除</button>
			</div>
			<hr />
			<h4>工单列表</h4>
			<br />
			<ul id="myTab" class="nav nav-tabs">
				<li class="active">
					<a href="#drop-2" data-toggle="tab" onclick="javascript:ref(-2);">最近</a>
				</li>
				<li class="dropdown">
					<a href="#" id="myTabDrop1" class="dropdown-toggle" data-toggle="dropdown">
						未完成<b class="caret"></b>
					</a>
					<ul class="dropdown-menu" role="menu" aria-labelledby="myTabDrop1">
						<li><a href="#drop0" tabindex="0" data-toggle="tab" onclick="javascript:ref(0,1);">创建中</a></li>
						<li><a href="#drop1" tabindex="1" data-toggle="tab" onclick="javascript:ref(1,1);">修改中</a></li>
						<li><a href="#drop2" tabindex="2" data-toggle="tab" onclick="javascript:ref(2,1);">已锁定</a></li>
						<li><a href="#drop3" tabindex="3" data-toggle="tab" onclick="javascript:ref(3,1);">代练中</a></li>
						<li><a href="#drop4" tabindex="4" data-toggle="tab" onclick="javascript:ref(4,1);">暂停中</a></li>
					</ul>
				</li>
				<li>
					<a href="#drop-1" data-toggle="tab" onclick="javascript:ref(-1,1);">已结算</a>
				</li>
				<li>
					<a href="#drop-3" data-toggle="tab" onclick="javascript:ref(-3,1);">全部</a>
				</li>
			</ul>
			<br />
			<div id="myTabContent" class="tab-content">
				<div class="tab-pane fade in active" id="drop-2"></div>
				<div class="tab-pane fade" id="drop0"></div>
				<div class="tab-pane fade" id="drop1"></div>
				<div class="tab-pane fade" id="drop2"></div>
				<div class="tab-pane fade" id="drop3"></div>
				<div class="tab-pane fade" id="drop4"></div>
				<div class="tab-pane fade" id="drop-1"></div>
				<div class="tab-pane fade" id="drop-3"></div>
			</div>
<?php
	}
?>
			<hr />
			<p>© 2014 <a href="http://82flex.com">82Flex</a>. 由 <a href="http://82flex.com/projects">DCRM</a> 强力驱动.</p>
		</div>
		<script type="text/javascript">
			var ctype = 0;
			var cpage = 0;
			$("div.tab-pane").css("min-height", "652px");
			function tick(){
				var ctickets = document.getElementsByName("cticket");
				var cnum = ctickets.length;
				var exist = false;
				for (var i = 0; i <= cnum - 1; i++) {
					if (ctickets[i].checked) {
						exist = true;
						break;
					}
				}
				if (exist) {
					$("button").removeAttr("disabled");
				} else {
					$("button").attr("disabled","disabled");
				}
			}
			function dopt(choice) {
				var ctickets = document.getElementsByName("cticket");
				var cnum = ctickets.length;
				var dstr = "";
				var exist = false;
				for (var i = 0; i <= cnum - 1; i++) {
					if (ctickets[i].checked) {
						dstr += ctickets[i].value + ",";
						exist = true;
					}
				}
				if (exist) {
					var method = "";
					if (choice == 1) {
						if (confirm("您确定要删除所选工单？\n删除后用户和后台将无法查询到。\n该操作不可逆。")) {
							method = "dticket";
						}
					} else if (choice == 2) {
						if (confirm("您确定要锁定或解锁所选工单？")) {
							method = "dlock";
						}
					} else if (choice == -1) {
						if (confirm("您确定要结算所选工单？")) {
							method = "dfinish";
						}
					}
					if (method != "") {
						$.post("?method="+method, {
							"dstrarr" : dstr
						},
						function(data,status) {
							if (status == "success") {
								ref(ctype,cpage);
							} else {
								alert("请求发送失败，请检查网络连接。");
							}
						});
					}
				} else {
					alert("请选择至少一个项目！");
				}
			}
			function ref(type,page,fade) {
				if (fade == 1) {
					$("#drop" + type).fadeTo("fast",0.3);
				}
				$.get("?method=ref&order=" + type + "&page=" + page,
				function(data,status) {
					if (status == "success") {
						var target = document.getElementById("drop" + type);
						target.innerHTML = data;
						ctype = type;
						cpage = page;
						if (fade == 1) {
							$("#drop" + type).fadeTo("fast",1);
							tick();
						}
					}
				});
			}
			function check_submit() {
				var tobj = document.getElementsByName("newtid")[0].value.trim();
				var reg = /^\d+$/;
				if (tobj.length != 15 || tobj.match(reg) == null) {
					alert("淘宝订单号或工单号必须为 15 位数字！");
					return false;
				}
				return true;
			}
			ref(-2);
		</script>
	</body>
</html>