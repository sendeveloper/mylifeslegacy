<?php
/*
 * @Author: Slash Web Design
 */

class CMS extends Core
{
	public		 $template = 'template-app.html';
	public        $loadCSS = array();
	public         $loadJS = array('facebook.js', 'client.js');
	public   $loadExternal = array('https://checkout.stripe.com/checkout.js');
	public		$hasAccess = true;
	protected $accessLevel = 4;
	
	function __construct()
	{
		parent::__construct();
	}
	
	public function fetch()
	{
		global $glob;
		
		$res = $this->db->run("SELECT * FROM page WHERE page_id = {$glob['page_id']}");
		
		if ($res[0]['home'] === '1') $this->template = 'template-home.html';
		
		return $this->parseDynamicBlocks($res[0]['content']);
	}

	protected function parseDynamicBlocks($str)
	{
		return
			str_replace(
				array('[CONTACT_FORM]'),
				array($this->contact_form()),
				$str
			);
	}
	
	protected function contact_form()
	{
		$p = new Parser("contact.html");

		$p->parseValue(array(
			'ALERT'	=>	$this->helper->prefill('error')
		));

		return $p->fetch();
	}
}
?>