<?php

class autoptimizeConfig
{
	private $config = null;
	static private $instance = null;
	
	private function __construct()
	{
		//Singleton
		if(is_admin())
		{
			//Add the admin page and settings
			add_action('admin_menu',array($this,'addmenu'));
			add_action('admin_init',array($this,'registersettings'));
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
<h2>Autoptimize Settings</h2>

<form method="post" action="options.php">
<?php settings_fields('autoptimize'); ?>
<table class="form-table">

<tr valign="top">
<th scope="row">Optimize HTML Code?</th>
<td><input type="checkbox" name="autoptimize_html" <?php echo get_option('autoptimize_html')?'checked="checked" ':''; ?>/></td>
</tr>
 
<tr valign="top">
<th scope="row">Optimize JavaScript Code?</th>
<td><input type="checkbox" name="autoptimize_js" <?php echo get_option('autoptimize_js')?'checked="checked" ':''; ?>/></td>
</tr>

<tr valign="top">
<th scope="row">Optimize CSS Code? </th>
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
		add_options_page('Autoptimize Options','Autoptimize',8,'autoptimize',array($this,'show'));
	}
	
	public function registersettings()
	{
		register_setting('autoptimize','autoptimize_html');
		register_setting('autoptimize','autoptimize_js');
		register_setting('autoptimize','autoptimize_css');
	}
	
	public function get($key)
	{		
		if(!is_array($this->config))
		{
			//Default config
			$config = array('autoptimize_html' => 1,
				'autoptimize_js' => 1,
				'autoptimize_css' => 1);
			
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
