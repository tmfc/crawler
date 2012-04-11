<?php
require_once dirname(__FILE__) . '/base_crawler.php';

define('DOWNLOAD_PATH',dirname(__FILE__) . '/downloads/');
define('IMG_SERVER','http://img1.feichang.com/news/');

class content_crawler extends base_crawler {
	//public $site_charset = 'GBK';
	private $content_part_rules;
	protected $max_count = 1;
	protected $source;
	
	protected $attachment_path_inited = false;
	protected $attachment_path;
	protected $current_download_id;
	protected $page_url;
	protected $result;
	
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
	public function parse_content($page_content) {
		//for every url,set attachment path inited as false
		$this->attachment_path_inited = false;
		$this->page_url = array();
		$this->result = array();
		$this->result['url'] = $this->current_url;
		//process $content,remove newlines
		$page_content = preg_replace('/\r|\n/', '', $page_content);
		//if part_name is 'content', use special routine
		foreach($this->content_part_rules as $rule)
		{
			$this->result[$rule->part_name] = $this->get_content_by_rule($page_content, $rule);
		}
		//var_dump($result);
		return $this->result;
	}
	
	public function extra_save_result($product) {
		return false;
	}
	
	protected function get_html_by_rule($page_content,$rule)
	{
		//use html tag to split
		if(stripos($rule->part_rule,'[content]') !== false)
		{
			$part_rule = explode('[content]', $rule->part_rule);
			$c = explode($part_rule[0], $page_content);
			$ret = explode($part_rule[1], $c[1]);
			$ret = $ret[0];
		}
		//use regex
		else
		{
			$ret = helper::get_preg_match_group($page_content,$rule->part_rule,'content');
		}
		//do filter,noly regex supplied
		if(!empty($rule->filter_rule))
		{
			$filter_rules = explode("|||", $rule->filter_rule);
			foreach($filter_rules as $filter_r)
			{
				$filter_rule = explode('[|]',trim($filter_r));
				$org_len = strlen($ret);
				$ret = preg_replace($filter_rule[0], $filter_rule[1], $ret);
			}
		}
		return trim($ret);
	}
	
	protected function get_content_by_rule($page_content,$rule)
	{
		$html = $this->get_html_by_rule($page_content,$rule);
		if($this->source->download_image == 1)
		{
			//find all images in html
			$images = helper::get_preg_matchs($html,'/<img[^>]*src="(?P<img>[^"]+)"/i');
			//download images
			foreach($images as $image)
			{
				//before download,init attachment path
				if(!$this->attachment_path_inited)
					$this->init_download_subpath();
				
				$img = helper::req_url($image['img']);
				$ext = substr($image['img'],strripos($image['img'],'.'));
				$filename = $this->attachment_path . $this->current_download_id . $ext;
				$filefullname = DOWNLOAD_PATH . $filename;
				file_put_contents($filefullname, $img);
				$html = str_ireplace($image['img'], IMG_SERVER . $filename, $html);
				if($this->current_download_id == 1)
					helper::createthumb($filefullname, str_ireplace($ext, '_s' . $ext,$filefullname), 100, 100);
				$this->current_download_id ++;
			}
			
		}
		if($rule->paged_part == 1)
		{
			//全部列出
			if($this->source->content_page_type == 1)
			{
				$this->page_url[] = $this->current_url;
				//取出包含所有页码的html
				$pager_rule = explode('[content]', $this->source->content_page_rule);
				$c = explode($pager_rule[0], $page_content);
				$pager_html = explode($pager_rule[1], $c[1]);
				$pager_html = $pager_html[0];
				
				$links = helper::get_preg_matchs($pager_html, '/<a[^>]*href=[\'"](?P<url>[^"\']+)[\'"]/i');
				foreach($links as $link)
				{
					$next_url = $link['url'];
					if(empty($next_url) || $next_url == '#')
						continue;
					if(in_array($next_url,$this->page_url))
						continue;
					
					$next_page_content = $this->get_url_content($next_url,$this->site_charset);
					$this->page_url[] = $next_url;
					$this->debug_info($next_url . ' ' .helper::$http_status_code,'url');
					$this->debug_info($next_page_content,'last_content',false,false);
					$next_page_content = preg_replace('/\r|\n/', '', $next_page_content);
					if(!empty($next_page_content))
					{
						$next_html = $this->get_content_by_rule($next_page_content, $rule);
						if(!empty($next_html))
							$html = $html . '[page]' . $next_html;
					}
				}
			}
			//下一页
			elseif($this->source->content_page_type == 2)
			{
				$next_url = helper::get_preg_match_group($page_content, $this->source->content_page_rule, 'next');
				
				//end of content
				if(empty($next_url))
					return $html;
				else
				{
					$next_page_content = $this->get_url_content($next_url,$this->site_charset);
					$this->debug_info($next_url . ' ' .helper::$http_status_code,'url');
					$this->debug_info($next_page_content,'last_content',false,false);
					$next_page_content = preg_replace('/\r|\n/', '', $next_page_content);
					if(!empty($next_page_content))
					{
						$next_html = $this->get_content_by_rule($next_page_content, $rule);
						if(!empty($next_html))
							return $html . '[page]' . $next_html;
						else
							return $html;
					}
				}
			}
		}
		return $html;
	}
	
	protected function init_download_subpath()
	{
		if(!empty($this->result['publish_time']))
		{
			$publish_time = strtotime($this->result['publish_time']);
		}
		else 
		{
			$publish_time = time();
		}
		$this->attachment_path = date('Y',$publish_time) . '/' . date('md',$publish_time) . '/' . md5($this->current_url). '/';
		$this->result['attachment_path'] = $this->attachment_path;
		if(!is_dir(DOWNLOAD_PATH . $this->attachment_path))
			mkdir(DOWNLOAD_PATH . $this->attachment_path,0755,true);
		
		$this->current_download_id = 1;
		
		//set attachment path inited as true
		$this->attachment_path_inited = true;
	}
}
$id = $argv[1];
//$id = 2;
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
	$crawler->result_saver = new content_saver( $id );
	$crawler->do_crawl ();
}