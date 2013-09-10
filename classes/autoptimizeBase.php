<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
abstract class autoptimizeBase
{
	protected $content = '';
	
	public function __construct($content)
	{
		$this->content = $content;
		//Best place to catch errors
	}
	
	//Reads the page and collects tags
	abstract public function read($justhead);
	
	//Joins and optimizes collected things
	abstract public function minify();
	
	//Caches the things
	abstract public function cache();
	
	//Returns the content
	abstract public function getcontent();
	
	//Converts an URL to a full path
	protected function getpath($url)
	{
		$siteurl = site_url();
		if ((strpos($url,'//')===false) && (strpos($url,parse_url($siteurl,PHP_URL_HOST))===false)) {
			$url = $siteurl.$url;
		}
        	$path = str_replace(WP_ROOT_URL,'',$url);
	        if(preg_match('#^(https?|ftp)://#i',$path))
        	{
                	/** External script/css (adsense, etc) */
            		return false;
            	}
        	$path = str_replace('//','/',WP_ROOT_DIR.$path);
        	return $path;
	}
	// logger
	protected function ao_logger($logmsg) {
		$logfile=WP_CONTENT_DIR.'/ao_log.txt';
		$logmsg.="\n";
		file_put_contents($logfile,$logmsg,FILE_APPEND);
	}
	// hide everything between noptimize-comment tags
	protected function hide_noptimize($noptimize_in) {
		if ( preg_match( '/<!--\s?noptimize\s?-->/', $noptimize_in ) ) { 
			$noptimize_out = preg_replace_callback(
				'#<!--\s?noptimize\s?-->.*?<!--\s?/\s?noptimize\s?-->#is',
				create_function(
					'$matches',
					'return "%%NOPTIMIZE%%".base64_encode($matches[0])."%%NOPTIMIZE%%";'
				),
				$noptimize_in
			);
		} else {
			$noptimize_out = $noptimize_in;
		}
		return $noptimize_out;
	}
	
	// unhide noptimize-tags
	protected function restore_noptimize($noptimize_in) {
		if ( preg_match( '/%%NOPTIMIZE%%/', $noptimize_in ) ) { 
			$noptimize_out = preg_replace_callback(
				'#%%NOPTIMIZE%%(.*?)%%NOPTIMIZE%%#is',
				create_function(
					'$matches',
					'return stripslashes(base64_decode($matches[1]));'
				),
				$noptimize_in
			);
		} else {
			$noptimize_out = $noptimize_in;
		}
		return $noptimize_out;
	}
}
