<?php
// author email: ugg.xchj@gmail.com
// 本代码仅供学习参考，不提供任何技术保证。
// 切勿使用本代码用于非法用处，违者后果自负。

class files
{
	public function setFileName($filename)
	{
		$this->filename = $filename;
	}
	public function fserialize($data)
	{
		$this->fileContent = serialize($data);

		if(!$fso=fopen($this->filename,'w'))
		{
			echo '无法打开数据库文件';
			return false;
		}

		if(!flock($fso,LOCK_EX)){//LOCK_NB,排它型锁定
			echo '无法锁定数据库文件';
			return false;
		}

		if(!fwrite($fso,$this->fileContent)){
			echo '无法写入缓存文件';
			return false;
		}

		flock($fso,LOCK_UN);//释放锁定
		fclose($fso);
		return true;
	}

	public function funserialize()
	{
		if(!file_exists($this->filename)){
			echo '无法读取数据库文件';
			return false;
		}
		//return unserialize(file_get_contents($cacheFile));
		$fso = fopen($this->filename, 'r');
		$this->fileContent = fread($fso, filesize($this->filename));
		fclose($fso);
		return unserialize($this->fileContent);
	}

	public function __construct()
	{
		$this->filename = dirname(__FILE__) .  "/keys";
	}
	protected $filename= "";
	protected $fileContent;

}
?>