<?php
require_once dirname(__FILE__) . '/base_crawler.php';

class test_crawler extends base_crawler {
	public $site_charset = 'GBK';
	protected $max_count = 0;
	
	public function parse_content($content) {
		$result = helper::get_preg_matchs($content, '%<b class="f14"><a href="(?P<url>http://\w+\.pconline\.com\.cn/\w+(?:/\w+)?/\d+/\d+\.html)" target="_blank">(?P<title>[^<]*)</a></b>%');
		return $result;
	}
	public function extra_save_result($product) {
		return false;
	}
}

$crawler = new test_crawler ();
$crawler->debug_mode = true;
$crawler->resume_crawl = true;

$crawler->url_provider = new numberic_list_page_url_provider ( 'http://mobile.pconline.com.cn/news/index_%num%.html',1,34,'http://mobile.pconline.com.cn/news/' );
$crawler->result_saver = new url_saver(1);
$crawler->do_crawl ();