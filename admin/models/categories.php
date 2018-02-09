<?php
/*
 * @Author: Slash Web Design						
 */

class categories
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
		$glob['data']['dc'] = isset($glob['data']['dc']) ? 1 : 0;
		$glob['data']['password'] = isset($glob['data']['password']) ? 1 : 0;
		
		if ($glob['id'] !== '')
		{
			$this->db->update("category", $glob['data'], "category_id = {$glob['id']}");
		}
		else
		{
			$this->db->insert("category", $glob['data']);
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
			$res = $this->db->run("SELECT * FROM category WHERE category_id = {$glob['id']}");
			$item = $res[0];

			$p->parseValue(array(
				'PAGE_TITLE'		=>	$item['name'],
				'PAGE_HINT'			=>	'Modify the fields below to edit this category',
				'NAME'				=>  $item['name'],
				'SORT_ORDER'		=>  $item['sort_order'],
				'DC'				=>	($item['dc'] === '1') ? 'checked="checked"' : '',
				'PASSWORD'			=>	($item['password'] === '1') ? 'checked="checked"' : ''
			));
		}
		else
		{
			$p->parseValue(array(
				'PAGE_TITLE'		=>	'Create new category',
				'PAGE_HINT'			=>	'Modify the fields below to edit this category',
				'NAME'				=>  $helper->prefill('name'),
				'SORT_ORDER'		=>  $helper->prefill('sort_order'),
				'DC'				=>	'',
				'PASSWORD'			=>	''
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

		$where = "WHERE LOWER(name) LIKE '%" . strtolower($glob['param']['filter']) . "%'";
		$sort  = ($glob['param']['sort'] !== '') ? $glob['param']['sort'] : 'sort_order';
		$order = ($glob['param']['order'] !== '') ? $glob['param']['order'] : 'asc';
		$index = $glob['param']['offset'];
		$sql = "SELECT * FROM category {$where} ORDER BY {$sort} {$order}";
		
		$res = $this->db->run($sql);
		
		while ((($index - $glob['param']['offset']) < ROWS_PER_PAGE) && ($index < count($res)))
		{
			$r = $res[$index];
			$p->parseBlock(array(
				'ID'			=>	$r['category_id'],
				'NAME'			=>	($r['dc'] === '1') ? $r['name'] . ' (default)' : $r['name'],
				'SORT_ORDER'	=>	$r['sort_order'],
				'PASSWORD'		=>	($r['password'] === '1') ? 'Yes' : 'No'
			), 'item');
			$index++;
		}
		
		$p->parseValue(array(
			'PAGE_TITLE'	=>	'Timeline categories',
			'FILTER'		=>	$glob['param']['filter'],
			'ORDER'			=>	($order === 'asc') ? 'desc' : 'asc',
			'EMPTY'			=>	(count($res) === 0) ? '<tr><td colspan="5" class="empty">There are no items to display</td></tr>' : '',
			'PAGINATION'	=>	$helper->buildPagination(count($res), ROWS_PER_PAGE, $glob['param']['offset'])
		));
		
		$html = $p->fetch();
		unset($p);
		
		$helper->respond($html, true);
	}

	public function delete(&$glob)
	{
		global $helper;

		$ids = implode(",", $glob['ids']);
		
		foreach ($glob['ids'] as $id)
		{
			// reset events to 0
			$this->db->update("event", array('category_id' => 0), "category_id = {$id}");
		}
		
		$this->db->run("DELETE FROM category WHERE category_id IN ({$ids})");
		
		$helper->respond(array('error' => 0, 'message' => 'Selected items deleted successfully'));
	}
}
?>