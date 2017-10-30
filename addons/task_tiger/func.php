<?php
/**根据openID，返回公众号关注列表
 * @param $openID *用户id*
 */
function followList($openid)
{
    $account = pdo_fetch('select * from ' . tablename("mc_mapping_fans") . " where openid = '$openid' ");


    $unionid = $account['unionid'];
    if ($account) {
//用户关注列表
        $accountList = pdo_fetchall('select uniacid from ' . tablename("mc_mapping_fans") . " where unionid = '$unionid' ", array());
//公众号列表
        $accountGroup = pdo_fetchall('select uniacid from ' . tablename("task_tiger_account") . " where `group` = 'admin' ", array());
//判断是否关注
        $followArray = Array();
        foreach ($accountGroup as $value) {
            if (in_array($value, $accountList)) {
                $followArray[$value['uniacid']] = true;
            } else {
                $followArray[$value['uniacid']] = false;
            }
        }
        echo "<pre>";
        var_dump($accountList);
        var_dump($accountGroup);
        var_dump($followArray);
        exit;


    } else {
        return false;
    }
}

 function trimPx($data) {
	$data['left'] = intval(str_replace('px', '', $data['left'])) * 2;
	$data['top'] = intval(str_replace('px', '', $data['top'])) * 2;
	$data['width'] = intval(str_replace('px', '', $data['width'])) * 2;
	$data['height'] = intval(str_replace('px', '', $data['height'])) * 2;
	$data['size'] = intval(str_replace('px', '', $data['size'])) * 2;
	$data['src'] = tomedia($data['src']);
	return $data;
}

 function mergeImage($target, $imgurl , $data) {
	$img = imagecreates($imgurl);
	$w = imagesx($img);
	$h = imagesy($img);
	imagecopyresized($target, $img, $data['left'], $data['top'], 0, 0, $data['width'], $data['height'], $w, $h);
	imagedestroy($img);
	return $target;
}
 function mergeText($m,$target ,$text , $data,$poster) {
	$font = IA_ROOT . "/attachment/font/".$poster['mbfont'];//字体文件
	$colors = hex2rgb($data['color']);
	$color = imagecolorallocate($target, $colors['red'], $colors['green'], $colors['blue']);
	imagettftext($target, $data['size'], 0, $data['left'], $data['top'] + $data['size'], $color, $font, $text);
	return $target;
}

function hex2rgb($colour) {
	if ($colour[0] == '#') {
		$colour = substr($colour, 1);
	}
	if (strlen($colour) == 6) {
		list($r, $g, $b) = array($colour[0] . $colour[1], $colour[2] . $colour[3], $colour[4] . $colour[5]);
	} elseif (strlen($colour) == 3) {
		list($r, $g, $b) = array($colour[0] . $colour[0], $colour[1] . $colour[1], $colour[2] . $colour[2]);
	} else {
		return false;
	}
	$r = hexdec($r);
	$g = hexdec($g);
	$b = hexdec($b);
	return array('red' => $r, 'green' => $g, 'blue' => $b);
}

/**合并图片
 * @param  $bg 背景图
 * @param  $qr 其他图
 * @param  $out 存放路径
 * @param $param 大小参数
 */
function mergeImage1($bgImg, $qr, $out, $param) {
	list($qrWidth, $qrHeight) = getimagesize($qr);
	$qrImg = imagecreates($qr);
	imagecopyresized($bgImg, $qrImg, $param['left'], $param['top'], 0, 0, $param['width'], $param['height'], $qrWidth, $qrHeight);
	ob_start();
	imagejpeg($bgImg, NULL, 100);
	$contents = ob_get_contents();
	ob_end_clean();
	imagedestroy($bgImg);
	imagedestroy($qrImg);
	$fh = fopen($out, "w+");
	fwrite($fh, $contents);
	fclose($fh);
}


/**创建图片
 * @param $bg 图片路径
 * @return
 */
function imagecreates($bg) {
	$bgImg = @imagecreatefromjpeg($bg);
	if (FALSE == $bgImg) {
		$bgImg = @imagecreatefrompng($bg);
	}
	if (FALSE == $bgImg) {
		$bgImg = @imagecreatefromgif($bg);
	}
	return $bgImg;
}

function saveImage($url,$tag) {
	$ch = curl_init ();
	curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, 'GET' );
	curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
	curl_setopt ( $ch, CURLOPT_URL, $url );
	ob_start ();
	curl_exec ( $ch );
	$return_content = ob_get_contents ();
	ob_end_clean ();
	$return_code = curl_getinfo ( $ch, CURLINFO_HTTP_CODE );
	$filename = IA_ROOT."/addons/task_tiger/qrcode/temp{$tag}.jpg";
	$fp= @fopen($filename,"a"); //将文件绑定到流 
	fwrite($fp,$return_content); //写入文件
	return $filename;
}

function downloadImageFromQzone($url)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, 0);    
    curl_setopt($ch, CURLOPT_NOBODY, 0);    //只取body头
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $package = curl_exec($ch);
    $httpinfo = curl_getinfo($ch);
    
    curl_close($ch);
    $imageAll = array_merge(array('imgBody' => $package), $httpinfo); 
    
    $filename = time().".jpg";
    $local_file = fopen($filename, 'w');
    if (false !== $local_file){
        if (false !== fwrite($local_file, $imageAll["imgBody"])) {
            fclose($local_file);
        }
    }
}

//$url = "http://wx.qlogo.cn/mmopen/ajNVdqHZLLD8wULlHMYicmTvbOeBlJGxzQLyZJz2dAf8ov7alKmxEuPEiaQh5I1fEM2TsMJ5paY7b3eBWBichTobQ/0";
//$imageAll = downloadImageFromQzone($url);


