<?php
abstract class base_result_saver {
	abstract public function save_result($result);
}

class blackhole_saver extends base_result_saver {
	public function save_result($result) {
		return;
	}
}

