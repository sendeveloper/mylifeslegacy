<?php
/*
 * @Author: Slash Web Design						
 */

class notifications
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
		
		if ($glob['id'] !== '')
		{
			$this->db->update("email", $glob['data'], "email_id = {$glob['id']}");
		}
		else
		{
			$this->db->insert("email", $glob['data']);
			$glob['id'] = $this->db->lastInsertId();
		}
		
		$helper->respond(array('error' => 0, 'message' => 'Item data saved successfully', 'id' => $glob['id']));
	}
	
	public function getItem(&$glob)
	{
		global $site_url, $helper;
		
		$p = new Parser(get_class($this) . ".item.html");

		if ($glob['id'] !== '')
		{
			$res = $this->db->run("SELECT * FROM email WHERE email_id = {$glob['id']}");
			$item = $res[0];

			$p->parseValue(array(
				'PAGE_TITLE'		=>	$item['code'],
				'PAGE_HINT'			=>	'Modify the fields below to edit this notification',
				'CODE'				=>  htmlentities($item['code']),
				'SUBJECT'			=>	htmlentities($item['subject']),
				'FROM_NAME'			=>	htmlentities($item['from_name']),
				'FROM_ADDRESS'		=>	htmlentities($item['from_address']),
				'CONTENT'			=>	$item['content'],
			));
		}
		
		$html = $p->fetch();
		unset($p);
		
		$helper->respond($html, true);
	}
	
	public function getList(&$glob)
	{
		global $site_url, $helper;
		
		$p = new Parser(get_class($this) . ".list.html");
		$p->defineBlock('item');

		$where = "
			WHERE
				(LOWER(code) LIKE '%" . strtolower($glob['param']['filter']) . "%') OR
				(LOWER(subject) LIKE '%" . strtolower($glob['param']['filter']) . "%')
		";
		$sort  = ($glob['param']['sort'] !== '') ? $glob['param']['sort'] : 'code';
		$order = ($glob['param']['order'] !== '') ? $glob['param']['order'] : 'asc';
		$index = $glob['param']['offset'];
		
		$res = $this->db->run("SELECT * FROM email {$where} ORDER BY {$sort} {$order}");
		
		while ((($index - $glob['param']['offset']) < ROWS_PER_PAGE) && ($index < count($res)))
		{
			$r = $res[$index];
			$p->parseBlock(array(
				'ID'		=>	$r['email_id'],
				'CODE'		=>	$r['code'],
				'SUBJECT'	=>	$r['subject']
			), 'item');
			$index++;
		}
		
		$p->parseValue(array(
			'PAGE_TITLE'	=>	'System notifications',
			'FILTER'		=>	$glob['param']['filter'],
			'ORDER'			=>	($order === 'asc') ? 'desc' : 'asc',
			'EMPTY'			=>	(count($res) === 0) ? '<tr><td colspan="6" class="empty">There are no items to display</td></tr>' : '',
			'PAGINATION'	=>	$helper->buildPagination(count($res), ROWS_PER_PAGE, $glob['param']['offset'])
		));
		
		$html = $p->fetch();
		unset($p);
		
		$helper->respond($html, true);
	}
}
?>