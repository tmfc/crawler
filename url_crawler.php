<?php
require_once dirname(__FILE__) . '/base_crawler.php';

class url_crawler extends base_crawler {
	//public $site_charset = 'GBK';
	private $source;
	private $url_regex;
	protected $max_count = 0;
	
	public function __construct($source)
	{
		$this->source = $source;
		$this->site_charset = $source->site_charset;
		$this->url_regex = $source->url_regex;
		
	}
	public function parse_content($content) {
		$urls = helper::get_preg_matchs($content, $this->url_regex);
		$result = array();
		foreach($urls as $url_info)
		{
			$url = $url_info['url'];
			$title = $url_info['title'];
			//url contains
			if($this->match_url_must_contain($url, $this->source->url_must_contain) 
				&& $this->match_url_must_not_contain($url, $this->source->url_must_not_contain)
			)
				$result[] = array('url'=>$url,'title'=>$title);
		}
		return $result;
	}
	public function extra_save_result($product) {
		return false;
	}
	
	protected function match_url_must_contain($url,$must_contain)
	{
		if(empty($must_contain))
			return true;
		else
			return stripos($url,$must_contain);
	}
	
	protected function match_url_must_not_contain($url,$must_not_contain)
	{
		if(empty($must_not_contain))
			return true;
		else
			return stripos($url,$must_not_contain) === false;
	}
	
	
}
$id = $argv[1];
$source = source::find($id);
if(!$source)
{
	echo 'no source,id:' . $id;
}
else
{
	$crawler = new url_crawler ($source);
	$crawler->debug_mode = true;
	$crawler->resume_crawl = true;
	
	$crawler->url_provider = new numberic_list_page_url_provider ( $source->url_template,$source->start,$source->end,$source->init_url );
	$crawler->result_saver = new url_saver($id);
	$crawler->do_crawl ();
}