<?php
date_default_timezone_set('PRC');
require_once dirname(__FILE__) . '/ActiveRecord.php';
// initialize ActiveRecord
ActiveRecord\Config::initialize(function($cfg)
{
	$cfg->set_model_directory(dirname(__FILE__) . '/models');
	$cfg->set_connections(array('development' => 'mysql://crawler:crawler@10.211.55.9/crawler?charset=utf8','testing' => 'mysql://crawler_test:crawler_test@10.211.55.9/crawler_test?charset=utf8','production' => 'mysql://crawler:yby7VYZFx9sTberS@localhost/crawler?charset=utf8'));

	// you can change the default connection with the below
// 	$cfg->set_default_connection('production');
	$cfg->set_default_connection('testing');
});

job_status::connection()->query("set names 'utf8'");