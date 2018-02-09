<?php
/*
 * @Author: Slash Web Design						
 */

class webpages
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
			$this->db->update("page", $glob['data'], "page_id = {$glob['id']}");
		}
		else
		{
			$this->db->insert("page", $glob['data']);
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
			$res = $this->db->run("SELECT * FROM page WHERE page_id = {$glob['id']}");
			$item = $res[0];

			$p->parseValue(array(
				'PAGE_TITLE'		=>	$item['name'],
				'PAGE_HINT'			=>	'Modify the fields below to edit this page',
				'NAME'				=>  htmlentities($item['name']),
				'URL'				=>	htmlentities($item['url']),
				'SITE_URL'			=>	SITE_URL,
				'META_TITLE'		=>	htmlentities($item['meta_title']),
				'META_DESCRIPTION'	=>	htmlentities($item['meta_description']),
				'META_KEYWORDS'		=>	htmlentities($item['meta_keywords']),
				'CONTENT'			=>	htmlentities($item['content'])
			));
		}
		else
		{
			$p->parseValue(array(
				'PAGE_TITLE'		=>	'New page',
				'PAGE_HINT'			=>	'Fill in the fields below to set up a new page',
				'NAME'				=>	$helper->prefill('name'),
				'URL'				=>	$helper->prefill('url'),
				'SITE_URL'			=>	SITE_URL,
				'META_TITLE'		=>	$helper->prefill('meta_title'),
				'META_DESCRIPTION'	=>	$helper->prefill('meta_description'),
				'META_KEYWORDS'		=>	$helper->prefill('meta_keywords'),
				'CONTENT'			=>	$helper->prefill('content')
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
				(LOWER(name) LIKE '%" . strtolower($glob['param']['filter']) . "%') OR
				(LOWER(url) LIKE '%" . strtolower($glob['param']['filter']) . "%')
		";
		$sort  = ($glob['param']['sort'] !== '') ? $glob['param']['sort'] : 'name';
		$order = ($glob['param']['order'] !== '') ? $glob['param']['order'] : 'asc';
		$index = $glob['param']['offset'];
		
		$res = $this->db->run("SELECT page_id, name, url FROM page {$where} ORDER BY {$sort} {$order}");
		
		while ((($index - $glob['param']['offset']) < ROWS_PER_PAGE) && ($index < count($res)))
		{
			$r = $res[$index];
			$p->parseBlock(array(
				'ID'	=>	$r['page_id'],
				'TITLE'	=>	$r['name'],
				'URL'	=>	SITE_URL . $r['url']
			), 'item');
			$index++;
		}
		
		$p->parseValue(array(
			'PAGE_TITLE'	=>	'Static website pages',
			'FILTER'		=>	$glob['param']['filter'],
			'ORDER'			=>	($order === 'asc') ? 'desc' : 'asc',
			'EMPTY'			=>	(count($res) === 0) ? '<tr><td colspan="4" class="empty">There are no items to display</td></tr>' : '',
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
		
		$this->db->run("DELETE FROM page WHERE page_id IN ({$glob['ids']})");
		
		$helper->respond(array('error' => 0, 'message' => 'Selected items deleted successfully'));
	}
}
?>