<?php

class autopimizeCache
{
	private $filename;
	private $mime;
	private $cachedir;
	
	public function __construct($cachedir,$md5)
	{
		$this->cachedir = $cachedir;
		$this->filename = 'autoptimize_'.$md5.'.php';
	}
	
	public function check()
	{
		if(!file_exists($this->cachedir.$this->filename))
		{
			//No cached file, sorry
			return false;
		}
		//Cache exists!
		return true;
	}
	
	public function cache($code,$mime)
	{
		$phpcode = file_get_contents(WP_PLUGIN_DIR.'/autoptimize/config/default.php');
		$phpcode = str_replace(array('%%CONTENT%%','exit;'),array($mime,''),$phpcode);
		file_put_contents($this->cachedir.$this->filename,$phpcode);
		file_put_contents($this->cachedir.$this->filename.'.deflate',gzencode($code,9,FORCE_DEFLATE));
		file_put_contents($this->cachedir.$this->filename.'.gzip',gzencode($code,9,FORCE_GZIP));
		file_put_contents($this->cachedir.$this->filename.'.none',$code);
	}
	
	public function getname()
	{
		return $this->filename;
	}
}
