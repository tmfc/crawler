<?php
abstract class base_result_saver {
	abstract public function save_result($result);
}

class blackhole_saver extends base_result_saver {
	public function save_result($result) {
		return;
	}
}

class url_saver extends base_result_saver {
	private $source_id;
	
	public function __construct($source_id)	{
		$this->source_id = $source_id;
	}
	public function save_result($result) {
		foreach($result as $url_info) {
			$url = $url_info['url'];
			$md5 = md5($url);
			$title = $url_info['title'];
			$content = content::find_by_sourceid_and_md5($this->source_id,$md5);
			if(!$content)
			{
				$content = new content();
				$content->sourceid = $this->source_id;
				$content->url = $url;
				$content->md5 = $md5;
				$content->title = $title;
				$content->status = content::INIT;
				$content->save();
			}
			
		}
	}
}
