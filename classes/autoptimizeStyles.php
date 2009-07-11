<?php
class autoptimizeStyles extends autoptimizeBase
{
	private $css = array();
	private $csscode = array();
	private $url = array();
	
	//Reads the page and collects style tags
	public function read()
	{
		//Save IE hacks
		$this->content = preg_replace('#(<\!--\[if.*\]>.*<\!\[endif\]-->)#Usie',
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
					$media = array('all');
					
					if($path !==false && preg_match('#\.css$#',$path))
					{
						//Good link
						//Get the media
						if(strpos($tag,'media=')!==false)
						{
							$medias = preg_replace('#^.*media=(?:"|\')(.*)(?:"|\').*$#U','$1',$tag);
							$medias = explode(',',$medias);
							$media = array();
							foreach($medias as $elem)
							{
								$media[] = current(explode(' ',trim($elem),2));
							}
						}

						$this->css[] = array($media,$path);
					}else{
						//Link is dynamic (.php etc)
						$tag = '';
					}
				}else{
					//<style>
					preg_match('#<style.*>(.*)</style>#Usmi',$tag,$code);
					$code = preg_replace('#^.*<!\[CDATA\[(?:\s*\*/)?(.*)(?://|/\*)\s*?\]\]>.*$#sm','$1',$code[1]);
					$this->css[] = array('all','INLINE;'.$code);
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
		foreach($this->css as $group)
		{
			list($media,$css) = $group;
			if(preg_match('#^INLINE;#',$css))
			{
				//<style>
				$css = preg_replace('#^INLINE;#','',$css);
				$css = $this->fixurls(ABSPATH.'/index.php',$css);
				if(!isset($this->csscode['all']))
					$this->csscode['all'] = '';
				$this->csscode['all'] .= "\n/*FILESTART*/".$css;
			}else{
				//<link>
				if($css !== false && file_exists($css) && is_readable($css))
				{
					$css = $this->fixurls($css,file_get_contents($css));
					foreach($media as $elem)
					{
						if(!isset($this->csscode[$elem]))
							$this->csscode[$elem] = '';
						$this->csscode[$elem] .= "\n/*FILESTART*/".$css;
					}
				}/*else{
					//Couldn't read CSS. Maybe getpath isn't working?
				}*/
			}
		}
		
		//Check for duplicate code
		$md5list = array();
		$tmpcss = $this->csscode;
		foreach($tmpcss as $media => $code)
		{
			$md5sum = md5($code);
			$medianame = $media;
			foreach($md5list as $med => $sum)
			{
				//If same code
				if($sum === $md5sum)
				{
					//Add the merged code
					$medianame = $med.', '.$media;
					$this->csscode[$medianame] = $code;
					$md5list[$medianame] = $md5list[$med];
					unset($this->csscode[$med], $this->csscode[$media]);
					unset($md5list[$med]);
				}
			}
			$md5list[$medianame] = $md5sum;
		}
		unset($tmpcss);
		
		//Manage @imports, while is for recursive import management
		foreach($this->csscode as &$thiscss)
		{
			//Flag to trigger import reconstitution
			$fiximports = false;
			while(preg_match_all('#@import.*(?:;|$)#Um',$thiscss,$matches))
			{
				foreach($matches[0] as $import)
				{
					$url = trim(preg_replace('#.+((?:https?|ftp)://.*\.css)(?:\s|"|\').+#','$1',$import)," \t\n\r\0\x0B\"'");
					$path = $this->getpath($url);
					if(file_exists($path) && is_readable($path))
					{
						$code = $this->fixurls($path,file_get_contents($path));
						/*$media = preg_replace('#^.*(?:\)|"|\')(.*)(?:\s|;).*$#','$1',$import);
						$media = array_map('trim',explode(' ',$media));
						if(empty($media))
						{
							$thiscss = [...] (Line under)
						}else{
							//media in @import - how should I handle these?
							//TODO: Infinite recursion!
						}*/
						$thiscss = preg_replace('#(/\*FILESTART\*/.*)'.preg_quote($import,'#').'#Us',$code.'$1',$thiscss);
					}else{
						//getpath is not working?
						//Encode so preg_match doesn't see it
						$thiscss = str_replace($import,'/*IMPORT*/'.base64_encode($import).'/*IMPORT*/',$thiscss);
						$fiximports = true;
					}
				}
			}
			
			//Recover imports
			if($fiximports)
			{
				$thiscss = preg_replace('#/\*IMPORT\*/(.*)/\*IMPORT\*/#Use','base64_decode("$1")',$thiscss);
			}
		}
		unset($thiscss);
		
		//$this->csscode has all the uncompressed code now. 
		if(class_exists('Minify_CSS_Compressor'))
		{
			foreach($this->csscode as &$code)
			{
				$code = trim(Minify_CSS_Compressor::process($code));
			}
			unset($code);
			return true;
		}
		
		return false;
	}
	
	//Caches the CSS in uncompressed, deflated and gzipped form.
	public function cache()
	{
		foreach($this->csscode as $media => $code)
		{
			$md5 = md5($code);
			$cache = new autopimizeCache(WP_PLUGIN_DIR.'/autoptimize/cache/',$md5);
			if(!$cache->check())
			{
				//Cache our code
				$cache->cache($code,'text/css');
			}
			$this->url[$media] = WP_PLUGIN_URL.'/autoptimize/cache/'.$cache->getname();
		}
	}
	
	//Returns the content
	public function getcontent()
	{
		//Restore IE hacks
		$this->content = preg_replace('#%%IEHACK%%(.*)%%IEHACK%%#Usie','base64_decode("$1")',$this->content);
		foreach($this->url as $media => $url)
		{
			$this->content = str_replace('</head>','<link type="text/css" media="'.$media.'" href="'.$url.'" rel="stylesheet" /></head>',$this->content);
		}
		return $this->content;
	}
	
	private function fixurls($file,$code)
	{
		$file = str_replace(ABSPATH,'/',$file); //Sth like /wp-content/file.css
		$dir = dirname($file); //Like /wp-content
		
		if(preg_match_all('#url\((.*)\)#Usi',$code,$matches))
		{
			$replace = array();
			foreach($matches[1] as $url)
			{
				//Remove quotes
				$url = trim($url," \t\n\r\0\x0B\"'");
				if(substr($url,0,1)=='/' || preg_match('#^(https?|ftp)://#i',$url))
				{
					//URL is absolute
					continue;
				}else{
					//relative URL. Let's fix it!
					$newurl = get_settings('home').str_replace('//','/',$dir.'/'.$url); //http://yourblog.com/wp-content/../image.png
					$replace[$url] = $newurl;
				}
			}
			
			//Do the replacing here to avoid breaking URLs
			$code = str_replace(array_keys($replace),array_values($replace),$code);
		}
		
		return $code;
	}
}
