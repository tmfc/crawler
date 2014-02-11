<?php
require_once dirname(__FILE__)."/../../lib/init.php";
require_once dirname(__FILE__)."/../../lib/helper.php";
require_once dirname(__FILE__)."/../../lib/status_reporter.php";

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
		//var_dump($columns);
		//die;
		//echo count($columns);
		if(!in_array(count($columns), array(14)))
		{
			helper::debug_info("format not match");
			$skiped_count ++;
			continue;
		}

		$info = new vehicle_info_js();
		
		$exist = vehicle_info_js::exists(array('vehicle_id' => $columns[5]));
			
		if($exist)
		{
			$skiped_count ++;
			continue;
		}
		
		$info->vehicle_number_type = $columns[0];
		$info->vehicle_number = $columns[1];
		$info->vehicle_brand = $columns[2];
		$info->vehicle_type = $columns[3];
		$info->vehicle_id = $columns[5];
		$info->engine_id = $columns[6];
		$info->owner = $columns[8];
		$info->owner_id = $columns[7];
		$info->enroll_time = $columns[12];
		$info->telphone = $columns[10];
		$info->mobile = $columns[11];
		$info->insurance_expire_date = $columns[13];
		
		
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
