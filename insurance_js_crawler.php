<?php
require_once dirname(__FILE__) . '/base_crawler.php';

class js_url_provider extends base_url_provider
{
	private $current_index;
	private $current_page;
	private $page_size = 100;
	private $jiangsu_db_url_list;
	public function __construct()
	{
		$this->current_page = 0;
		$this->get_jiangsu_db_url_list();
	}

	protected function get_jiangsu_db_url_list()
	{
		$this->jiangsu_db_url_list = vehicle_info_js::find('all',array('conditions' => array('status' => vehicle_info_js::INIT),'limit' => $this->page_size,'offset' => $this->current_page * $this->page_size));
		$this->current_page ++;
		$this->current_index = 0;
	}
	
	public function get_next_post_data()
	{
		//var_dump(count($this->jiangsu_db_url_list));
		
		if($this->current_index == count($this->jiangsu_db_url_list))
		{
			$this->get_jiangsu_db_url_list();
		}
		if(count($this->jiangsu_db_url_list) == 0)
			return false;

		$ret = "panduan=1&queryMethod=queryByFrame&policyNo=&licenseNo=&frameLastSixNo2=&frameLastSixNo1=&engineLastSixNo=&frameNo=" . $this->jiangsu_db_url_list[$this->current_index]->vehicle_id . "&random=";
		$this->current_index ++;
		//echo $ret;
		return $ret;
	}
	
	public function get_next_url(){
		return "http://221.226.36.226:9003/jiastat/peruser/queryByFrame.do";
	}
}

class js_test_url_provider extends base_url_provider
{
	private $init_url;
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

	public function get_next_post_data()
	{
		return "panduan=1&queryMethod=queryByFrame&policyNo=&licenseNo=&frameLastSixNo2=&frameLastSixNo1=&engineLastSixNo=&frameNo=LSGTC52UX7Y043780&random=";
	}
}

class insurance_info_js_result_saver extends base_result_saver
{
	public function save_result($result) {
		foreach($result as $r) {
			$policy_no = $r->policyno;
			
			$insurance_info = insurance_info_js::find_by_policy_no($policy_no);
			if(!$insurance_info)
			{
				$insurance_info = new insurance_info_js();
			}
			$insurance_info->vehicle_id = $r->vehicle_id;
			$insurance_info->license_no = $r->licenseno;
			$insurance_info->license_type = $r->licenseType;
			$insurance_info->policy_no = $r->policyno;
			$insurance_info->company_code = $r->companycode;
			$insurance_info->insurance_type = $r->risktype;
			$insurance_info->operate_date = $r->operateDate;
			$insurance_info->start_date = $r->startdate;
			$insurance_info->end_date = $r->enddate;
			$insurance_info->claim_query_no = $r->claimqueryno;
			$insurance_info->claim_status = $r->claimstatus;
			$insurance_info->damage_date = $r->damagedate;
			$insurance_info->report_date = $r->reportdate;
			$insurance_info->clain_date = $r->claindate;
			$insurance_info->endcase_date = $r->endcasedate;
			$insurance_info->driver_name = $r->drivername;
			$insurance_info->estimate_loss = $r->estimateloss;
			$insurance_info->sum_paid = $r->sumpaid;
			$insurance_info->sum_all_paid = $r->sumallpaid;
			$insurance_info->currency = $r->currency;
			$insurance_info->indemnity_duty = $r->indemnityduty;
			$insurance_info->claim_no = $r->claimno;
			$insurance_info->claim_type = $r->claimtype;
			$insurance_info->regist_no = $r->registno;
			$insurance_info->case_no = $r->caseno;
			$insurance_info->claim_cyc = $r->claimcyc;
			$insurance_info->valid_status = $r->validstatus;
			$insurance_info->policy_confirm_no = $r->policyConfirmNo;
			$insurance_info->confirm_sequence_no = $r->confirmSequenceNo;
			$insurance_info->end_case_cyc = $r->endcasecyc;
			$insurance_info->remark = $r->remark;

			$insurance_info->save();
				
		}
	}
}

class insurance_js_crawler extends base_crawler {
	public $base_url = "http://221.226.36.226:9003/";
	public $site_charset = 'GBK';
	protected $max_count = 0;
	protected $captcha = "";
	public function parse_content($content) {
		$frameNo = helper::get_preg_match_group($this->current_post_data, '/frameNo=(?P<frameNo>.*)&/', 'frameNo');
		$result = helper::get_preg_match_group($content, '/portal_softDownMore_list = (?P<data>\[.*\])/','data');
		$result = json_decode($result);
		foreach($result as $r)
		{
			$r->vehicle_id = $frameNo;
		}
		return $result;
	}
	public function extra_save_result($product) {
		return false;
	}
	function process_captcha($content){
		if(!$this->fake_crawl)
		{
			$captcha_img_url = $this->base_url . 'jiastat/pages/login/RandomNumUtil.jsp?d=' . time();
			helper::download_image($captcha_img_url, 'captcha.jpg');
			$this->captcha = exec("php crack/crack.php captcha.jpg");
			$this->debug_info($this->captcha);
		}
	}
	function get_post_data()
	{
		$ret = $this->url_provider->get_next_post_data() . $this->captcha;
		$this->debug_info($ret);
		return $ret;
	}
}

$crawler = new insurance_js_crawler ();
$crawler->debug_mode = true;
$crawler->need_captcha = true;
$crawler->crawl_by_post = true;
$crawler->resume_crawl = false;
//$crawler->fake_crawl = true;

//$crawler->url_provider = new js_test_url_provider ( 'http://221.226.36.226:9003/jiastat/peruser/queryByFrame.do');
$crawler->url_provider = new js_url_provider();
$crawler->result_saver = new insurance_info_js_result_saver();
$crawler->do_crawl ();
