<?php
class job_status extends ActiveRecord\Model
{
	static $table_name = 'job_status';
	
	const NOT_START = 0;
	const RUNNING = 1;
	const END = 2;
}