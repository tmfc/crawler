<?php
require_once dirname(__FILE__) . '/base_crawler.php';

class test_crawler extends base_crawler {
	public $site_charset = 'GBK';
	protected $max_count = 0;
	public $domain_id = 26;
	
	public function parse_content($content) {
		return array();
	}
	public function extra_save_result($product) {
		return false;
	}
}

$crawler = new test_crawler ();
$crawler->debug_mode = true;
$crawler->resume_crawl = true;

$crawler->url_provider = new category_url_provider ( $crawler->domain_id );//new pclady_url_provider( );
$crawler->url_provider->max_page_regex = '%>(?P<max_page>[\d]+)</a><a href=\'([^\']+?)\'\s+class=\'next\'>下一页</a>%s';
$crawler->result_saver = new blackhole_saver();
$crawler->do_crawl ();