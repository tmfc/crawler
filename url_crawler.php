<?php
require_once dirname(__FILE__) . '/base_crawler.php';

class url_crawler extends base_crawler {
	//public $site_charset = 'GBK';
	private $url_regex;
	protected $max_count = 0;
	
	public function __construct($site_charset,$regex)
	{
		$this->site_charset = $site_charset;
		$this->url_regex = $regex;
		
	}
	public function parse_content($content) {
		$result = helper::get_preg_matchs($content, $this->url_regex);
		return $result;
	}
	public function extra_save_result($product) {
		return false;
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
	$crawler = new url_crawler ($source->site_charset,$source->url_regex);
	$crawler->debug_mode = true;
	$crawler->resume_crawl = true;
	
	$crawler->url_provider = new numberic_list_page_url_provider ( $source->url_template,$source->start,$source->end,$source->init_url );
	$crawler->result_saver = new url_saver($id);
	$crawler->do_crawl ();
}