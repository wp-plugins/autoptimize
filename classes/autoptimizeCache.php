<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class autoptimizeCache
{
	private $filename;
	private $mime;
	private $cachedir;
	private $delayed;
	
	public function __construct($md5,$ext='php')
	{
		$this->cachedir = AUTOPTIMIZE_CACHE_DIR;
		$this->delayed = AUTOPTIMIZE_CACHE_DELAY;
		$this->nogzip = AUTOPTIMIZE_CACHE_NOGZIP;
		if($this->nogzip == false)
			$this->filename = 'autoptimize_'.$md5.'.php';
		else
			$this->filename = 'autoptimize_'.$md5.'.'.$ext;
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
	
	public function retrieve()
	{
		if($this->check())
		{
			if($this->nogzip == false)
				return file_get_contents($this->cachedir.$this->filename.'.none');
			else
				return file_get_contents($this->cachedir.$this->filename);
		}
		return false;
	}
	
	public function cache($code,$mime)
	{
		if($this->nogzip == false)
		{
			$file = ($this->delayed ? 'delayed.php' : 'default.php');
			$phpcode = file_get_contents(WP_PLUGIN_DIR.'/autoptimize/config/'.$file);
			$phpcode = str_replace(array('%%CONTENT%%','exit;'),array($mime,''),$phpcode);
			file_put_contents($this->cachedir.$this->filename,$phpcode);
			file_put_contents($this->cachedir.$this->filename.'.none',$code);
			if(!$this->delayed)
			{
				//Compress now!
				file_put_contents($this->cachedir.$this->filename.'.deflate',gzencode($code,9,FORCE_DEFLATE));
				file_put_contents($this->cachedir.$this->filename.'.gzip',gzencode($code,9,FORCE_GZIP));
			}
		}else{
			//Write code to cache without doing anything else
			file_put_contents($this->cachedir.$this->filename,$code);			
		}
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
				@unlink(AUTOPTIMIZE_CACHE_DIR.$file);
			}
		}
		
		// Do we need to clean any caching plugins cache-files?
		if(function_exists('wp_cache_clear_cache'))	{
			wp_cache_clear_cache(); // wp super cache
		} else if ( function_exists('w3tc_pgcache_flush') ) {
			w3tc_pgcache_flush(); //w3 total cache
		} else if ( function_exists('hyper_cache_invalidate') ) {
			hyper_cache_invalidate(); // hypercache
		} else if ( function_exists('wp_fast_cache_bulk_delete_all') ) {
			wp_fast_cache_bulk_delete_all(); // wp fast cache
		} else if (class_exists("WpFastestCache")) {
                	$wpfc = new WpFastestCache(); // wp fastest cache
                	$wpfc -> deleteCache();
		} else if ( class_exists("c_ws_plugin__qcache_purging_routines") ) {
			c_ws_plugin__qcache_purging_routines::purge_cache_dir(); // quick cache
		} else if(file_exists(WP_CONTENT_DIR.'/wp-cache-config.php') && function_exists('prune_super_cache')){
			// fallback for WP-Super-Cache
			global $cache_path;
			prune_super_cache($cache_path.'supercache/',true);
			prune_super_cache($cache_path,true);
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
				if(is_file(AUTOPTIMIZE_CACHE_DIR.$file))
				{
					if(AUTOPTIMIZE_CACHE_NOGZIP && (strpos($file,'.js') !== false || strpos($file,'.css') !== false))
					{
						$count++;
					}elseif(!AUTOPTIMIZE_CACHE_NOGZIP && strpos($file,'.none') !== false){
						$count++;
					}/*else{
						//Tricky one... it was a dir or a gzip/deflate file
					}*/
				}
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

		/** write index.html here to avoid prying eyes */
		$indexFile=AUTOPTIMIZE_CACHE_DIR.'/index.html';
		if(!is_file($indexFile)) {
			@file_put_contents($indexFile,'<html><body>Generated by <a href="http://wordpress.org/extend/plugins/autoptimize/">Autoptimize</a></body></html>');
		}
		
		/** write .htaccess here to overrule wp_super_cache */
		$htAccess=AUTOPTIMIZE_CACHE_DIR.'/.htaccess';
		if(!is_file($htAccess)) {
			@file_put_contents($htAccess,
			'<IfModule mod_headers.c>
	Header set Vary "Accept-Encoding"
	Header set Cache-Control "max-age=10672000, must-revalidate"
</IfModule>
<IfModule mod_expires.c>
	ExpiresActive On
	ExpiresByType text/css A30672000
	ExpiresByType text/javascript A30672000
	ExpiresByType application/javascript A30672000
</IfModule>');
		}

		//All OK
		return true;
	}
}
