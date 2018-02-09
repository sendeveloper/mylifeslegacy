<?php
/*
 * @Author: Slash Web Design						
 */

class menu
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
		$glob['data']['url'] = $helper->sanitizeURL($glob['data']['url']);
		
		if ($glob['id'] !== '')
		{
			$this->db->update("menu", $glob['data'], "menu_id = {$glob['id']}");
		}
		else
		{
			$this->db->insert("menu", $glob['data']);
			$glob['id'] = $this->db->lastInsertId();
		}
		
		$helper->respond(array('error' => 0, 'message' => 'Item data saved successfully', 'id' => $glob['id']));
	}
	
	public function getItem(&$glob)
	{
		global $helper;
		
		$p = new Parser(get_class($this) . ".item.html");

		if ($glob['id'] !== '')
		{
			$res = $this->db->run("SELECT * FROM menu WHERE menu_id = {$glob['id']}");
			$item = $res[0];

			$p->parseValue(array(
				'PAGE_TITLE'		=>	$item['label'],
				'PAGE_HINT'			=>	'Modify the fields below to edit this menu item',
				'LABEL'				=>  htmlentities($item['label']),
				'URL'				=>	htmlentities($item['url']),
				'PARENT'			=>	$this->menuDD(0, $item['parent_id']),
				'SORT_ORDER'		=>	htmlentities($item['sort_order']),
				'SITE_URL'			=>	SITE_URL,
			));
		}
		else
		{
			$p->parseValue(array(
				'PAGE_TITLE'		=>	'New menu item',
				'PAGE_HINT'			=>	'Fill in the fields below to set up a new menu item',
				'LABEL'				=>	$helper->prefill('label'),
				'URL'				=>	$helper->prefill('url'),
				'SITE_URL'			=>	SITE_URL,
				'SORT_ORDER'		=>	$helper->prefill('sort_order'),
				'PARENT'			=>	$this->menuDD(0, $helper->prefill('parent_id')),
			));
		}
		
		$html = $p->fetch();
		unset($p);
		
		$helper->respond($html, true);
	}
	
	public function getList(&$glob)
	{
		global $helper;
		
		$p = new Parser(get_class($this) . ".list.html");
		$p->defineBlock('item');

		$where = "
			WHERE
				(LOWER(label) LIKE '%" . strtolower($glob['param']['filter']) . "%') OR
				(LOWER(url) LIKE '%" . strtolower($glob['param']['filter']) . "%')
		";
		$sort  = ($glob['param']['sort'] !== '') ? $glob['param']['sort'] : 'sort_order';
		$order = ($glob['param']['order'] !== '') ? $glob['param']['order'] : 'asc';
		$index = $glob['param']['offset'];
		
		$res = $this->db->run("SELECT * FROM menu {$where} ORDER BY parent_id ASC, {$sort} {$order}");
		
		while ((($index - $glob['param']['offset']) < ROWS_PER_PAGE) && ($index < count($res)))
		{
			$r = $res[$index];
			$p->parseBlock(array(
				'ID'		=>	$r['menu_id'],
				'LABEL'		=>	$r['label'],
				'PARENT'	=>	($r['parent_id'] !== '0') ? $this->getParent($r['parent_id']) : 'Top level',
				'SORT_ORDER'=>	$r['sort_order'],
				'URL'		=>	$r['url']
			), 'item');
			$index++;
		}
		
		$p->parseValue(array(
			'PAGE_TITLE'	=>	'Website main menu',
			'FILTER'		=>	$glob['param']['filter'],
			'ORDER'			=>	($order === 'asc') ? 'desc' : 'asc',
			'EMPTY'			=>	(count($res) === 0) ? '<tr><td colspan="6" class="empty">There are no items to display</td></tr>' : '',
			'PAGINATION'	=>	$helper->buildPagination(count($res), ROWS_PER_PAGE, $glob['param']['offset'])
		));
		
		$html = $p->fetch();
		unset($p);
		
		$helper->respond($html, true);
	}

	public function delete(&$glob)
	{
		global $helper;

		$glob['ids'] = implode(",", $glob['ids']);
		
		$this->db->run("DELETE FROM menu WHERE menu_id IN ({$glob['ids']})");
		
		$helper->respond(array('error' => 0, 'message' => 'Selected items deleted successfully'));
	}
	
	private function getParent($id)
	{
		$res = $this->db->run("SELECT label FROM menu WHERE menu_id = {$id}");
		return $res[0]['label'];
	}
	
	private function menuDD($parentId, $selectedId = '', $padding = '')
	{
		$out = ($parentId === 0) ? '<option value="0">Top level</option>' : '';
		$res = $this->db->run("SELECT menu_id, label FROM menu WHERE parent_id = {$parentId} ORDER BY parent_id ASC, sort_order ASC");
		foreach ($res as $r)
		{
			$sel = ($r['menu_id'] === $selectedId) ? 'selected="selected"' : '';
			$out .= '<option value="' . $r['menu_id'] . '" ' . $sel . '>' . $padding . $r['label'] . '</option>';
			$out .= $this->menuDD($r['menu_id'], $selectedId, $padding . '&nbsp;&nbsp;&nbsp;');
		}
		
		return $out;
	}
}
?>