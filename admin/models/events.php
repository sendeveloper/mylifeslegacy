<?php
/*
 * @Author: Slash Web Design						
 */

class events
{
	protected $db;
	protected $helper;
	
	function __construct()
	{
		global $db, $helper;
		
		$this->db = $db;
		$this->helper = $helper;
	}

	public function getItem(&$glob)
	{
		$p = new Parser(get_class($this) . ".item.html");

		if ($glob['id'] !== '')
		{
			$res = $this->db->run("SELECT r.request_id, CONCAT(m.fname, ' ', m.lname) AS requester, r.name, r.email, r.username, r.dod, r.date, r.status, r.file FROM member m, request r WHERE r.request_id = {$glob['id']} AND r.member_id = m.member_id");
			$item = $res[0];
			
			switch ($item['status'])
			{
				case 'pending':
					$actions =
						'<button class="btn btn-ico btn-approve" data-username="' . $item['username'] . '"><span class="ico ico-check"></span> Approve</button>' .
						'<button class="btn btn-ico btn-decline"><span class="ico ico-clear"></span> Decline</button>';
					break;
				default:
					$actions = '';
			}

			$p->parseValue(array(
				'PAGE_TITLE'		=>	"Release request for {$item['name']}",
				'PAGE_HINT'			=>	'View and approve or decline this request',
				'REQUESTER'			=>  htmlentities($item['requester']),
				'NAME'				=>  htmlentities($item['name']),
				'USERNAME'			=>  htmlentities($item['username']),
				'EMAIL'				=>  htmlentities($item['email']),
				'STATUS'			=>  $item['status'],
				'DATE'				=>	date('m/d/Y', $item['date']),
				'DOD'				=>	date('m/d/Y', $item['dod']),
				'FILE'				=>	"../" . $item['file'],
				'ACTIONS'			=>	$actions
			));
		}
		
		$html = $p->fetch();
		unset($p);
		
		$this->helper->respond($html, true);
	}
	
	public function getList(&$glob)
	{
		$p = new Parser(get_class($this) . ".list.html");
		$p->defineBlock('item');

		$where = "
			WHERE
				((CONCAT(LOWER(m.fname), ' ', LOWER(m.lname)) LIKE '%" . strtolower($glob['param']['filter']) . "%') OR
				(LOWER(r.email) LIKE '%" . strtolower($glob['param']['filter']) . "%') OR
				(LOWER(r.name) LIKE '%" . strtolower($glob['param']['filter']) . "%')) AND
				(r.member_id = m.member_id)
		";
		$sort  = ($glob['param']['sort'] !== '') ? $glob['param']['sort'] : 'r.date';
		$order = ($glob['param']['order'] !== '') ? $glob['param']['order'] : 'desc';
		$index = $glob['param']['offset'];
		$sql = "SELECT r.request_id, CONCAT(m.fname, ' ', m.lname) AS requester, r.name, r.email, r.date, r.status FROM member m, request r {$where} ORDER BY {$sort} {$order}";
		
		$res = $this->db->run($sql);
		
		while ((($index - $glob['param']['offset']) < ROWS_PER_PAGE) && ($index < count($res)))
		{
			$r = $res[$index];
			$p->parseBlock(array(
				'ID'			=>	$r['request_id'],
				'REQUESTER'		=>	$r['requester'],
				'NAME'			=>	$r['name'],
				'EMAIL'			=>	$r['email'],
				'DATE'			=>	date("d-m-Y H:i", $r['date']),
				'STATUS'		=>	$r['status']
			), 'item');
			$index++;
		}
		
		$p->parseValue(array(
			'PAGE_TITLE'	=>	'Release',
			'FILTER'		=>	$glob['param']['filter'],
			'ORDER'			=>	($order === 'asc') ? 'desc' : 'asc',
			'EMPTY'			=>	(count($res) === 0) ? '<tr><td colspan="7" class="empty">There are no items to display</td></tr>' : '',
			'PAGINATION'	=>	$this->helper->buildPagination(count($res), ROWS_PER_PAGE, $glob['param']['offset']),
			'EXPORT_QUERY'	=>	base64_encode($sql)
		));
		
		$html = $p->fetch();
		unset($p);
		
		$this->helper->respond($html, true);
	}

	public function delete(&$glob)
	{
		foreach ($glob['ids'] as $id)
		{
			$res = $this->db->run("SELECT file FROM request WHERE request_id = {$id}");
			@unlink("../{$res[0]['file']}");
		}

		$glob['ids'] = implode(",", $glob['ids']);
		
		$this->db->run("DELETE FROM request WHERE request_id IN ({$glob['ids']})");
		
		$this->helper->respond(array('error' => 0, 'message' => 'Selected items deleted successfully'));
	}
}
?>