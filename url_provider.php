<?php
abstract class base_url_provider
{
	abstract public function get_next_url();

	public function result_notify($status_code,$content)
	{
		return ;
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
		$top_sitemap = get_url_content($this->init_url,'UTF-8//IGNORE');
		
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
			$sitemap_content = get_url_content($this->sitemap_list[$this->current_sitemap_index],'UTF-8//IGNORE');
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

class category_url_provider extends base_url_provider
{
	public $domain_id;
	private $category_list;
	private $current_category_index = 0;
	private $current_page_index = 1;
	public $max_page_regex = null;
	public $page_size = 1;
	
	public $current_category;
	
	public $start_page_index = 1;
	public $default_max_page = 10;
	private $max_page = 0 ;
	
	public function __construct($domain_id,$start_page_index = 1)
	{
		$this->domain_id = $domain_id;
		$this->start_page_index = $start_page_index;
		$this->current_page_index = $this->start_page_index;
	}
	
	public function get_next_url()
	{
		if(!isset($this->category_list) || empty($this->category_list))
		{
			$this->category_list = Category::get_refleshable_domain_category ($this->domain_id);
		}
		
		if($this->current_category_index >= count($this->category_list)) {
			return false;
		}
		else 
		{
			$cate = $this->category_list[$this->current_category_index];
			$this->current_category = $cate;
			$next_url = str_replace ( "[page]", $this->current_page_index, $cate->object_mapping->source_url );
			$this->current_page_index ++;
			
			if($this->max_page == 0 && $this->current_page_index > $this->default_max_page)
				$this->category_change();
			
			return $next_url;
		}
	}
		
	public function result_notify($status_code,$content)
	{
		if(in_array($status_code, array(301,302,404)))
			$this->category_change();
		else
		{
			$this->max_page = $this->get_max_page($content);
			
			if(($this->max_page - ($this->start_page_index==0?1:0)) < ($this->current_page_index))
				$this->category_change();
		}
	}
	
	private function get_max_page($content)
	{		
		if(isset($content) && !empty($content) && isset($this->max_page_regex) && !empty($this->max_page_regex))
		{
			if (preg_match ( $this->max_page_regex, $content, $total_page_match )) 
			{
				$total_page = $total_page_match['max_page'] / $this->page_size;
				return $total_page;
			}
			else 
				return $this->default_max_page;
		}
		else
			return $this->default_max_page;
	}
	
	private function category_change()
	{
		$this->current_category_index ++;
		$this->current_page_index = $this->start_page_index;
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
		$top_page_content = get_url_content($this->init_url,$this->site_encode);
		if(!empty($this->category_regex))
		{
			//找到分类列表
			$category_url_match_list = get_preg_matchs($top_page_content, $this->category_regex);
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
				$page_content = get_url_content($this->category_url_list[$this->current_category_url],$this->site_encode);
				//file_put_contents('page_content', $page_content);
				$this->max_page = $this->get_max_page($page_content);
				//如果需要处理下级分类
				if($this->process_s_level_category)
				{
					//如果还有下级分类
					$s_level_category_match_list = get_preg_matchs($page_content, $this->category_regex);
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
		$max_page_match = get_preg_match($page_content, $this->max_page_regex);
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

class jingdong_book_url_provider extends category_list_url_provider
{
	protected $init_url = 'http://book.360buy.com/';
	protected $site_encode = "gb2312";
	
	public $category_regex = '%<a href=\'(?P<url>http://www\.360buy\.com/products/\d+-\d+-\d+\.html)\' title=\'[^\']+\'>[^<]+</a>%';
	public $max_page_regex = '%<a href="[^"]+"[^>]*>(?P<max_page>\d+)</a><a href="[^"]+" class="next">%';
	protected function make_page_url($url,$page)
	{
		return str_replace('.html',"-0-0-0-0-0-0-0-1-1-{$page}.html",$url);
	}
}

class dangdang_book_url_provider extends category_list_url_provider
{
	protected $init_url = 'http://list.dangdang.com/book/01.htm?ref=book-01-A';
	protected $site_encode = "GB2312";
	protected $base_url = 'http://list.dangdang.com/book/';
	
	public $category_regex = '/<li><a name=\'linkCategory\' href="(?P<url>[^"]+)" title="[^"]+">/';
	public $max_page_regex = '/共(?P<max_page>\d+)页/';
	protected function make_page_url($url,$page)
	{
		return str_replace(array('_Z40.html','_Z25.html'),"_P{$page}_Z40.html",$url);
	}
}

class pclady_url_provider extends base_url_provider
{
	protected $init_url = 'http://cosme.pclady.com.cn/products_list/br0_bs0_bi0_sm0_ef0_pb0_pe0_or5';
	protected $max_page = 0;
	protected $curr_page = 0;
	protected $site_encode = "GBK";
	
	
	public function get_next_url()
	{
		if($this->max_page==0 && $this->curr_page==0){
			$page_content = get_url_content($this->init_url.'.html', $this->site_encode);
			$next_page_tag = get_preg_match($page_content, '%>(?P<max>[\d]+)</a><a href=\'([^\']+?)\'\s+class=\'next\'>下一页</a>%s');
			$this->max_page = $next_page_tag['max'];
		}
		
		if($this->curr_page==0)
		{
			$this->curr_page = 1;
			return $this->init_url.'.html';
		} elseif ($this->curr_page<$this->max_page){
			$url = $this->init_url.'_p'.$this->curr_page.'.html';
			$this->curr_page++;
			return $url;
		} else {
			return false;
		}
	}
}

class strawberry_url_provider extends base_url_provider
{
	private $base_url = 'http://cn.strawberrynet.com';
	protected $site_encode = "GB2312";
	private $nav_list = array('http://cn.strawberrynet.com/skincare/',
							  'http://cn.strawberrynet.com/makeup/',
							  'http://cn.strawberrynet.com/haircare/',
							  'http://cn.strawberrynet.com/perfume/',
							  'http://cn.strawberrynet.com/mens-skincare/',
							  'http://cn.strawberrynet.com/mens-fragrances/');
	private $current_nav_index = 0;
	private $brand_list = array();
	
	
	public function __construct()
	{}
	
	public function get_next_url()
	{
		if(empty($this->brand_list)) {
			if(!$this->fill()) {
				return false;
			}
		}
		
		$url = array_shift($this->brand_list);
		return $url;
	}
	
	public function fill() {
		if(!isset($this->nav_list[$this->current_nav_index])) {
			return false;
		}
		$nav = $this->nav_list[$this->current_nav_index];	
		$this->current_nav_index++;

		$content = get_url_content($nav);
		$r = get_preg_matchs($content, '%<a class="indexItem" onclick="[^"]+" href="(?P<url>[^"]+)">[^<]+<br /><font class="font_M">[^<]+</font></a>%s');
		foreach($r as $match) {
			array_push($this->brand_list, $this->base_url . $match['url']);
		}

		return true;
	}

	public function add_url($url) {
		array_push($this->brand_list, $url);
	}
}

class ocj_url_provider extends base_url_provider 
{
	private $init_url = 'http://ocj.com.cn/shop/section_M.jsp?wLgroup=66&wMgroup=<ireplace>&showType=2';
	private $cat_id = array('6601', '6602', '6605', '6606', '6607', '6613', '6617', '6618', '6619', '6620', '6621', '6622');
	private $current_url = '';

	private $max_page = 10;
	private $current_page = 0;


	public function __construct()
	{}
	
	public function get_next_url() {
		if(0 == $this->current_page || $this->current_page > $this->max_page) {
			$r = $this->get_next_category();
			if(!$r) {
				return false;
			}
		}
		
		return $this->current_url . '&currpage=' . $this->current_page++;
	}

	private function get_next_category() {
		if(empty($this->cat_id)) {
			return false;
		}
		
		$current_cat_id = array_shift($this->cat_id);

		$cat_url = str_replace('<ireplace>', $current_cat_id, $this->init_url);
		$this->current_url = $cat_url;

		$content = get_url_content($cat_url);
		$r = get_preg_match($content, '%(?P<max_page>\d+)</a> <[^>]+>下一页%s');
		$this->max_page = empty($r['max_page']) ? 1 : $r['max_page'];

		$this->current_page = 1;

		return true;
	}
}

class yihaodian_url_provider extends base_url_provider 
{
	private $init_url = 'http://search.yihaodian.com/s/c5009-b0-a-s1-v0-p[page]-price-d0-f0-k';
	private $current_page = 0;
	private $max_page = 201;

	public function __construct()
	{}
	
	public function get_next_url() {
		if(0 == $this->current_page) {
			$content = get_url_content(str_replace('[page]', 1, $this->init_url), 'utf-8');
			$r = get_preg_match($content, '%id="lastPage" href="[^"]+">(?P<max_page>\d+)%s');
			if(isset($r['max_page']) && !empty($r['max_page'])) {
				$this->max_page = $r['max_page'];
			}
		}

		if($this->current_page > $this->max_page) {
			return false;
		} else {
			$this->current_page++;
			return str_replace('[page]', $this->current_page, $this->init_url);
		}
	}
}

class dazhe_url_provider extends base_url_provider 
{
	private $init_url = 'http://www.dazhe.cn/goodslist-04-0-0-pagenum[page].html';
	private $current_page = 0;
	private $max_page = 201;

	public function __construct()
	{}
	
	public function get_next_url() {
		if(0 == $this->current_page) {
			$content = get_url_content(str_replace('[page]', 1, $this->init_url), 'utf-8');
			$r = get_preg_match($content, "%<a href='goodslist-04-0-0-pagenum(?P<max_page>\d+).html'>最后一页</a>%s");
			if(isset($r['max_page']) && !empty($r['max_page'])) {
				$this->max_page = $r['max_page'];
			}
		}

		if($this->current_page > $this->max_page) {
			return false;
		} else {
			$this->current_page++;
			return str_replace('[page]', $this->current_page, $this->init_url);
		}
	}
}

class nala_url_provider extends base_url_provider 
{
	private $init_url = 'http://www.nala.com.cn/item/search?content=&sort=sequence&max=24&price=&order=desc&func=&offset=[page]&brandId=&discount=';
	private $current_page = 0;
	private $max_page = 201;

	public function __construct()
	{}
	
	public function get_next_url() {
		if(0 == $this->current_page) {
			$content = get_url_content(str_replace('[page]', 1, $this->init_url), 'utf-8');
			$r = get_preg_match($content, '%(?P<max_page>\d+)</a><a href="[^"]+" class="nextLink">下一页%s');
			if(isset($r['max_page']) && !empty($r['max_page'])) {
				$this->max_page = $r['max_page'];
			}

			echo $this->max_page;
		}

		if($this->current_page > $this->max_page) {
			return false;
		} else {
			$this->current_page++;
			return str_replace('[page]', $this->current_page * 24, $this->init_url);
		}
	}
}


class piaoliang100_url_provider extends base_url_provider
{
	protected $site_encode = "UTF-8";
	
	/*
	*	入口在，品牌分类(分页)
	*	http://www.piaoliang100.com/brand-0-0-1.html
	*	进入某一分类后，出现产品列表(分页)，例如：
	*	http://www.piaoliang100.com/brandgoods-338-0-1.html
	*/
	private $brand_page = 1;
	private $brand_page_max = 0;
	private $brand_id_list = array();
	
	private $product_page = 1;
	private $product_page_max = 0;
	private $product_url_list = array();
	//每页产品数量
	private $product_num_per_page = 24;
	
	//当前品牌
	private $currentBrand = '';
	
	public function __construct()
	{}
	
	//获取产品列表页url
	public function get_next_url()
	{
		//初始化，第一次进入
		if ('' === $this->currentBrand) {
			$this->currentBrand = $this->next_brand_url();
		}
		
		//若品牌为空，直接返回
		if(false == $this->currentBrand) {
			return false;
		}
		
		if(1==$this->product_page || $this->product_page <= $this->product_page_max) {
			if(1 == $this->product_page) {
				$page_content = get_url_content($this->currentBrand, $this->site_encode);
				$end_page_tag = get_preg_match($page_content, '/href="[^"]+-(?P<max>[\d]+).html">尾页/s');
				if(empty($end_page_tag['max'])) {
					$this->product_page_max = 1;
				} else {
					$this->brand_page_max = $end_page_tag['max'];
				}
			}
			
			$url = $this->get_product_list_url($this->product_page);
			$this->product_page++;
			return $url;
		} else {
			$this->currentBrand = $this->next_brand_url();
			$this->product_page = 1;
			$this->product_page_max = 0;
			return $this->get_next_url();
		}
	}
	
	private function get_product_list_url($page) {
		return preg_replace('/\d+.html/', "$page.html", $this->currentBrand);
	}
	
	//获取下一个品牌url
	private function next_brand_url() {
		//url列表为空，下一页，或者退出
		if(empty($this->brand_id_list)) {
			if(1==$this->brand_page || $this->brand_page <= $this->brand_page_max) {
				$page_content = get_url_content($this->get_brand_list_url($this->brand_page), $this->site_encode);
				if(1 == $this->brand_page) {
					$end_page_tag = get_preg_match($page_content, '/href="[^"]+-(?P<max>[\d]+).html">尾页/s');
					$this->brand_page_max = $end_page_tag['max'];
				}
				
				//取品牌id列表
				preg_match_all('%<a href="http://www.piaoliang100.com/brandgoods-(\d+)-0-1.html" target="_blank">\s+<span>%s', $page_content, $brand_id_list, PREG_PATTERN_ORDER);
				$this->brand_id_list = $brand_id_list[1];
				
				$this->brand_page++;
			} else {
				return false;
			}
		}
		
		//url列表不为空，弹出一个url
		$brand_id = $this->brand_id_list[0];
		array_shift($this->brand_id_list);
		
		return $this->get_brand_url($brand_id);
	}
	
	//根据页码返回品牌列表页url
	private function get_brand_list_url($page) {
		return "http://www.piaoliang100.com/brand-0-0-{$page}.html";
	}
	
	//据品牌id返回品牌页url
	private function get_brand_url($brand_id) {
		return "http://www.piaoliang100.com/brandgoods-{$brand_id}-6-1.html";
	}
}

class coupon_url_provider extends base_url_provider
{
	protected $init_url = 'http://zhekou.xungou.com';
	protected $curr = false;
	protected $next = false;
	protected $site_encode = "GBK";
	
	public function __construct($url)
	{
		$this->next = $url;
	}
	
	public function get_next_url()
	{
		$this->curr = $this->next;
		if($this->curr == false){
			return false;
		}
		echo $this->curr."\r\n";
		
		$page_content = get_url_content($this->curr, $this->site_encode);
		$next_page_tag = get_preg_match($page_content, '/<a href="(?P<next>[^"]+)" class="next">下一页/s');
		if(empty($next_page_tag)){
			$this->next = false;
			return $this->curr;
		}else{
			$this->next = $this->init_url.$next_page_tag['next'];
			return $this->curr;
		}
	}
}

class amazon_book_url_provider extends category_list_url_provider
{
	protected $init_url = 'http://www.amazon.cn/gp/search/ref=sr_hi_1?rh=n%3A658390051&bbn=658391051&ie=UTF8';
	//protected $site_encode = "";
	protected $base_url = 'http://www.amazon.cn';
	
	public $category_regex = '#<a href="(?P<url>/gp/search/ref=sr_nr_n_\d+\?rh=n%3A658390051%2Cn%3A%21658391051(%2Cn%3A\d+)?%2Cn%3A\d+&bbn=\d+&ie=UTF8&qid=\d+&rnid=\d+)">#';
	public $max_page_regex = '/共(?P<max_page>[\d,]+)条/';
	
	protected function get_max_page($page_content)
	{
		$max_page_match = get_preg_match($page_content, $this->max_page_regex);
		if(empty($max_page_match))
			return 1;
		$item_count = str_replace(',', '', $max_page_match['max_page']);
		return (($item_count / 12) <= 400)?ceil($item_count / 12):400;
	}
	
	protected function make_page_url($url,$page)
	{
		$n_id_matches = get_preg_matchs($url, '/%2Cn%3A(?P<n_id>\d+)/');
		$n_id = $n_id_matches[count($n_id_matches) - 1]['n_id'];
		return "http://www.amazon.cn/mn/search/ajax/ref=sr_pg_2?rh=n:{$n_id}&page={$page}&ie=UTF8&fromHash=&section=ATF,BTF";
	}
}

class _51fanli_url_provider extends category_list_url_provider{
	protected $init_url = 'http://www.51fanli.com/procat.asp';
	protected $site_encode = "gb2312";
	protected $base_url = 'http://www.51fanli.com/procat.asp';
	
	protected $process_s_level_category = false;
	
	public $category_regex = '/(?P<url>\?t=\d+)#leftmenu/s';
	public $max_page_regex = '%<li><a href="\?t=\d+&c=&page=(?P<max_page>\d+)#leftmenu">\d+</a></li><li><a href="\?t=\d+&c=&page=2#leftmenu">&gt;&gt;</a>%s';
	
	protected function get_max_page($page_content)
	{
		$max_page_match = get_preg_match($page_content, $this->max_page_regex);
		//var_dump($max_page_match);
		if(empty($max_page_match))
			return 1;
		return $max_page_match['max_page'];
	}
	
	public function make_page_url($url,$page)
	{
		return str_replace('procat.asp?',"procat.asp?c=&page={$page}&",$url);
	}
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