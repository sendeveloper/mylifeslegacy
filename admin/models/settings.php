<?php
/*
 * @Author: Slash Web Design						
 */

class settings
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
		
		if ($glob['data']['name'] === 'ADMIN_PASSWORD') $glob['data']['value'] = $helper->encrypt($glob['data']['value']);
		
		if ($glob['id'] !== '')
		{
			$this->db->update("settings", $glob['data'], "settings_id = {$glob['id']}");
		}
		else
		{
			$this->db->insert("settings", $glob['data']);
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
			$res = $this->db->run("SELECT * FROM settings WHERE settings_id = {$glob['id']}");
			$item = $res[0];

			$p->parseValue(array(
				'PAGE_TITLE'		=>	$item['name'],
				'PAGE_HINT'			=>	'Modify the fields below to edit this setting',
				'NAME'				=>  htmlentities($item['name']),
				'VALUE'				=>	($item['name'] === 'ADMIN_PASSWORD') ? $helper->decrypt($item['value']) : $item['value'],
				'DESCRIPTION'		=>	$item['description']
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
				(LOWER(name) LIKE '%" . strtolower($glob['param']['filter']) . "%') OR
				(LOWER(value) LIKE '%" . strtolower($glob['param']['filter']) . "%')
		";
		$sort  = ($glob['param']['sort'] !== '') ? $glob['param']['sort'] : 'name';
		$order = ($glob['param']['order'] !== '') ? $glob['param']['order'] : 'asc';
		$index = $glob['param']['offset'];
		
		$res = $this->db->run("SELECT * FROM settings {$where} ORDER BY {$sort} {$order}");
		
		while ((($index - $glob['param']['offset']) < ROWS_PER_PAGE) && ($index < count($res)))
		{
			$r = $res[$index];
			$p->parseBlock(array(
				'ID'			=>	$r['settings_id'],
				'NAME'			=>	ucfirst(strtolower(str_replace("_", " ", $r['name']))),
				'VALUE'			=>	($r['name'] === 'ADMIN_PASSWORD') ? '<span class="blur">' . $helper->decrypt($r['value']) . '</span>' : $r['value'],
				'DESCRIPTION'	=>	$r['description']
			), 'item');
			$index++;
		}
		
		$p->parseValue(array(
			'PAGE_TITLE'	=>	'System settings',
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