<?php
/*
Plugin Name: Autoptimize
Plugin URI: http://www.turleando.com.ar/
Description: Optimiza tu sitio web, uniendo el CSS y JavaScript, y comprimi&eacute;ndolo.
Version: 0.2
Author: Emilio López
Author URI: http://www.turleando.com.ar/
Released under the GNU General Public License (GPL)
http://www.gnu.org/licenses/gpl.txt
*/

//Load config class
@include(WP_PLUGIN_DIR.'/autoptimize/classes/autoptimizeConfig.php');

//Set up the buffering
function autoptimize_start_buffering()
{
	//Pre-2.6 compatibility
	if(!defined('WP_PLUGIN_URL'))
		define('WP_PLUGIN_URL',WP_CONTENT_URL.'/plugins');
	if(!defined('WP_PLUGIN_DIR'))
		define('WP_PLUGIN_DIR',WP_CONTENT_DIR.'/plugins');
	
	//Config element
	$conf = autoptimizeConfig::instance();
	
	//Load our always-on classes
	@include(WP_PLUGIN_DIR.'/autoptimize/classes/autoptimizeBase.php');
	@include(WP_PLUGIN_DIR.'/autoptimize/classes/autoptimizeCache.php');
	
	//Load extra classes and set some vars
	if($conf->get('autoptimize_html'))
	{
		@include(WP_PLUGIN_DIR.'/autoptimize/classes/autoptimizeHTML.php');	
		@include(WP_PLUGIN_DIR.'/autoptimize/classes/minify-html.php');
	}
	
	if($conf->get('autoptimize_js'))
	{
		@include(WP_PLUGIN_DIR.'/autoptimize/classes/autoptimizeScripts.php');
		@include(WP_PLUGIN_DIR.'/autoptimize/classes/jsmin-1.1.1.php');
		define('CONCATENATE_SCRIPTS',false);
		define('COMPRESS_SCRIPTS',false);
	}
	
	if($conf->get('autoptimize_css'))
	{
		@include(WP_PLUGIN_DIR.'/autoptimize/classes/autoptimizeStyles.php');
		@include(WP_PLUGIN_DIR.'/autoptimize/classes/minify-css-compressor.php');
		define('COMPRESS_CSS',false);
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
	if($conf->get('autoptimize_html'))
		$classes[] = 'autoptimizeHTML';
	
	//Run the classes
	foreach($classes as $name)
	{
		$instance = new $name($content);
		if($instance->read())
		{
			$instance->minify();
			$instance->cache();
			$content = $instance->getcontent();
		}
		unset($instance);
	}
	return $content;
}


$conf = autoptimizeConfig::instance();
if($conf->get('autoptimize_html') || $conf->get('autoptimize_js') || $conf->get('autoptimize_css'))
{
	//Hook to wordpress
	add_action('template_redirect','autoptimize_start_buffering',2);
}
