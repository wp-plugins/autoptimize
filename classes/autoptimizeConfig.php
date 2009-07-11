<?php

class autoptimizeConfig
{
	private $config = null;
	static private $instance = null;
	
	//Singleton: private construct
	private function __construct()
	{
		if(is_admin())
		{
			//Add the admin page and settings
			add_action('admin_menu',array($this,'addmenu'));
			add_action('admin_init',array($this,'registersettings'));
			//Set meta info
			if(function_exists('plugin_row_meta'))
			{
				//2.8+
				add_filter('plugin_row_meta',array($this,'setmeta'),10,2);
			}elseif(function_exists('post_class')){
				//2.7
				$plugin = plugin_basename(WP_PLUGIN_DIR.'/autoptimize/autoptimize.php');
				add_filter('plugin_action_links_'.$plugin,array($this,'setmeta'));
			}
		}
	}
	
	static public function instance()
	{
		//Only one instance
		if (self::$instance == null)
		{
			self::$instance = new autoptimizeConfig();
		}
		
		return self::$instance;
    }
	
	public function show()
	{
?>
<div class="wrap">
<h2><?php _e('Autoptimize Settings','autoptimize'); ?></h2>

<form method="post" action="options.php">
<?php settings_fields('autoptimize'); ?>
<table class="form-table">

<tr valign="top">
<th scope="row"><?php _e('Optimize HTML Code?','autoptimize'); ?></th>
<td><input type="checkbox" name="autoptimize_html" <?php echo get_option('autoptimize_html')?'checked="checked" ':''; ?>/></td>
</tr>
 
<tr valign="top">
<th scope="row"><?php _e('Optimize JavaScript Code?','autoptimize'); ?></th>
<td><input type="checkbox" name="autoptimize_js" <?php echo get_option('autoptimize_js')?'checked="checked" ':''; ?>/></td>
</tr>

<tr valign="top">
<th scope="row"><?php _e('Optimize CSS Code?','autoptimize'); ?></th>
<td><input type="checkbox" name="autoptimize_css" <?php echo get_option('autoptimize_css')?'checked="checked" ':''; ?>/></td>
</tr>

</table>

<p class="submit">
<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
</p>

</form>
</div>
<?php
	}
	
	public function addmenu()
	{
		add_options_page(__('Autoptimize Options','autoptimize'),'Autoptimize',8,'autoptimize',array($this,'show'));
	}
	
	public function registersettings()
	{
		register_setting('autoptimize','autoptimize_html');
		register_setting('autoptimize','autoptimize_js');
		register_setting('autoptimize','autoptimize_css');
	}
	
	public function setmeta($links,$file=null)
	{
		//Inspired on http://wpengineer.com/meta-links-for-wordpress-plugins/
		
		//Do it only once - saves time
		static $plugin;
		if(empty($plugin))
			$plugin = plugin_basename(WP_PLUGIN_DIR.'/autoptimize/autoptimize.php');
		
		if($file===null)
		{
			//2.7
			$settings_link = sprintf('<a href="options-general.php?page=autoptimize">%s</a>', __('Settings'));
			array_unshift($links,$settings_link);
		}else{
			//2.8
			//If it's us, add the link
			if($file === $plugin)
			{
				$newlink = array(sprintf('<a href="options-general.php?page=autoptimize">%s</a>',__('Settings')));
				$links = array_merge($links,$newlink);
			}
		}
		
		return $links;
	}
	
	public function get($key)
	{		
		if(!is_array($this->config))
		{
			//Default config
			$config = array('autoptimize_html' => 0,
				'autoptimize_js' => 0,
				'autoptimize_css' => 0);
			
			//Override with user settings
			if(get_option('autoptimize_html')!==false)
				$config['autoptimize_html'] = get_option('autoptimize_html');
			if(get_option('autoptimize_js')!==false)
				$config['autoptimize_js'] = get_option('autoptimize_js');
			if(get_option('autoptimize_css')!==false)
				$config['autoptimize_css'] = get_option('autoptimize_css');
			
			//Save for next question
			$this->config = $config;
		}
		
		if(isset($this->config[$key]))
			return $this->config[$key];
		
		return false;
	}
}
