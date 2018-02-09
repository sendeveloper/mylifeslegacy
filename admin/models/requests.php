<?php
/*
 * @Author: Slash Web Design						
 */

class requests
{
	protected $db;
	protected $helper;
	
	function __construct()
	{
		global $db, $helper;
		
		$this->db = $db;
		$this->helper = $helper;
	}
	
	public function approve(&$glob)
	{
		$res = $this->db->run("SELECT name, email, dod FROM request WHERE request_id = {$glob['request_id']}");
		$requester = $res[0];

		$res = $this->db->run("SELECT member_id FROM member WHERE LOWER(username) = '" . strtolower($glob['username']) . "'");
		
		if (count($res) > 0)
		{
			$id = $res[0]['member_id'];
			
			$this->db->update("member", array('released' => 1), "member_id = {$id}");
			$this->db->update("person", array('alive' => 0, 'dod' => $requester['dod']), "member_id = {$id} AND me = 1");

			$r = $this->helper->sendMailTemplate(
				'request.approved',
				array('[NAME]'),
				array($requester['name']),
				array('name' => $requester['name'], 'email' => $requester['email'])
			);
			
			$this->sendPasswords($id);
	
			$this->db->update("request", array('status' => 'approved'), "request_id = {$glob['request_id']}");

			$this->helper->respond(array('error' => 0, 'message' => 'Release request approved', 'mail' => $r));
		}
		else
		{
			$this->helper->respond(array('error' => 1, 'message' => 'Invalid username', 'mail' => null));
		}
	}
	
	public function decline(&$glob)
	{
		$this->db->update("request", array('status' => 'declined'), "request_id = {$glob['request_id']}");
		
		$res = $this->db->run("SELECT m.fname, m.email FROM member m, request r WHERE r.request_id = {$glob['request_id']} AND r.member_id = m.member_id");
		$user = $res[0];
		
		$r = $this->helper->sendMailTemplate(
			'request.declined',
			array('[NAME]'),
			array($user['fname']),
			array('name' => $user['fname'], 'email' => $user['email'])
		);
		
		$this->helper->respond(array('error' => 0, 'message' => 'Release request declined', 'mail' => $r));
	}
	
	public function getItem(&$glob)
	{
		$p = new Parser(get_class($this) . ".item.html");

		if ($glob['id'] !== '')
		{
			$res = $this->db->run("SELECT r.* FROM request r WHERE r.request_id = {$glob['id']}");
			$item = $res[0];
			
			switch ($item['status'])
			{
				case 'pending':
					$actions =
						'<button class="btn btn-ico btn-approve" data-username="' . $item['username'] . '" data-myname="' . $item['firstname'] . ' ' . $item['lastname'] . '"><span class="ico ico-check"></span> Approve</button>' .
						'<button class="btn btn-ico btn-decline"><span class="ico ico-clear"></span> Decline</button>';
					break;
				default:
					$actions = '';
			}
			$myDateTime1 = DateTime::createFromFormat('Y-m-d', $item['dob']);
			if ($myDateTime1 != false && $item['dob']!='0000-00-00')
				$dobStr = $myDateTime1->format('m/d/Y');
			else
				$dobStr = '';

			$myDateTime2 = DateTime::createFromFormat('Y-m-d', $item['dod']);
			if ($myDateTime2 != false && $item['dod']!='0000-00-00')
				$dodStr = $myDateTime2->format('m/d/Y');
			else
				$dodStr = '';


			$p->parseValue(array(
				'PAGE_TITLE'		=>	"Release request for {$item['name']}",
				'PAGE_HINT'			=>	'View and approve or decline this request',
				'NAME'				=>  htmlentities($item['name']),
				'USERNAME'			=>  htmlentities($item['username']),
				'EMAIL'				=>  htmlentities($item['email']),
				'STATUS'			=>  $item['status'],
				'DATE'				=>	date('m/d/Y', $item['date']),
				'DOB'				=>	$dobStr,
				'DOD'				=>	$dodStr,

				'FIRSTNAME'				=>  htmlentities($item['firstname']),
				'MIDDLENAME'				=>  htmlentities($item['middlename']),
				'LASTNAME'				=>  htmlentities($item['lastname']),
				'MOTHERFNAME'				=>  htmlentities($item['m_firstname']),
				'MOTHERLNAME'				=>  htmlentities($item['m_lastname']),
				'FATHERFNAME'				=>  htmlentities($item['f_firstname']),
				'FATHERLNAME'				=>  htmlentities($item['f_lastname']),

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
				(LOWER(r.email) LIKE '%" . strtolower($glob['param']['filter']) . "%') OR
				(LOWER(r.name) LIKE '%" . strtolower($glob['param']['filter']) . "%')
		";
		$sort  = ($glob['param']['sort'] !== '') ? $glob['param']['sort'] : 'r.date';
		$order = ($glob['param']['order'] !== '') ? $glob['param']['order'] : 'desc';
		$index = $glob['param']['offset'];
		$sql = "SELECT r.request_id, r.name, r.email, r.date, r.status FROM request r {$where} ORDER BY {$sort} {$order}";
		
		$res = $this->db->run($sql);
		
		while ((($index - $glob['param']['offset']) < ROWS_PER_PAGE) && ($index < count($res)))
		{
			$r = $res[$index];
			$p->parseBlock(array(
				'ID'			=>	$r['request_id'],
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
	
	protected function sendPasswords($id)
	{
		$res = $this->db->run("SELECT name, email, password FROM member_email WHERE member_id = {$id}");
		foreach ($res as $r)
		{
			$r = $this->helper->sendMailTemplate(
				'request.send.password',
				array('[NAME]', '[PASSWORD]'),
				array($r['name'], $r['password']),
				array('name' => $r['name'], 'email' => $r['email'])
			);
		}
	}
}
?>