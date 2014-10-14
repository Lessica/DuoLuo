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
	
	function httpinfo($info_type) {
		switch ($info_type) {
			case 304:
				$info = "304 Not Modified";
			break;
			case 400:
				$info = "400 Bad Request";
			break;
			case 404:
				$info = "404 Not Found";
			break;
			case 403:
				$info = "403 Forbidden";
			break;
			case 405:
				$info = "405 Method Not Allowed";
			break; 
			case 500:
				$info = "500 Internal Server Error";
			break;
			default:
				$info = "501 Not Implemented";
			break;
		}
		header("HTTP/1.1 ".$info);
		header("Status: ".$info);
		header("Content-Type: text/html; charset=UTF-8");
?>
<html>
<head>
	<title><?php echo($info); ?></title>
</head>
<body bgcolor="white">
	<center>
		<h1><?php echo($info); ?></h1>
	</center>
<hr />
	<center>nginx</center>
</body>
</html>
<?php
		exit();
	}
	
	class ValidateCode {
		private $charset = 'abcdefghijklmnopqrstuvwxvzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	    private $code;
	    private $codelen = 4;
	    private $width = 176;
	    private $height = 72;
	    private $img;
	    private $font;
	    private $fontsize = 36;
	    private $fontcolor;
	
	    //构造方法初始化
	    public function __construct() {
	        $this->font = ROOT_PATH.'/css/default.ttf';
	    }
	
	    //生成随机码
	    private function createCode() {
	        $_len = strlen($this->charset)-1;
	        for ($i=0;$i<$this->codelen;$i++) {
	            $this->code .= $this->charset[mt_rand(0,$_len)];
	        }
	    }
	
	    //生成背景
	    private function createBg() {
	        $this->img = imagecreatetruecolor($this->width, $this->height);
	        $color = imagecolorallocate($this->img, mt_rand(157,255), mt_rand(157,255), mt_rand(157,255));
	        imagefilledrectangle($this->img,0,$this->height,$this->width,0,$color);
	    }
	
	    //生成文字
	    private function createFont() {    
	        $_x = $this->width / $this->codelen;
	        for ($i=0;$i<$this->codelen;$i++) {
	            $this->fontcolor = imagecolorallocate($this->img,mt_rand(0,156),mt_rand(0,156),mt_rand(0,156));
	            imagettftext($this->img,$this->fontsize,mt_rand(-30,30),$_x*$i+10+mt_rand(1,5),$this->height / 1.4,$this->fontcolor,$this->font,$this->code[$i]);
	        }
	    }
	
	    //生成线条、雪花
	    private function createLine() {
	        for ($i=0;$i<6;$i++) {
	            $color = imagecolorallocate($this->img,mt_rand(0,156),mt_rand(0,156),mt_rand(0,156));
	            imageline($this->img,mt_rand(0,$this->width),mt_rand(0,$this->height),mt_rand(0,$this->width),mt_rand(0,$this->height),$color);
	        }
	        for ($i=0;$i<100;$i++) {
	            $color = imagecolorallocate($this->img,mt_rand(200,255),mt_rand(200,255),mt_rand(200,255));
	            imagestring($this->img,mt_rand(1,5),mt_rand(0,$this->width),mt_rand(0,$this->height),'*',$color);
	        }
	    }
	
	    //输出
	    private function outPut() {
	        header('Content-Type: image/png');
	        imagepng($this->img);
	        imagedestroy($this->img);
	    }
	
	    //对外生成
	    public function doimg() {
	        $this->createBg();
	        $this->createCode();
	        $this->createLine();
	        $this->createFont();
	        $this->outPut();
	    }
	
	    //获取验证码
	    public function getCode() {
	        return strtolower($this->code);
	    }
	}
	/**
	* @param string $string 原文或者密文
	* @param string $operation 操作(ENCODE | DECODE), 默认为 DECODE
	* @param string $key 密钥
	* @param int $expiry 密文有效期, 加密时候有效， 单位 秒，0 为永久有效
	* @return string 处理后的 原文或者 经过 base64_encode 处理后的密文
	*
	* @example
	*
	*  $a = authcode('abc', 'ENCODE', 'key');
	*  $b = authcode($a, 'DECODE', 'key');  // $b(abc)
	*
	*  $a = authcode('abc', 'ENCODE', 'key', 3600);
	*  $b = authcode('abc', 'DECODE', 'key'); // 在一个小时内，$b(abc)，否则 $b 为空
	*/
	
	function authcode($string, $operation = 'DECODE', $key = '', $expiry = 3600) {
		$ckey_length = 4;
		// 随机密钥长度 取值 0-32;
		// 加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度。
		// 取值越大，密文变动规律越大，密文变化 = 16 的 $ckey_length 次方
		// 当此值为 0 时，则不产生随机密钥
		
		//$key = md5($key ? $key : EABAX::getAppInf('KEY'));
		$keya = md5(substr($key, 0, 16));
		$keyb = md5(substr($key, 16, 16));
		$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
		
		$cryptkey = $keya.md5($keya.$keyc);
		$key_length = strlen($cryptkey);
		
		$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
		$string_length = strlen($string);
		
		$result = '';
		$box = range(0, 255);
		
		$rndkey = array();
		for($i = 0; $i <= 255; $i++) {
			$rndkey[$i] = ord($cryptkey[$i % $key_length]);
		}
		
		for($j = $i = 0; $i < 256; $i++) {
			$j = ($j + $box[$i] + $rndkey[$i]) % 256;
			$tmp = $box[$i];
			$box[$i] = $box[$j];
			$box[$j] = $tmp;
		}
		
		for($a = $j = $i = 0; $i < $string_length; $i++) {
			$a = ($a + 1) % 256;
			$j = ($j + $box[$a]) % 256;
			$tmp = $box[$a];
			$box[$a] = $box[$j];
			$box[$j] = $tmp;
			$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
		}
		
		if($operation == 'DECODE') {
			if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
				return substr($result, 26);
			} else {
				return '';
			}
		} else {
			return $keyc.str_replace('=', '', base64_encode($result));
		}
	}
	
	function starhide($istr) {
		$length = strlen($istr);
		$middle = ceil($length / 2) - 1;
		$ostr = '';
		for ($i = 0; $i < $middle - 2; $i++) {
			$ostr .= $istr[$i];
		}
		for ($j = 0; $j < 4; $j++) {
			$ostr .= '*';
		}
		for ($k = $middle + 2; $k < $length; $k++) {
			$ostr .= $istr[$k];
		}
		return $ostr;
	}
?>