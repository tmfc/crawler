<?php
if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50300)
	die('PHP ActiveRecord requires PHP 5.3 or higher');

define('PHP_ACTIVERECORD_VERSION_ID','1.0');

require dirname(__FILE__) . '/db/Singleton.php';
require dirname(__FILE__) . '/db/Config.php';
require dirname(__FILE__) . '/db/Utils.php';
require dirname(__FILE__) . '/db/DateTime.php';
require dirname(__FILE__) . '/db/Model.php';
require dirname(__FILE__) . '/db/Table.php';
require dirname(__FILE__) . '/db/ConnectionManager.php';
require dirname(__FILE__) . '/db/Connection.php';
require dirname(__FILE__) . '/db/SQLBuilder.php';
require dirname(__FILE__) . '/db/Reflections.php';
require dirname(__FILE__) . '/db/Inflector.php';
require dirname(__FILE__) . '/db/CallBack.php';
require dirname(__FILE__) . '/db/Exceptions.php';

spl_autoload_register('activerecord_autoload');

function activerecord_autoload($class_name)
{
	$path = ActiveRecord\Config::instance()->get_model_directory();
	$root = realpath(isset($path) ? $path : '.');

	if (($namespaces = ActiveRecord\get_namespaces($class_name)))
	{
		$class_name = array_pop($namespaces);
		$directories = array();

		foreach ($namespaces as $directory)
			$directories[] = $directory;

		$root .= DIRECTORY_SEPARATOR . implode($directories, DIRECTORY_SEPARATOR);
	}

	$file = "$root/$class_name.php";

	if (file_exists($file))
		require $file;
}
?>
