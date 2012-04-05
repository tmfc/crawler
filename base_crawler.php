<?php
require_once dirname(__FILE__)."/url_provider.php";
require_once dirname(__FILE__)."/result_saver.php";
require_once dirname(__FILE__)."/lib/status_reporter.php";

define ( "COOKIE_FILE", dirname(__FILE__) . '/cookie.txt' );

abstract class base_crawler
{
	//站点编码
	public $site_charset = 'UTF-8//IGNORE';
	
	//url提供者、结果保存者
	public $url_provider = null;
	public $result_saver = null;
	
	protected $current_url = null;
	protected $count = 0;
	protected $max_count = 0;
	
	//抓取网页的状态码（200、404等）
	private $http_status_code = 0;
	
	//是否是调试模式
	public $debug_mode = false;
	public $fake_crawl = false;
	public $resume_crawl = true;
	
	//分析网页
	abstract public function parse_content($content);
	//对结果做额外的处理
	abstract public function extra_save_result($result);
	
	//主方法，进行抓取
	public function do_crawl()
	{
		try 
		{
			ini_set('memory_limit', '1000M'); //内存限制
			set_time_limit(0); //
			
			status_reporter::init(get_class($this));
			
			$last_url = '';
			//从数据库取上一次抓取保存的URL
			if($this->resume_crawl == true && key_exists('url_provider', status_reporter::get_last_report()->extra_status))
				$last_url = status_reporter::get_last_report()->extra_status['url_provider'];
			
			//没有url提供者，结果保存者，退出
			if($this->url_provider == null || $this->result_saver == null)
			{
				$this->debug_info("no url_provider or no result_saver");
				return;
			}
			$this->debug_info("crawler start");
			status_reporter::start();
			while(true)
			{
				$this->count++;
				if($this->max_count>0 && $this->count > $this->max_count){
					status_reporter::update('url_provider','');
					status_reporter::end();
					return ; 
				}
				
				//从url提供者处获取下一个url
				$url = $this->url_provider->get_next_url();
				
				if ($url === false || empty($url))
				{
					$this->debug_info("\r\nTotal: ".$this->count."\r\n");
					$this->debug_info("crawler end:no more url to crawl");	
					status_reporter::update('url_provider','');
					status_reporter::end();
					return;
				}
				else{
					if($last_url != "" && $url != $last_url){
						$this->debug_info($url,'url');
						
						continue;
					} else {
						$last_url = "";
					}
				}
				
				status_reporter::update('url_provider',$url);
				$this->current_url = $url;
				
				//假抓取
				if($this->fake_crawl)
				{
					$this->debug_info($url,'url');
					status_reporter::update('url_provider',$url);
					status_reporter::update('url_count',"++");
					status_reporter::report();
					continue;
				}
				
				//抓取页面内容
				$content = $this->get_url_content($url,$this->site_charset);

				//记录debug信息
				$this->debug_info($url . ' ' .$this->http_status_code,'url');
				$this->debug_info($content,'last_content',false,false);
				status_reporter::update("memory",round((memory_get_usage(true)/1024/1024),2));
				
				//通知url_provider当前url的抓取状态和抓取内容，供url_provider进行判断
				$this->url_provider->result_notify($this->http_status_code,$content);
	
				if ($content === false)
				{
					$this->debug_info("crawler continue:get_url_content return false,url:$url,status:{$this->http_status_code}");
					status_reporter::update('content_false_count',"++");
					continue;
				}
				
				//分析页面内容
				$result = $this->parse_content($content);
				if($result === false)
				{
					$this->debug_info("crawler end:parse_content return false,url:$url,content:$content");
					status_reporter::update("error","parse_content return false,url:$url,content:$content");
					status_reporter::end(3);
					return;
				}
				//保存结果
				$ret = $this->result_saver->save_result($result);
				
				//额外保存结果
				$this->extra_save_result($result);
				
				if($ret === false)
				{
					$this->debug_info("crawler end:save_result return false,url:$url,result:" . var_dump($result,true));
					status_reporter::update('save_result_false_count',"++");
					continue;
				}
				
				status_reporter::report();
			}
		}
		catch (Exception $e)
		{
			$this->debug_info($e->getMessage(),'exception');
			$this->debug_info($e->getTraceAsString(),'exception');
			//SendEmailToAdmin("爬虫" . get_class($this) . "出现异常", $e->getMessage() . $e->getTraceAsString());
			status_reporter::end(4);
		}
	}
	
	//调试信息
	protected function debug_info($str,$type = 'trace',$echo = true,$append=true)
	{
		if($this->debug_mode == true)
		{
			$info = date('c',time()) . ' ' . $str;
			if($echo)
				echo $info . PHP_EOL;
			file_put_contents(get_class($this) . "_$type.dbg", $info. PHP_EOL,$append?FILE_APPEND:null);
		}
	}
	
	protected function req_url($url = "", $post_data = NULL) {
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
		$this->http_status_code = $code;
		curl_close ( $res );
		if ($code >= 200 && $code < 400) {
			return $tt;
		} else {
			@file_put_contents ( "log_error_url", "\r\n\r\n***************************\r\n\r\n$code.[" . date ( 'l' ) . "]=>$url   \r\n$tt", FILE_APPEND );
			return false;
		}
	}
	
	protected function get_url_content($url, $s_charact = "gbk", $d_charact = "UTF-8//IGNORE") {
		//echo $s_charact;
		$urlContent = $this->req_url ( $url );
		
		status_reporter::update('url',$url);
		status_reporter::update('url_count',"++");
		status_reporter::update("http_status_{$this->http_status_code}_count","++");
		
		if (! $urlContent) {
			return FALSE;
		}
	
		if ($s_charact == $d_charact) {
			return $urlContent;
		}
		$content = iconv ( $s_charact, $d_charact, $urlContent );
		return $content;
	}
	
	public function get_brand_id($brand_name = "", $brand_url = "", $brand_en = "", $add_new = false) {
		$brand_name = trim ( $brand_name );
		$brand_en = trim ( $brand_en );
		if (empty ( $brand_name ) && empty( $brand_en )) {
			//$brand_name = $brand_en;
			return 0;
		}
		$brand = Brand::get_brand_by_name ( $brand_name );
		if ($brand) {
			return $brand [0]->id;
		} else {
			if (empty ( $brand_en )) {
				$brand_en = $brand_name;
			}
			$brand = Brand::get_brand_by_name ( $brand_en );
			if ($brand) {
				return $brand [0]->id;
			}
			if ($add_new) {
				$brand = new Brand ();
				$brand->name = $brand_name;
				$brand->name_en = $brand_en;
				$brand->brand_desc = $brand_name;
				$brand->mapping_keywords = $brand_name;
				$brand->site_url = $brand_url;
				$brand_id = Brand::add_brand ( $brand );
				return $brand_id;
			}
			return 0;
		}
	}
	
	public function save_brand_category_ref($brand_id, $category_id) {
		$brand_category_ref_id = Brand_category_ref::get_brand_category_ref ( $brand_id, $category_id );
		if (! $brand_category_ref_id) {
			$brand_category_ref = new Brand_category_ref ();
			$brand_category_ref->brand_id = $brand_id;
			$brand_category_ref->category_id = $category_id;
			$brand_category_ref->goods_num = 0;
			Brand_category_ref::add_brand_category_ref ( $brand_category_ref );
		}
	}
	
}


?>