<?php
/*
 *	@Author: Slash Web Design
 */

class Parser
{
	protected $dir = "views/";
	protected $template = "";
	protected $blocks = array();
	
	function __construct($filename)
	{
		$template = file_exists($this->dir . $filename) ? $this->dir . $filename : $filename;
		$this->template = file_get_contents($template);
	}
	
	public function defineBlock($name)
	{
		$this->blocks[$name] = $this->getBlockTemplate($name);
	}
	
	public function parseValue($obj)
	{
		foreach ($obj as $key => $val)
		{
			$this->template = str_replace("{" . $key . "}", $val, $this->template);
		}
	}
	
	public function parseBlock($object, $name)
	{
		array_push($this->blocks[$name]['objects'], $object);
	}
	
	public function fetch()
	{
		foreach ($this->blocks as $name => $block)
		{
			$blockHTML = "";
			foreach ($block['objects'] as $nvp)
			{
				$out = $block['template'];
				foreach ($nvp as $tag => $val)
				{
					$out = str_replace("{" . $tag . "}", $val, $out);
				}
				$blockHTML .= $out;
			}
			
			$this->template = str_replace("{_" . $name . "_template_block}", $blockHTML, $this->template);
		}

		unset($this->blocks);
		return $this->template;
	}
	
	protected function getBlockTemplate($name)
	{
		$startOut = stripos($this->template, "<!-- BEGIN DYNAMIC BLOCK: {$name} -->", 0);
		$startIn  = $startOut + strlen("<!-- BEGIN DYNAMIC BLOCK: {$name} -->");
		$endIn    = stripos($this->template, "<!-- END DYNAMIC BLOCK: {$name} -->", $startIn);
		$endOut   = $endIn + strlen("<!-- END DYNAMIC BLOCK: {$name} -->");
		$length   = $endIn - $startIn;
		
		$out = array(
			'template'	=>	substr($this->template, $startIn, $length),
			'objects'	=>	array()
		);
		
		$outerTemplate = substr($this->template, $startOut, $endOut - $startOut);
		$this->template = str_replace($outerTemplate, "{_" . $name . "_template_block}", $this->template);
		
		return $out;
	}
}
?>