<?php
/*
 * @Author: Slash Web Design
 */

class Subscription extends Core
{
	public		 $template = 'template-app.html';
	public   $loadExternal = array('https://checkout.stripe.com/checkout.js');
	public        $loadCSS = array('datepicker.css', 'fileuploader.css', 'subscription.css');
	public         $loadJS = array('datepicker.js', 'fileuploader.js', 'subscription.js', 'client.js');
	public		$hasAccess = true;
	protected $accessLevel = 4;
	
	function __construct()
	{
		parent::__construct();
	}
	public function fetch()
	{
		global $db;
		$sql = "SELECT * FROM subscription_info LIMIT 1";
		$result = $db->run($sql);
		$title = ""; $description = "";
		if (count($result) > 0)
		{
			$title = $result[0]['title'];
			$description = $result[0]['description'];
		}
		$data = array(
			"TITLE" => $title,
			"DESCRIPTION" => $description
		);

		$p = new Parser("subscription.html");
		$p->parseValue($data);
		return $p->fetch();
	}
}
?>