<?php
abstract class base_url_provider
{
	abstract public function get_next_url();

	public function result_notify($status_code,$content)
	{
		return ;
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
	
	protected function get_preg_matchs($str, $preg) {
		preg_match_all ( $preg, $str, $match_array, PREG_SET_ORDER );
		return $match_array;
	}
	
	protected function get_preg_match($str, $preg) {
		preg_match ( $preg, $str, $match );
		return $match;
	}
	
	protected function get_preg_match_group($str, $preg,$group_name) {
		preg_match ( $preg, $str, $match );
		if(key_exists($group_name, $match))
			return $match[$group_name];
		else
			return FALSE;
	}
}

class sitemap_url_provider extends base_url_provider
{
	public $init_url;
	private $sitemap_list;
	private $current_sitemap_index = 0;
	private $url_list;
	private $current_url_index = 0;

	public function __construct($init_url)
	{
		$this->init_url = $init_url;
		$top_sitemap = $this->get_url_content($this->init_url,'UTF-8//IGNORE');
		
		//parse_sitemap
		$sitemaps = new SimpleXMLElement($top_sitemap);
		$this->sitemap_list = array();
		foreach($sitemaps->sitemap as $sitemap)
		{
			$this->sitemap_list[] = (string)$sitemap->loc;
		}
	}
	
	public function get_next_url()
	{
		if($this->current_sitemap_index >= count($this->sitemap_list) - 1 && $this->current_url_index >= count($this->url_list) - 1)
			return false;
		//当前sitemap还未初始化
		if(!isset($this->url_list) || empty($this->url_list))
		{
			echo 'get_sitemap' . $this->current_sitemap_index  . ' ' . $this->sitemap_list[$this->current_sitemap_index] . PHP_EOL;
			$sitemap_content = $this->get_url_content($this->sitemap_list[$this->current_sitemap_index],'UTF-8//IGNORE');
			file_put_contents('sitemap', $sitemap_content.PHP_EOL);
			if(empty($sitemap_content))
			{
				$this->current_sitemap_index ++;
				return $this->get_next_url();
			}
			$urls = new SimpleXMLElement($sitemap_content);

			$this->url_list = array();
			foreach($urls->url as $url)
			{
				$this->url_list[] = (string)$url->loc;
			}
		}
		$next_url = $this->url_list[$this->current_url_index];
		$this->current_url_index ++;
		
		//是最后一个url
		if($this->current_url_index == count($this->url_list))
		{
			//不是最后一个sitemap文件，设置url_list为初始状态
			if($this->current_sitemap_index < count($this->sitemap_list) - 1)
			{
				$this->url_list = array();
				$this->current_url_index = 0;
				$this->current_sitemap_index ++;
				//return $this->get_next_url();
			}
		}
		
		return $next_url;
	}
}

abstract class category_list_url_provider extends base_url_provider
{
	protected $init_url;
	protected $site_encode;
	protected $base_url = '';
		
	private $category_url_list = array();
	private $current_category_url = 0;
	private $current_page_index = 1;
	private $max_page = 0;
	protected $process_s_level_category = true;
	
	public $category_regex;
	public $max_page_regex;

	public function __construct()
	{
		//抓取初始页
		$top_page_content = $this->get_url_content($this->init_url,$this->site_encode);
		if(!empty($this->category_regex))
		{
			//找到分类列表
			$category_url_match_list = $this->get_preg_matchs($top_page_content, $this->category_regex);
			foreach($category_url_match_list as $category_url_match)
			{
				$url = $category_url_match['url'];
				if(stripos($url, 'http://') === false)
					$url = $this->base_url . $url;
				$this->category_url_list[] = $url;
			}
		}
	}
	
	public function get_next_url()
	{
		if(!empty($this->category_url_list))
		{
			//分类已经抓完
			if($this->current_category_url >= count($this->category_url_list))
				return false;
			//echo count($this->category_url_list) , PHP_EOL;
			//当前是第一页，抓取最大页面数
			if($this->current_page_index == 1)
			{
				//var_dump($this->category_url_list[$this->current_category_url]);
				$page_content = $this->get_url_content($this->category_url_list[$this->current_category_url],$this->site_encode);
				//file_put_contents('page_content', $page_content);
				$this->max_page = $this->get_max_page($page_content);
				//如果需要处理下级分类
				if($this->process_s_level_category)
				{
					//如果还有下级分类
					$s_level_category_match_list = $this->get_preg_matchs($page_content, $this->category_regex);
					if(!empty($s_level_category_match_list))
					{
						//将下级分类加入分类列表
						foreach($s_level_category_match_list as $s_level_category_match)
						{
							$url = $s_level_category_match['url'];
							if(stripos($url, 'http://') === false)
								$url = $this->base_url . $url;
							$this->category_url_list[] = $url;
						}
						//有下级分类的情况下，当前分类只返回一页
						$this->max_page = 1;
					}
				}
				//echo count($this->category_url_list) , PHP_EOL;
			}
			
			//返回分类页面
			if($this->current_page_index <= $this->max_page)
			{
				$base_url = $this->category_url_list[$this->current_category_url];
				$url = $this->make_page_url($base_url,$this->current_page_index);
				
				$this->current_page_index ++;
				
				if($this->current_page_index > $this->max_page)
				{
					$this->current_page_index = 1;
					$this->current_category_url ++;
				}
				return $url;
			}
		}
	}
	
	protected function get_max_page($page_content)
	{
		$max_page_match = $this->get_preg_match($page_content, $this->max_page_regex);
		if(empty($max_page_match))
			return 1;
		//var_dump($max_page_match);
		return $max_page_match['max_page'];
	}
	
	abstract protected function make_page_url($url,$page);
	//{
		//return str_replace('.html',"-0-0-0-0-0-0-0-1-1-{$page}.html",$url);
	//}
}


class onepage_url_provider extends base_url_provider
{
	public $init_url;
	public $visited = false;
	
	public function __construct($init_url)
	{
		$this->init_url = $init_url;
	}
	
	public function get_next_url()
	{
		if($this->visited)
			return false;
		else
		{
			$this->visited = true;
			return $this->init_url;
		}
	}
}