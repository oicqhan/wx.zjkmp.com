<?php
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

$url = "http://wx.qlogo.cn/mmopen/ajNVdqHZLLD8wULlHMYicmTvbOeBlJGxzQLyZJz2dAf8ov7alKmxEuPEiaQh5I1fEM2TsMJ5paY7b3eBWBichTobQ/0";
$imageAll = downloadImageFromQzone($url);
echo $imageAll;


?>
