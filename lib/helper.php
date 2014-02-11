<?php
define ( "COOKIE_FILE", dirname(__FILE__) . '/cookie.txt' );

class helper
{
	static public $http_status_code;
	static public $debug_file_name = "application";
	
	public static function get_preg_matchs($str, $preg) {
		preg_match_all ( $preg, $str, $match_array, PREG_SET_ORDER );
		return $match_array;
	}
	
	public static function get_preg_match($str, $preg) {
		preg_match ( $preg, $str, $match );
		return $match;
	}
	
	public static function get_preg_match_group($str, $preg,$group_name) {
		preg_match ( $preg, $str, $match );
		if(key_exists($group_name, $match))
			return $match[$group_name];
		else
			return FALSE;
	}
	
	public static function req_url($url = "", $post_data = NULL) {
		$cookie_jar = COOKIE_FILE;
		$res = curl_init ();
		curl_setopt ( $res, CURLOPT_URL, $url );
		curl_setopt ( $res, CURLOPT_HEADER, 0 );
		curl_setopt ( $res, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $res, CURLOPT_TIMEOUT, 20);
		curl_setopt ( $res, CURLOPT_ENCODING, "identity" );
		curl_setopt ( $res, CURLOPT_COOKIEFILE, $cookie_jar );
		curl_setopt ( $res, CURLOPT_COOKIEJAR, $cookie_jar );
	
		//User-Agent	Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.2.13) Gecko/20101203 Firefox/3.6.13
		curl_setopt ( $res, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; rv:8.0.1) Gecko/20100101 Firefox/8.0.1' );
		//curl_setopt ( $res, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.2.13) Gecko/20101203 Firefox/3.6.13;)' );
		if ($post_data != NULL && ! empty ( $post_data )) {
			//post
			curl_setopt ( $res, CURLOPT_POST, 1 );
			curl_setopt ( $res, CURLOPT_POSTFIELDS, $post_data );
		}
		$tt = curl_exec ( $res );
		$code = curl_getinfo ( $res, CURLINFO_HTTP_CODE );
		//缓存http状态
		self::$http_status_code = $code;
		curl_close ( $res );
		if ($code >= 200 && $code < 400) {
			return $tt;
		} else {
			@file_put_contents ( "log_error_url", "\r\n\r\n***************************\r\n\r\n$code.[" . date ( 'l' ) . "]=>$url   \r\n$tt", FILE_APPEND );
			return false;
		}
	}
	
	public static function get_url_content($url, $s_charact = "gbk", $d_charact = "UTF-8//IGNORE") {
		//echo $s_charact;
		$urlContent = self::req_url ( $url );
	
		status_reporter::update('url',$url);
		status_reporter::update('url_count',"++");
		status_reporter::update("http_status_" . self::$http_status_code . "_count","++");
	
		if (! $urlContent) {
			return FALSE;
		}
	
		if ($s_charact == $d_charact) {
			return $urlContent;
		}
		$content = iconv ( $s_charact, $d_charact, $urlContent );
		return $content;
	}
	
	public static function post_url_content($url, $post_data, $s_charact = "gbk", $d_charact = "UTF-8//IGNORE") {
		//echo $s_charact;
		$urlContent = self::req_url ( $url, $post_data );
	
		status_reporter::update('url',$url);
		status_reporter::update('post_data',$post_data);
		status_reporter::update('url_count',"++");
		status_reporter::update("http_status_" . self::$http_status_code . "_count","++");
	
		if (! $urlContent) {
			return FALSE;
		}
	
		if ($s_charact == $d_charact) {
			return $urlContent;
		}
		$content = iconv ( $s_charact, $d_charact, $urlContent );
		return $content;
	}
	
	public static function download_image($img_url,$dest_path)
	{
		$img_data = self::req_url ( $img_url );
		$local_file = fopen($dest_path , 'w');
		fwrite($local_file, $img_data);
		fclose($local_file);
	}
	
	public static function createthumb($name,$filename,$new_w,$new_h)
	{
		$system=explode(".",$name);
		if (preg_match("/jpg|jpeg/",$system[1])){
			$src_img=imagecreatefromjpeg($name);
		}
		if (preg_match("/png/",$system[1])){
			$src_img=imagecreatefrompng($name);
		}
		$old_x=imageSX($src_img);
		$old_y=imageSY($src_img);
		if ($old_x > $old_y)
		{
			$thumb_w=$new_w;
			$thumb_h=$old_y*($new_h/$old_x);
		}
		if ($old_x < $old_y)
		{
			$thumb_w=$old_x*($new_w/$old_y);
			$thumb_h=$new_h;
		}
		if ($old_x == $old_y)
		{
			$thumb_w=$new_w;
			$thumb_h=$new_h;
		}
		$dst_img=ImageCreateTrueColor($thumb_w,$thumb_h);
		imagecopyresampled($dst_img,$src_img,0,0,0,0,$thumb_w,$thumb_h,$old_x,$old_y);
		if (preg_match("/png/",$system[1]))
		{
			imagepng($dst_img,$filename);
		} else {
			imagejpeg($dst_img,$filename);
		}
		imagedestroy($dst_img);
		imagedestroy($src_img);
	}
	
	//调试信息
    function debug_info($str,$type = 'trace',$echo = true,$append=true)
	{
		$info = date('c',time()) . ' ' . $str;
		if($echo)
			echo $info . PHP_EOL;
		file_put_contents(self::$debug_file_name . "_$type.log", $info. PHP_EOL,$append?FILE_APPEND:null);
	}
}