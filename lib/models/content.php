<?php
class content extends ActiveRecord\Model
{
	static $table_name = 'content';
	
	const INIT = 0;
	const CONTENT_CRAWLED = 1;
	const IMPORTED = 2;
}