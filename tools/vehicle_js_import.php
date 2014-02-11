<?php
require_once dirname(__FILE__)."/../lib/init.php";
require_once dirname(__FILE__)."/../lib/helper.php";
require_once dirname(__FILE__)."/../lib/status_reporter.php";

ini_set("auto_detect_line_endings", true);

helper::$debug_file_name = "vehicle_js_import";

status_reporter::init("vehicle_js_import");
status_reporter::start();


$filename = $argv[1];
status_reporter::update('filename',$filename);
$file = fopen($filename,"r");

if(!$file)
{
	helper::debug_info('file open failed');
	status_reporter::update('status','file open failed');
	status_reporter::end(2);
	die();
}
status_reporter::report();

$index = 0;
$skiped_count = 0;
$processed_count = 0;
$error_count  = 0;
while(! feof($file))
{
	$index ++;
	$line = fgets($file);
	$line = iconv('gb2312','utf-8',$line);
	if($index % 100 == 0)
	{
		helper::debug_info('current line:' . $index);
		status_reporter::update('current line',$index);
		status_reporter::report();
	}
	try{
		$columns = explode(';',trim($line));
		if(!in_array(count($columns), array(8,9,11,12,13)))
		{
			helper::debug_info("format not match");
			$skiped_count ++;
			continue;
		}
		
		$info = new vehicle_info_js();
		if(count($columns) == 8)
		{
			$exist = vehicle_info_js::exists(array('vehicle_id' => $columns[3]));
				
			if($exist)
			{
				$skiped_count ++;
				continue;
			}
				
			//$info->id_type = 'C'; //底盘号
			$info->vehicle_number_type = 0;//$columns[1];
			$info->vehicle_number = $columns[0];
			$info->vehicle_brand = $columns[1];
			$info->vehicle_type = $columns[2];
			$info->vehicle_id = $columns[3];
			$info->engine_id = $columns[4];
			$info->owner = $columns[5];
			$info->enroll_time = $columns[6];
			$info->telphone = $columns[7];
			$info->mobile = $columns[7];
		}
		elseif(count($columns) == 9 )
		{	
			$exist = vehicle_info_js::exists(array('vehicle_id' => $columns[6]));
			
			if($exist)
			{
				$skiped_count ++;
				continue;
			}
			
			//$info->id_type = 'C'; //底盘号
			$info->vehicle_number_type = $columns[1];
			$info->vehicle_number = '苏A' . $columns[0];
			$info->vehicle_brand = $columns[8];
			$info->vehicle_type = $columns[3];
			$info->vehicle_id = $columns[6];
			$info->engine_id = $columns[5];
			$info->owner = $columns[2];
			if(substr($columns[7],0,2) != '20')
				$info->enroll_time = str_replace('日','', str_replace(array('年','月'), '-', '20' . substr($columns[7],0,stripos($columns[7],' '))));
			$info->telphone = $columns[4];
			$info->mobile = $columns[4];
		}
		elseif(count($columns) == 11)
		{
			$exist = vehicle_info_js::exists(array('vehicle_id' => $columns[4]));
				
			if($exist)
			{
				$skiped_count ++;
				continue;
			}
				
			//$info->id_type = 'V'; //车架号
			$info->vehicle_number_type = 0;
			$info->vehicle_number = $columns[0];
			$info->vehicle_brand = $columns[1];
			$info->vehicle_type = $columns[2];
			$info->vehicle_id = $columns[3];
			$info->engine_id = $columns[4];
			$info->owner = $columns[5];
			$info->owner_id = $columns[6];
			$info->enroll_time = $columns[7];
			$info->telphone = $columns[8];
			$info->mobile = $columns[9];
			$info->insurance_expire_date = $columns[10];	
		}
		else 
		{
			$exist = vehicle_info_js::exists(array('vehicle_id' => $columns[4]));
			
			if($exist)
			{
				$skiped_count ++;
				continue;
			}
			
			//$info->id_type = 'V'; //车架号
			$info->vehicle_number_type = $columns[0];
			$info->vehicle_number = $columns[1];
			$info->vehicle_brand = $columns[2];
			$info->vehicle_type = $columns[3];
			$info->vehicle_id = $columns[4];
			$info->engine_id = $columns[5];
			if(count($columns) == 12)
			{
				$info->owner = $columns[6];
				$info->owner_id = $columns[7];
				$info->enroll_time = $columns[8];
				$info->telphone = $columns[9];
				$info->mobile = $columns[10];
				$info->insurance_expire_date = $columns[11];
			}
			elseif(count($columns) == 13)
			{
				$info->usage = $columns[6];
				$info->owner = $columns[7];
				$info->owner_id = $columns[8];
				$info->enroll_time = $columns[9];
				$info->telphone = $columns[10];
				$info->mobile = $columns[11];
				$info->insurance_expire_date = $columns[12];
			}
		}
		$info->status = vehicle_info_js::INIT;
		$processed_count ++;
		$info->save();
	}
	catch(Exception $e)
	{
		$skiped_count ++;
		helper::debug_info($e->getMessage(),'exception');
		helper::debug_info($e->getTraceAsString(),'exception');
	}
}

status_reporter::update('total line',$index);
status_reporter::update('skiped line',$skiped_count);
status_reporter::update('processed line',$processed_count);
status_reporter::update('error line',$error_count);
helper::debug_info('total line:' . $index);
helper::debug_info('skiped line:' . $skiped_count);
helper::debug_info('processed line:' . $processed_count);
helper::debug_info('error line:' . $error_count);

status_reporter::end();
fclose($file);
