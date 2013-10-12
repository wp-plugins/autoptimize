<?php
/*
Plugin Name: Autoptimize
Plugin URI: http://blog.futtta.be/autoptimize
Description: Optimizes your website, concatenating the CSS and JavaScript code, and compressing it.
Version: 1.7.0
Author: Frank Goossens (futtta)
Author URI: http://blog.futtta.be/
Released under the GNU General Public License (GPL)
http://www.gnu.org/licenses/gpl.txt
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Load config and cache class
include(WP_PLUGIN_DIR.'/autoptimize/classes/autoptimizeConfig.php');
include(WP_PLUGIN_DIR.'/autoptimize/classes/autoptimizeCache.php');

// Plugin constants
define('AUTOPTIMIZE_CACHE_DIR',WP_CONTENT_DIR.'/cache/autoptimize/');
define('AUTOPTIMIZE_CACHE_URL',content_url().'/cache/autoptimize/');
define('AUTOPTIMIZE_CACHE_DELAY',true);
define('WP_ROOT_URL',str_replace('/wp-content','',content_url()));
define('WP_ROOT_DIR',str_replace('/wp-content','',WP_CONTENT_DIR));

// Initialize the cache at least once
$conf = autoptimizeConfig::instance();

/* Check if we're updating, in which case we need to flush the cache
to avoid old versions of aggregated files lingering around */

$autoptimize_version="1.7.0";
$autoptimize_db_version=get_option('autoptimize_version','none');

if ($autoptimize_db_version !== $autoptimize_version) {
	if ($autoptimize_db_version==="none") {
        add_action('admin_notices', 'config_autoptimize_notice');
	} else if (strpos($autoptimize_db_version,"1.6.")!==false) {
		// if user was on version 1.6.x, force advanced options to be shown by default
		update_option('autoptimize_show_adv','1');
		$delete_options=array("autoptimize_cdn_css","autoptimize_cdn_css_url","autoptimize_cdn_js","autoptimize_cdn_js_url","autoptimize_cdn_img","autoptimize_cdn_img_url","autoptimize_css_yui","autoptimize_js_yui");
		foreach ($delete_options as $del_opt) {
			delete_option( $del_opt );
		}
	}
	
	autoptimizeCache::clearall();
	update_option('autoptimize_version',$autoptimize_version);
	$autoptimize_db_version=$autoptimize_version;
}

// Do we gzip when caching?
define('AUTOPTIMIZE_CACHE_NOGZIP',(bool) $conf->get('autoptimize_cache_nogzip'));

// Load translations
$plugin_dir = basename(dirname(__FILE__));
load_plugin_textdomain('autoptimize','wp-content/plugins/'.$plugin_dir.'/localization',$plugin_dir.'/localization');

function config_autoptimize_notice() {
	echo '<div class="updated"><p>';
	_e('Thank you for installing and activating Autoptimize. Please configure it under "Settings" -> "Autoptimize" to start improving your site\'s performance.', 'autoptimize' );
	echo '</p></div>';
}
// Set up the buffering
function autoptimize_start_buffering()
{
	if (!is_feed()) {

	// Config element
	$conf = autoptimizeConfig::instance();
	
	// Load our base class
	include(WP_PLUGIN_DIR.'/autoptimize/classes/autoptimizeBase.php');
	
	// Load extra classes and set some vars
	if($conf->get('autoptimize_html'))
	{
		include(WP_PLUGIN_DIR.'/autoptimize/classes/autoptimizeHTML.php');
		// BUG: new minify-html does not support keeping HTML comments, skipping for now
		// if (defined('AUTOPTIMIZE_LEGACY_MINIFIERS')) {
			@include(WP_PLUGIN_DIR.'/autoptimize/classes/external/php/minify-html.php');
		// } else {
		//	@include(WP_PLUGIN_DIR.'/autoptimize/classes/external/php/minify-2.1.7-html.php');
		// }
	}
	
	if($conf->get('autoptimize_js'))
	{
		include(WP_PLUGIN_DIR.'/autoptimize/classes/autoptimizeScripts.php');
		if (defined('AUTOPTIMIZE_LEGACY_MINIFIERS')) {
			@include(WP_PLUGIN_DIR.'/autoptimize/classes/external/php/jsmin-1.1.1.php');
		} else {
			@include(WP_PLUGIN_DIR.'/autoptimize/classes/external/php/minify-2.1.7-jsmin.php');
		}
		define('CONCATENATE_SCRIPTS',false);
		define('COMPRESS_SCRIPTS',false);
	}
	
	if($conf->get('autoptimize_css'))
	{
		include(WP_PLUGIN_DIR.'/autoptimize/classes/autoptimizeStyles.php');
		if (defined('AUTOPTIMIZE_LEGACY_MINIFIERS')) {
			@include(WP_PLUGIN_DIR.'/autoptimize/classes/external/php/minify-css-compressor.php');
		} else {
			@include(WP_PLUGIN_DIR.'/autoptimize/classes/external/php/yui-php-cssmin-2.4.8-1.php');
		}
		define('COMPRESS_CSS',false);
	}
			
	// Now, start the real thing!
	ob_start('autoptimize_end_buffering');
	}
}

//Action on end - 
function autoptimize_end_buffering($content)
{
	// Config element
	$conf = autoptimizeConfig::instance();
	
	// Choose the classes
	$classes = array();
	if($conf->get('autoptimize_js'))
		$classes[] = 'autoptimizeScripts';
	if($conf->get('autoptimize_css'))
		$classes[] = 'autoptimizeStyles';
	if($conf->get('autoptimize_html'))
		$classes[] = 'autoptimizeHTML';
		
	// Set some options
	$classoptions = array(
		'autoptimizeScripts' => array(
			'justhead' => $conf->get('autoptimize_js_justhead'),
			'forcehead' => $conf->get('autoptimize_js_forcehead'),
			'trycatch' => $conf->get('autoptimize_js_trycatch'),
			'js_exclude' => $conf->get('autoptimize_js_exclude'),
			'cdn_url' => $conf->get('autoptimize_cdn_url')
		),
		'autoptimizeStyles' => array(
			'justhead' => $conf->get('autoptimize_css_justhead'),
			'datauris' => $conf->get('autoptimize_css_datauris'),
			'defer' => $conf->get('autoptimize_css_defer'),
			'css_exclude' => $conf->get('autoptimize_css_exclude'),
			'cdn_url' => $conf->get('autoptimize_cdn_url')
		),
		'autoptimizeHTML' => array(
			'keepcomments' => $conf->get('autoptimize_html_keepcomments')
		)
	);
		
	
	// Run the classes
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
	if( $conf->get('autoptimize_html') || $conf->get('autoptimize_js') || $conf->get('autoptimize_css') || $conf->get('autoptimize_cdn_js') || $conf->get('autoptimize_cdn_css'))
	{
		// Hook to wordpress
		add_action('template_redirect','autoptimize_start_buffering',2);
	}
}

// Do not pollute other plugins
unset($conf);
