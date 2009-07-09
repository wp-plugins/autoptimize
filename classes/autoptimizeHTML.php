<?php

class autoptimizeHTML extends autoptimizeBase
{
	private $url = '';
	
	//Does nothing
	public function read()
	{
		//Nothing to read for HTML
		return true;
	}
	
	//Joins and optimizes CSS
	public function minify()
	{
		if(class_exists('Minify_HTML'))
		{
			//Remove whitespace
			$this->content = Minify_HTML::minify($this->content);
			return true;
		}
		
		//Didn't minify :(
		return false;
	}
	
	//Does nothing
	public function cache()
	{
		//No cache for HTML
		return true;
	}
	
	//Returns the content
	public function getcontent()
	{
		return $this->content;
	}
}
