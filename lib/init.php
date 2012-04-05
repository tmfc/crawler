<?php
date_default_timezone_set('Asia/Shanghai');
require_once dirname(__FILE__) . '/ActiveRecord.php';
// initialize ActiveRecord
ActiveRecord\Config::initialize(function($cfg)
{
	$cfg->set_model_directory(dirname(__FILE__) . '/models');
	$cfg->set_connections(array('development' => 'mysql://crawler:crawler@127.0.0.1/crawler;charset=utf8'));

	// you can change the default connection with the below
	//$cfg->set_default_connection('production');
});