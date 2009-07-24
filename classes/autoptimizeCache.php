<?php

class autoptimizeCache
{
	private $filename;
	private $mime;
	private $cachedir;
	
	public function __construct($md5)
	{
		$this->cachedir = AUTOPTIMIZE_CACHE_DIR;
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
	
	static function clearall()
	{
		//Cache not available :(
		if(!autoptimizeCache::cacheavail())
			return false;
		
		//Clean the cachedir
		$scan = scandir(AUTOPTIMIZE_CACHE_DIR);
		foreach($scan as $file)
		{
			if(!in_array($file,array('.','..')) && strpos($file,'autoptimize') !== false && is_file(AUTOPTIMIZE_CACHE_DIR.$file))
			{
				@@unlink(AUTOPTIMIZE_CACHE_DIR.$file);
			}
		}
		
		//Do we need to clean WP Super Cache's cache files?
		if(function_exists('wp_cache_clean_cache') && file_exists(WP_CONTENT_DIR.'/wp-cache-config.php'))
		{
			$cacheconfig = file_get_contents(WP_CONTENT_DIR.'/wp-cache-config.php');
			preg_match('#^\$file_prefix\s*=\s*(\'|")(.*)\\1;$#Um',$cacheconfig,$matches);
			$prefix = $matches[2];
			wp_cache_clean_cache($prefix);
			unset($cacheconfig,$prefix);
			echo 'WPSUPERCACHEEEE XD';
		}
		
		return true;
	}
	
	static function stats()
	{
		//Cache not available :(
		if(!autoptimizeCache::cacheavail())
			return 0;

		//Count cached info
		$count = 0;
		$scan = scandir(AUTOPTIMIZE_CACHE_DIR);
		foreach($scan as $file)
		{
			if(!in_array($file,array('.','..')) && strpos($file,'autoptimize') !== false)
			{
				if(is_file(AUTOPTIMIZE_CACHE_DIR.$file) && strpos($file,'none') !== false)
				{
					++$count;
				}/*else{
					//Tricky one... it was a dir or a gzip/deflate file
				}*/
			}
		}
		
		//Tell the number of instances
		return $count;
	}
	
	static function cacheavail()
	{
		if(!defined('AUTOPTIMIZE_CACHE_DIR'))
		{
			//We didn't set a cache
			return false;
		}
		
		//Check for existence
		if(!file_exists(AUTOPTIMIZE_CACHE_DIR))
		{
			@mkdir(AUTOPTIMIZE_CACHE_DIR,0775,true);
			if(!file_exists(AUTOPTIMIZE_CACHE_DIR))
			{
				//Where should we cache?
				return false;
			}
		}
		
		if(!is_writable(AUTOPTIMIZE_CACHE_DIR))
		{
			//How are we supposed to write?
			return false;
		}
		
		//All OK
		return true;
	}
}
