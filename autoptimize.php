<?php
/*
Plugin Name: Autoptimize
Plugin URI: http://www.turleando.com.ar/autoptimize/
Description: Optimizes your website, concatenating the CSS and JavaScript code, and compressing it.
Version: 1.0
Author: Emilio López
Author URI: http://www.turleando.com.ar/
Released under the GNU General Public License (GPL)
http://www.gnu.org/licenses/gpl.txt
*/

//Load config and cache class
include(WP_PLUGIN_DIR.'/autoptimize/classes/autoptimizeConfig.php');
include(WP_PLUGIN_DIR.'/autoptimize/classes/autoptimizeCache.php');

//Plugin constants
define('AUTOPTIMIZE_CACHE_DIR',WP_CONTENT_DIR.'/cache/autoptimize/');
define('AUTOPTIMIZE_CACHE_URL',WP_CONTENT_URL.'/cache/autoptimize/');
define('AUTOPTIMIZE_CACHE_DELAY',true);


//Load translations
$plugin_dir = basename(dirname(__FILE__));
load_plugin_textdomain('autoptimize','wp-content/plugins/'.$plugin_dir.'/localization',$plugin_dir.'/localization');

//Set up the buffering
function autoptimize_start_buffering()
{
	//Config element
	$conf = autoptimizeConfig::instance();
	
	//Load our base class
	include(WP_PLUGIN_DIR.'/autoptimize/classes/autoptimizeBase.php');
	
	//Load extra classes and set some vars
	if($conf->get('autoptimize_html'))
	{
		include(WP_PLUGIN_DIR.'/autoptimize/classes/autoptimizeHTML.php');	
		@include(WP_PLUGIN_DIR.'/autoptimize/classes/minify-html.php');
	}
	
	if($conf->get('autoptimize_js'))
	{
		include(WP_PLUGIN_DIR.'/autoptimize/classes/autoptimizeScripts.php');
		@include(WP_PLUGIN_DIR.'/autoptimize/classes/jsmin-1.1.1.php');
		define('CONCATENATE_SCRIPTS',false);
		define('COMPRESS_SCRIPTS',false);
	}
	
	if($conf->get('autoptimize_css'))
	{
		include(WP_PLUGIN_DIR.'/autoptimize/classes/autoptimizeStyles.php');
		@include(WP_PLUGIN_DIR.'/autoptimize/classes/minify-css-compressor.php');
		define('COMPRESS_CSS',false);
	}
	
	if($conf->get('autoptimize_cdn_js') || $conf->get('autoptimize_cdn_css'))
	{
		include(WP_PLUGIN_DIR.'/autoptimize/classes/autoptimizeCDN.php');
	}
	
	//Now, start the real thing!
	ob_start('autoptimize_end_buffering');
}

//Action on end - 
function autoptimize_end_buffering($content)
{
	//Config element
	$conf = autoptimizeConfig::instance();
	
	//Choose the classes
	$classes = array();
	if($conf->get('autoptimize_js'))
		$classes[] = 'autoptimizeScripts';
	if($conf->get('autoptimize_css'))
		$classes[] = 'autoptimizeStyles';
	if($conf->get('autoptimize_cdn_js') || $conf->get('autoptimize_cdn_css'))
		$classes[] = 'autoptimizeCDN';
	if($conf->get('autoptimize_html'))
		$classes[] = 'autoptimizeHTML';
		
	//Set some options
	$classoptions = array(
		'autoptimizeScripts' => array(
			'justhead' => $conf->get('autoptimize_js_justhead'),
			'trycatch' => $conf->get('autoptimize_js_trycatch')
		),
		'autoptimizeStyles' => array(
			'justhead' => $conf->get('autoptimize_css_justhead')
		),
		'autoptimizeCDN' => array(
			'js' => $conf->get('autoptimize_cdn_js'),
			'jsurl' => $conf->get('autoptimize_cdn_js_url'),
			'css' => $conf->get('autoptimize_cdn_css'),
			'cssurl' => $conf->get('autoptimize_cdn_css_url')
		),
		'autoptimizeHTML' => array(
			'keepcomments' => $conf->get('autoptimize_html_keepcomments')
		)
	);
		
	
	//Run the classes
	foreach($classes as $name)
	{
		$instance = new $name($content);
		if($instance->read($classoptions[$name]))
		{
			$instance->minify();
			$instance->cache();
			$content = $instance->getcontent();
		}
		unset($instance);
	}
	return $content;
}

if(autoptimizeCache::cacheavail())
{
	$conf = autoptimizeConfig::instance();
	if($conf->get('autoptimize_html') || $conf->get('autoptimize_js') || $conf->get('autoptimize_css') || $conf->get('autoptimize_cdn_js') || $conf->get('autoptimize_cdn_css'))
	{
		//Hook to wordpress
		add_action('template_redirect','autoptimize_start_buffering',2);
	}
}
