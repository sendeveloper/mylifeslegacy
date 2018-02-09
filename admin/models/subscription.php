<?php
/*
 * @Author: Slash Web Design						
 */

class subscription
{
	var $db;
	
	function __construct()
	{
		global $db;
		
		$this->db = $db;
	}
	
	public function save(&$glob)
	{
		global $helper;
		
		parse_str($glob['data'], $glob['data']);
		
		// if ($glob['id'] !== '')
		// {
		// 	$this->db->update("email", $glob['data'], "email_id = {$glob['id']}");
		// }
		// else
		// {
		// 	$this->db->insert("email", $glob['data']);
		// 	$glob['id'] = $this->db->lastInsertId();
		// }
		if ($glob['data']['id'] !== '')
		{
			$glob['data']['amount']	*= 100;
			$this->db->update("subscription_info", $glob['data'], "id = {$glob['data']['id']}");
		}
		
		$helper->respond(array('error' => 0, 'message' => 'Item data saved successfully', 'id' => $glob['id']));
	}

	public function getItem(&$glob){
		global $site_url, $helper;
		
		$p = new Parser(get_class($this) . ".item.html");

		$res = $this->db->run("SELECT * FROM subscription_info LIMIT 1");
		$item = $res[0];

		$p->parseValue(array(
			'PAGE_TITLE'		=>	'Subscription Information',
			'PAGE_HINT'			=>	'Modify the fields below to edit this subscription info',
			'AMOUNT'			=>  htmlentities($item['amount'])/100,
			'TRIAL_DAYS'		=>	htmlentities($item['trial_days']),
			'TITLE'				=>	htmlentities($item['title']),
			'DESCRIPTION'		=>	htmlentities($item['description']),
			'ID'				=>	htmlentities($item['id']),
		));
		
		$html = $p->fetch();
		unset($p);
		
		$helper->respond($html, true);
	}
	
	public function getList(&$glob)
	{
		$this->getItem($glob);
	}
}
?>