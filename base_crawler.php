<?php
require_once dirname(__FILE__)."/lib/init.php";
require_once dirname(__FILE__)."/url_provider.php";
require_once dirname(__FILE__)."/result_saver.php";
require_once dirname(__FILE__)."/lib/status_reporter.php";

abstract class base_crawler
{
	//站点编码
	public $site_charset = 'UTF-8//IGNORE';
	
	//url提供者、结果保存者
	public $url_provider = null;
	public $result_saver = null;
	
	protected $current_url = null;
	protected $current_post_data = null;
	protected $count = 0;
	protected $max_count = 0;
	
	//是否是调试模式
	public $debug_mode = false;
	public $need_captcha = false;
	public $crawl_by_post = false;
	public $fake_crawl = false;
	public $resume_crawl = true;
	
	//分析网页
	abstract public function parse_content($content);
	//对结果做额外的处理
	abstract public function extra_save_result($result);
	
	//获取需要post的信息，通常不需要，返回NULL
	public function get_post_data()
	{
		return NULL;
	}
	
	//处理验证码，通常情况不需要处理，空函数
	protected function process_captcha($content)
	{
		return NULL;	
	}
	
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
				$this->debug_info($this->count);
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
				
				//需要输入验证码
				if($this->need_captcha)
				{
					$content = $this->get_url_content($url,$this->site_charset);
					$this->process_captcha($content);
				}
				
				//获取下一个post_data
				if($this->crawl_by_post)
				{
					$post_data = $this->get_post_data();
					if($post_data == false || empty($post_data))
					{
						$this->debug_info("\r\nTotal: ".$this->count."\r\n");
						$this->debug_info("crawler end:no more post data to crawl");
						status_reporter::update('url_provider','');
						status_reporter::end();
						return;
					}
					
					$this->current_post_data = $post_data;
				}
				
				//假抓取
				if($this->fake_crawl)
				{
					$this->debug_info($url,'url');
					if($this->crawl_by_post)
						$this->debug_info($this->current_post_data,'post_data');
					status_reporter::update('url_provider',$url);
					status_reporter::update('url_count',"++");
					status_reporter::report();
					continue;
				}
				
				//抓取页面内容
				if($this->crawl_by_post)
				{
					$content = $this->post_url_content($url,$this->current_post_data,$this->site_charset);
				}
				else 
				{
					$content = $this->get_url_content($url,$this->site_charset);
				}

				//记录debug信息
				$this->debug_info($url . ' ' .helper::$http_status_code,'url');
				$this->debug_info($content,'last_content',false,false);
				status_reporter::update("memory",round((memory_get_usage(true)/1024/1024),2));
				
				//通知url_provider当前url的抓取状态和抓取内容，供url_provider进行判断
				$this->url_provider->result_notify(helper::$http_status_code,$content);
	
				if ($content === false)
				{
					$this->debug_info("crawler continue:get_url_content return false,url:$url,status:" . helper::$http_status_code);
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
			file_put_contents(get_class($this) . "_$type.log", $info. PHP_EOL,$append?FILE_APPEND:null);
		}
	}
	
	protected function get_url_content($url, $s_charact = "gbk", $d_charact = "UTF-8//IGNORE") {
		return helper::get_url_content($url, $s_charact = "gbk", $d_charact = "UTF-8//IGNORE");
	}
	protected function post_url_content($url, $post_data,$s_charact = "gbk", $d_charact = "UTF-8//IGNORE"){
		return helper::post_url_content($url, $post_data,$s_charact = "gbk", $d_charact = "UTF-8//IGNORE");
	}
}


?>