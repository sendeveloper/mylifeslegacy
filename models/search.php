<?php
/*
 * @Author: Slash Web Design
 */

class Search extends Core
{
	public		 $template = 'template-app.html';
	public        $loadCSS = array('datepicker.css');
	public         $loadJS = array('datepicker.js', 'search.js', 'facebook.js', 'client.js');
	public   $loadExternal = array('https://checkout.stripe.com/checkout.js');
	public		$hasAccess = true;
	protected $accessLevel = 4;
	
	function __construct()
	{
		parent::__construct();
	}
	
	public function fetch()
	{
		$p = new Parser("search.html");

		$name = $this->helper->prefill('name');
		$fathername = $this->helper->prefill('fathername');
		$maiden = $this->helper->prefill('maiden');
		$city = $this->helper->prefill('city');
		$day = $this->helper->prefill('day');
		$alias = $this->helper->prefill('alias');
		$p->parseValue(array(
			'ALERT'		=>	$this->helper->prefill('error'),
			'Q1'			=>	isset($name) ? $name : '',
			'Q2'			=>	isset($fathername) ? $fathername : '',
			'Q3'			=>	isset($maiden) ? $maiden : '',
			'Q4'			=>	isset($city) ? $city : '',
			'Q5'			=>	isset($day) ? $day : '',
			'Q6'			=> 	isset($alias) ? $alias : ''
		));

		return $p->fetch();
	}
}
?>