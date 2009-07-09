<?php

class autoptimizeStyles extends autoptimizeBase
{
	private $css = array();
	private $csscode = '';
	private $url = '';
	
	//Reads the page and collects style tags
	public function read()
	{
		//Save IE hacks
		$this->content = preg_replace('#(<\!--\[if IE.*\]>.*<\!\[endif\]-->)#Usie',
			'\'%%IEHACK%%\'.base64_encode("$1").\'%%IEHACK%%\'',$this->content);
		
		//Get <style> and <link>
		if(preg_match_all('#(<style[^>]*>.*</style>)|(<link[^>]*text/css[^>]*>)#Usmi',$this->content,$matches))
		{
			foreach($matches[0] as $tag)
			{
				if(preg_match('#<link.*href=("|\')(.*)("|\')#Usmi',$tag,$source))
				{
					//<link>
					$url = current(explode('?',$source[2],2));
					$path = $this->getpath($url);
					if($path !==false && preg_match('#\.css$#',$path))
					{
						//Good link
						$this->css[] = $path;
					}else{
						//Link is dynamic (.php etc)
						$tag = '';
					}
				}else{
					//<style>
					preg_match('#<style.*>(.*)</style>#Usmi',$tag,$code);
					$code = preg_replace('#.*<!\[CDATA\[(?:\s*\*/)?(.*)(?://|/\*)\s*?\]\]>.*#sm','$1',$code[1]);
					$this->css[] = 'INLINE;'.$code;
				}
				
				//Remove the original style tag
				$this->content = str_replace($tag,'',$this->content);
			}
			
			return true;
		}
	
		//No styles :(
		return false;
	}
	
	//Joins and optimizes CSS
	public function minify()
	{
		foreach($this->css as $css)
		{
			if(preg_match('#^INLINE;#',$css))
			{
				//<style>
				$css = preg_replace('#^INLINE;#','',$css);
				$css = $this->fixurls(ABSPATH.'/index.php',$css);
				$this->csscode .= "\n/*FILESTART*/".$css;
			}else{
				//<link>
				if($css !== false && file_exists($css) && is_readable($css))
				{
					$css = $this->fixurls($css,file_get_contents($css));
					$this->csscode .= "\n/*FILESTART*/".$css;
				}/*else{
					//Couldn't read CSS. Maybe getpath isn't working?
				}*/
			}
		}
		
		//Manage @imports, while is for recursive import management
		while(preg_match_all('#@import (?:url\()?.*(?:\)?)\s*;#',$this->csscode,$matches))
		{
			foreach($matches[0] as $import)
			{
				$url = preg_replace('#.*(?:url\()?(?:"|\')(.*)(?:"|\')(?:\))?.*$#','$1',$import);
				$path = $this->getpath($url);
				if(file_exists($path) && is_readable($path))
				{
					$code = $this->fixurls($path,file_get_contents($path));
					$this->csscode = preg_replace('#(/\*FILESTART\*/.*)'.preg_quote($import,'#').'#Us',$code.'$1',$this->csscode);
				}/*else{
					//getpath is not working?
				}*/
			}
		}
		
		//$this->csscode has all the uncompressed code now. 
		if(class_exists('Minify_CSS_Compressor'))
		{
			$this->csscode = trim(Minify_CSS_Compressor::process($this->csscode));
			return true;
		}
		
		return false;
	}
	
	//Caches the CSS in uncompressed, deflated and gzipped form.
	public function cache()
	{
		$md5 = md5($this->csscode);
		$cache = new autopimizeCache(WP_PLUGIN_DIR.'/autoptimize/cache/',$md5);
		if(!$cache->check())
		{
			//Cache our code
			$cache->cache($this->csscode,'text/css');
		}
		$this->url = WP_PLUGIN_URL.'/autoptimize/cache/'.$cache->getname();
	}
	
	//Returns the content
	public function getcontent()
	{
		//Restore IE hacks
		$this->content = preg_replace('#%%IEHACK%%(.*)%%IEHACK%%#Usie','base64_decode("$1")',$this->content);
		$this->content = str_replace('</head>','<link type="text/css" href="'.$this->url.'" rel="stylesheet" /></head>',$this->content);
		return $this->content;
	}
	
	private function fixurls($file,$code)
	{
		$file = str_replace(ABSPATH,'/',$file); //Sth like /wp-content/file.css
		$dir = dirname($file); //Like /wp-content
		
		if(preg_match_all('#url\((.*)\)#Usi',$code,$matches))
		{
			foreach($matches[1] as $url)
			{
				//Remove quotes
				$url = preg_replace('#^(?:"|\')(.*)(?:"|\')$#','$1',$url);
				if(substr($url,0,1)=='/' || preg_match('#^(https?|ftp)://#i',$url))
				{
					//URL is absolute
					continue;
				}else{
					//relative URL. Let's fix it!
					$newurl = get_settings('home').str_replace('//','/',$dir.'/'.$url); //http://yourblog.com/wp-content/../image.png
					$code = str_replace($url,$newurl,$code);
				}
			}
		}
		
		return $code;
	}
}
