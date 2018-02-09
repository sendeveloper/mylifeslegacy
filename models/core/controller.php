<?php
/*
 * @Author: Slash Web Design
 */

class Controller extends Core
{
	public $template = '';
	public $content = '';
	public $assets = '';
	
	protected $object;
	protected $method;
	
	function __construct()
	{
		include_once("stripe_info.php");
		global $glob;
		
		parent::__construct();
		
		//$this->helper->p($glob);

		if (isset($glob['act']))
		{
			list($classString, $methodString) = explode("-", $glob['act']);
			
			$this->object = new $classString;
			$this->object->$methodString($glob);
		}
		else
		{
			$glob['sp_key'] = STRIPE_PUBLIC_KEY;
			if (!isset($glob['pag']))
			{
				$glob['pag'] = "cms";
				$glob['title'] = "home";
			}
			
			if (isset($glob['title']))
			{
				$res = $this->db->run("SELECT page_id FROM page WHERE url = '{$glob['title']}'");
				if (count($res) > 0)
				{
					$glob['page_id'] = $res[0]['page_id'];
				}
				else
				{
					$glob['pag'] = $glob['title'];
				}
			}
			
			$glob['pag'] = $this->normalizeObjectName($glob['pag']);
	
			//$this->helper->p($glob);

			if (!file_exists("models/{$glob['pag']}.php") && !file_exists("models/core/{$glob['pag']}.php")) $glob['pag'] = 'NotFound';
		
			$this->object = new $glob['pag'];
		}
	}
	
	public function run()
	{
		global $glob;
		if (!$this->object->hasAccess)
		{
			$this->object = new NotFound(true);
		}
		$this->content = $this->object->fetch();
		$this->template = $this->object->template;

		$this->parseJS($this->object->loadJS, $this->object->loadExternal);
		$this->parseCSS($this->object->loadCSS);
	}
	
	protected function normalizeObjectName($name)
	{
		return str_replace("-", "", $name);
	}
	
	protected function parseJS($scripts, $external)
	{
		foreach ($scripts as $script)
		{
			$url = (DEBUG) ? "assets/js/{$script}?v=" . rand(111, 999) : "assets/js/{$script}";
			$this->assets .= '<script src="' . $url . '"></script>';
		}

		foreach ($external as $script)
		{
			$this->assets .= '<script src="' . $script . '" defer></script>';
		}
	}
	
	protected function parseCSS($styleSheets)
	{
		$out = '';

		foreach ($styleSheets as $ss)
		{
			$url = (DEBUG) ? "assets/css/{$ss}?v=" . rand(111, 999) : "assets/css/{$ss}";
			$this->assets .= '<link rel="stylesheet" media="screen" href="' . $url . '" />';
		}
		
		return $out;
	}
	
	
}
?>