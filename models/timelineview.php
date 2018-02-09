<?php
/*
 * @Author: Slash Web Design
 */

class TimelineView extends Core
{
	public		 $template = 'template-full.html';
	public   $loadExternal = array('https://checkout.stripe.com/checkout.js');
	public        $loadCSS = array('datepicker.css', 'fileuploader.css', 'timeline.css');
	public         $loadJS = array('modernizr.js', 'slideshow.js', 'timeline-view.js', 'facebook.js', 'client.js');
	public		$hasAccess = false;
	protected $accessLevel = 4;
	
	function __construct()
	{
		global $glob;
		
		parent::__construct();
		
		$res = $this->db->run("SELECT released FROM member WHERE member_id = {$glob['id']}");
		if ((count($res) > 0) && ($res[0]['released'] === '1'))
		{
			$this->hasAccess = true;
		}
		else
		{
			$glob['error'] = 'You do not have access to this page';
			$this->hasAccess = false;
		}
	}
	
	public function access($ld)
	{
		$res = $this->db->run("SELECT email_id FROM member_email WHERE password = '{$ld['password']}' AND member_id = {$ld['id']}");
		
		$this->helper->respond(array(
			'status'	=>	(count($res) > 0),
			'message'	=>	(count($res) > 0) ? 'Access granted' : 'Access denied'
		));
	}
	
	public function fetchEvents(&$ld)
	{
		$this->helper->respond(array('success' => true, 'events' => $this->getEvents($ld)));
	}
	
	private function getEvents($ld)
	{
		$id = isset($ld['id']) ? $ld['id'] : $this->user->id;
		$categories = array();
		
		if ($ld['category_id'] !== '-1')
		{
			$categories[] = $ld['category_id'];
		}
		else
		{
			$res = $this->db->run("SELECT category_id FROM category WHERE password = 0");
			foreach ($res as $r)
			{
				$categories[] = $r['category_id'];
			}
		}
		$categories = implode(",", $categories);
		$sql = "SELECT * FROM event WHERE member_id = {$id} AND category_id IN ({$categories}) ORDER BY date DESC";
		$res = $this->db->run($sql);
		foreach ($res as $key => $r)
		{
			$res[$key]['files'] = ($r['files'] !== '') ? explode(';', $r['files']) : array();
		}
		
		return $res;
	}

	public function fetch()
	{
		global $glob;
		
		$p = new Parser("timeline-view.html");
		$p->defineBlock('item');
		$p->defineBlock('item_menu');
		
		$defaultId = 0;
		$total = 0;
		
		$res = $this->db->run("SELECT category_id, name, dc, password FROM category ORDER BY sort_order ASC");
		
		$res[] = array('category_id' => 0, 'name' => 'Uncategorized', 'dc' => 0, 'password' => 0);
		$res[] = array('category_id' => -1, 'name' => 'All', 'dc' => 0, 'password' => 0);
		
		foreach ($res as $c)
		{
			$res2 = $this->db->run("SELECT COUNT(event_id) AS total FROM event WHERE member_id = {$glob['id']} AND category_id = {$c['category_id']}");
			$total += $res2[0]['total'];
			
			$counter = 0;
			if ($c['category_id'] === -1)
			{
				$counter = ($total > 99) ? '99+' : $total;
			}
			else
			{
				$counter = ($res2[0]['total'] > 99) ? '99+' : $res2[0]['total'];
			}
			
			$p->parseBlock(array(
				'ITEM_ID'	=>	$c['category_id'],
				'ITEM_NAME'	=>	$c['name'],
				'ITEM_PASS'	=>	$c['password'],
				'ITEM_COUNT'=>	$counter
			), 'item');
			
			$p->parseBlock(array(
				'ITEM_ID'	=>	$c['category_id'],
				'ITEM_NAME'	=>	$c['name'],
				'ITEM_PASS'	=>	$c['password'],
				'ITEM_COUNT'=>	$counter
			), 'item_menu');
			
			if ($c['dc'] === '1') $defaultId = $c['category_id'];
		}
		
		$p->parseValue(array(
			'DEFAULT_ID'=>	$defaultId,
			'ID'		=>	$glob['id']
		));
		
		return $p->fetch();
	}
}
?>