<?php
require_once dirname(__FILE__)."/init.php";

class status_reporter
{
	static private $extra_status = array();
	static private $host;
	static private $job_name;
	
	static private $job_status;
	
	static private $last_update_time = 0;
	
	static public $internal = 60;
	
	static public function init($job_name)
	{
		status_reporter::$job_name = $job_name;
		$host_name = getenv('HOSTNAME');
		status_reporter::$host = empty($host_name)?"default":$host_name;
		status_reporter::$job_status = job_status::get_by_name_and_host(status_reporter::$job_name, status_reporter::$host);
		if(empty(status_reporter::$job_status))
		{
			status_reporter::$job_status = new job_status();
			status_reporter::$job_status->name = status_reporter::$job_name;
			status_reporter::$job_status->host = status_reporter::$host;
			status_reporter::$job_status->status = job_status::NOT_START; //尚未开始
			status_reporter::$job_status->extra_status = serialize(status_reporter::$extra_status);
			status_reporter::$job_status->start_time = date('c');
			status_reporter::$job_status->create();
		}
		else
		{
			status_reporter::$job_status->extra_status = unserialize(status_reporter::$job_status->extra_status);
		}
	}
	
	static public function start()
	{
		if(!empty(status_reporter::$job_status))
		{
			status_reporter::$job_status->status = job_status::RUNNING;//正在运行
			status_reporter::$job_status->extra_status = serialize(status_reporter::$extra_status);
			status_reporter::$job_status->start_time = date('c');
			status_reporter::$job_status->end_time = null;
			status_reporter::$job_status->save();
		}
	}
	
	static public function end($status = job_status::END)
	{
		if(!empty(status_reporter::$job_status))
		{
			status_reporter::$job_status->status = $status; //退出状态
			status_reporter::$job_status->extra_status = serialize(status_reporter::$extra_status);
			status_reporter::$job_status->end_time = date('c');
			status_reporter::$job_status->save();
		}
	}
	
	static public function update($name,$value)
	{
		if($value == "++")
		{
			if(!key_exists($name, status_reporter::$extra_status))
				status_reporter::$extra_status[$name] = 1;
			else
				status_reporter::$extra_status[$name]  = status_reporter::$extra_status[$name] + 1;
		}
		else 
			status_reporter::$extra_status[$name] = $value;
	}
	
	static public function report()
	{
		if(!empty(status_reporter::$job_status))
		{
			if(status_reporter::$internal > 0 && time() - status_reporter::$last_update_time < status_reporter::$internal)
				return;
			status_reporter::$job_status->extra_status = serialize(status_reporter::$extra_status);
			status_reporter::$job_status->update_time = date('c');
			status_reporter::$job_status->save();
			
			status_reporter::$last_update_time = time();
		}
	}
	
	static public function get_last_report()
	{
		return status_reporter::$job_status;
	}
}