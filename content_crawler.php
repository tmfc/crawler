<?php
require_once dirname(__FILE__) . '/base_crawler.php';


class content_crawler extends base_crawler {
	//public $site_charset = 'GBK';
	private $content_part_rules;
	protected $max_count = 1;
	protected $source;
	
	public function __construct($source_id,$source)
	{
		$this->site_charset = $source->site_charset;
		$this->source = $source;

		$this->content_part_rules = source_content_rule::find('all', array('conditions' => array('sourceid' => $source_id)));
		if(!$this->content_part_rules)
		{
			echo 'no content part rules,source:' . $source_id;
			exit;
		}
	}
	public function parse_content($content) {
		$result = array();
		//process $content,remove newlines
		$content = preg_replace('/\r|\n/', '', $content);
		//TODO:if part_name is 'content', use special routine
		foreach($this->content_part_rules as $rule)
		{
			$result[$rule->part_name] = $this->get_content_by_rule($content, $rule);
		}
		//var_dump($result);
		return $result;
	}
	public function extra_save_result($product) {
		return false;
	}
	
	protected function get_content_by_rule($content,$rule)
	{
		//use html tag to split
		if(stripos($rule->part_rule,'[content]') !== false)
		{
			$part_rule = explode('[content]', $rule->part_rule);
			$c = explode($part_rule[0], $content);
			$ret = explode($part_rule[1], $c[1]);
			$ret = $ret[0];
		}
		//use regex
		else
		{
			$ret = helper::get_preg_match_group($content,$rule->part_rule,'content');
		}
		//do filter,noly regex supplied
		if(!empty($rule->filter_rule))
		{
			$rule->filter_rule = str_replace("\r","", $rule->filter_rule);
			$filter_rules = explode("\n", $rule->filter_rule);
			foreach($filter_rules as $filter_r)
			{
				$filter_rule = explode('[|]',$filter_r);
				$ret = preg_replace($filter_rule[0], $filter_rule[1], $ret);
			}
		}
		return trim($ret);
	}
	
	protected function get_for_content($page_content,$rule)
	{
		$content = $this->get_content_by_rule($page_content,$rule);
		if($source->download_image == 1)
		{
			//TODO:download images
		}
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
	$crawler = new content_crawler ($id,$source);
	$crawler->debug_mode = true;
	$crawler->resume_crawl = true;
	
	$crawler->url_provider = new content_db_url_provider ( $id );
	$crawler->result_saver = new blackhole_saver();
	$crawler->do_crawl ();
}