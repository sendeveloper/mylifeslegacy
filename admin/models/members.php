<?php
/*
 * @Author: Slash Web Design						
 */

class members
{
	var $db;
	
	function __construct()
	{
		global $db;
		
		$this->db = $db;
	}
	
	public function export(&$glob)
	{
		global $helper;
		
		$res = $this->db->run(base64_decode($glob['q']));

		$fp = fopen('user.export.csv', 'w');
		fputs($fp, "\xEF\xBB\xBF");

		fputcsv($fp, array('User ID', 'Name', 'Email', 'Status', 'Registration date'));
		foreach ($res as $fields)
		{
			foreach ($fields as $key => $value)
			{
				if ($key === 'date') $fields[$key] = date("d/m/Y H:i:s", $value);
			}
			fputcsv($fp, $fields);
		}

		fclose($fp);
		
		header("Content-type: application/csv;charset=UTF-8");
		header("Content-Disposition: attachment; filename=\"user.export.csv\"");
		
		echo file_get_contents("user.export.csv");
		die();
	}
	
	public function save(&$glob)
	{
		global $helper;
		
		parse_str($glob['data'], $glob['data']);
		$glob['data']['password'] = $helper->encrypt($glob['data']['password']);

		$myDateTime = DateTime::createFromFormat('m/d/Y', $glob['data']['dob']);
		if ($myDateTime != false && $glob['data']['dob']!='0000-00-00')
			$dobStr = $myDateTime->format('Y-m-d');
		else
			$dobStr = '';

		$glob['data']['dob'] = $dobStr;
		
		if ($glob['id'] !== '')
		{
			$this->db->update("member", $glob['data'], "member_id = {$glob['id']}");
		}
		else
		{
			$glob['data']['date'] = time();
			$this->db->insert("member", $glob['data']);
			$glob['id'] = $this->db->lastInsertId();
			
			// set up as person
			$this->db->insert("person", array(
				'member_id'		=>	$glob['id'],
				'fname'			=>	$glob['data']['fname'],
				'lname'			=>	$glob['data']['lname'],
				'gender'		=>	$glob['data']['gender'],
				'me'			=>	1,
				'dob'			=>	$glob['data']['dob'],
				'pob'			=>	$glob['data']['pob'],
				'email'			=>	$glob['data']['email'],
				'alive'			=>	1,
				'placeholder'	=>	0
			));
		}
		
		// process profile image
		if ($glob['data']['image'] !== '')
		{
			$r = new Resize("../" . $glob['data']['image']);
			$r->resizeImage(300, 300, 'crop');
			$r->saveImage("../uploads/profile/{$glob['id']}.jpg", 75);
			
			unset($r);
			@unlink("../" . $glob['data']['image']);
		}
		
		$helper->respond(array('error' => 0, 'message' => 'Item data saved successfully', 'id' => $glob['id']));
	}
	
	public function getItem(&$glob)
	{
		global $site_url, $helper;
		
		$p = new Parser(get_class($this) . ".item.html");

		if ($glob['id'] !== '')
		{
			$res = $this->db->run("SELECT * FROM member WHERE member_id = {$glob['id']}");
			$item = $res[0];
			
			$res = $this->db->run("SELECT person_id FROM person WHERE member_id = {$glob['id']} AND me = 1");
			$personId = $res[0]['person_id'];

			$myDateTime = DateTime::createFromFormat('Y-m-d', $item['dob']);
			if ($myDateTime != false && $item['dob']!='0000-00-00')
				$dobStr = $myDateTime->format('m/d/Y');
			else
				$dobStr = '';

			$p->parseValue(array(
				'PAGE_TITLE'		=>	$item['fname'] . ' ' . $item['lname'],
				'PAGE_HINT'			=>	'Modify the fields below to edit this user',
				'FNAME'				=>  htmlentities($item['fname']),
				'LNAME'				=>  htmlentities($item['lname']),
				'USERNAME'			=>  htmlentities($item['username']),
				'EMAIL'				=>  htmlentities($item['email']),
				'IMAGE'				=>  file_exists("../uploads/profile/{$glob['id']}.jpg") ? "../uploads/profile/{$glob['id']}.jpg?v=" . rand(111, 999) : "../assets/img/profile.na.png",
				'PASSWORD'			=>  $helper->decrypt($item['password']),
				'ACTIVE'			=>  ($item['active'] === '0') ? '' : 'checked="checked"',
				'INACTIVE'			=>  ($item['active'] === '1') ? '' : 'checked="checked"',
				'CITY'				=>	$item['city'],
				'DOB'				=>	$dobStr,
				'POB'				=>	$item['pob'],
				'GENDERS'			=>	$this->buildGenders($item['gender']),
				'EDIT_S'			=>	'',
				'EDIT_E'			=>	'',
				'URL_FT'			=>	SITE_URL . "family-tree/{$personId}/" . $helper->sanitizeURL($item['fname'] . " " . $item['lname']),
				'URL_TL'			=>	SITE_URL . "timeline/{$personId}/" . $helper->sanitizeURL($item['fname'] . " " . $item['lname']),
			));
		}
		else
		{
			$p->parseValue(array(
				'PAGE_TITLE'		=>	'Create new user',
				'PAGE_HINT'			=>	'Modify the fields below to edit this user',
				'FNAME'				=>  $helper->prefill('fname'),
				'LNAME'				=>  $helper->prefill('lname'),
				'USERNAME'			=>  $helper->prefill('username'),
				'EMAIL'				=>  $helper->prefill('email'),
				'IMAGE'				=>  "../assets/img/profile.na.png",
				'PASSWORD'			=>  $helper->prefill('password'),
				'ACTIVE'			=>  'checked="checked"',
				'INACTIVE'			=>  '',
				'CITY'				=>	$helper->prefill('city'),
				'DOB'				=>	'',
				'POB'				=>	$helper->prefill('pob'),
				'GENDERS'			=>	$this->buildGenders($helper->prefill('gender')),
				'EDIT_S'			=>	'<!--',
				'EDIT_E'			=>	'-->',
				'URL_FT'			=>	'',
				'URL_TL'			=>	'',
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
				(CONCAT(LOWER(fname), ' ', LOWER(lname)) LIKE '%" . strtolower($glob['param']['filter']) . "%') OR
				(LOWER(email) LIKE '%" . strtolower($glob['param']['filter']) . "%')
		";
		$sort  = ($glob['param']['sort'] !== '') ? $glob['param']['sort'] : 'name';
		$order = ($glob['param']['order'] !== '') ? $glob['param']['order'] : 'asc';
		$index = $glob['param']['offset'];
		$sql = "SELECT member_id, CONCAT(fname, ' ',  lname) AS name, username, email, active, date FROM member {$where} ORDER BY {$sort} {$order}";
		
		$res = $this->db->run($sql);
		
		while ((($index - $glob['param']['offset']) < ROWS_PER_PAGE) && ($index < count($res)))
		{
			$r = $res[$index];
			$p->parseBlock(array(
				'ID'			=>	$r['member_id'],
				'NAME'			=>	$r['name'],
				'USERNAME'		=>	$r['username'],
				'EMAIL'			=>	$r['email'],
				'REGISTRATION'	=>	date("d-m-Y H:i", $r['date']),
				'STATUS'		=>	($r['active'] === '0') ? 'inactive' : 'active',
			), 'item');
			$index++;
		}
		
		$p->parseValue(array(
			'PAGE_TITLE'	=>	'Website members',
			'FILTER'		=>	$glob['param']['filter'],
			'ORDER'			=>	($order === 'asc') ? 'desc' : 'asc',
			'EMPTY'			=>	(count($res) === 0) ? '<tr><td colspan="6" class="empty">There are no items to display</td></tr>' : '',
			'PAGINATION'	=>	$helper->buildPagination(count($res), ROWS_PER_PAGE, $glob['param']['offset']),
			'EXPORT_QUERY'	=>	base64_encode($sql)
		));
		
		$html = $p->fetch();
		unset($p);
		
		$helper->respond($html, true);
	}

	public function delete(&$glob)
	{
		global $helper;

		$glob['ids'] = implode(",", $glob['ids']);
		
		foreach ($glob['ids'] as $id)
		{
			// clean up relationships
			$this->db->run("DELETE FROM relationship WHERE member_id = {$id}");

			// clean up persons
			$res = $this->db->run("SELECT person_id FROM person WHERE member_id = {$id}");
			foreach ($res as $r)
			{
				@unlink("../uploads/person/{$r['person_id']}.jpg");
			}
			
			$this->db->run("DELETE FROM person WHERE member_id = {$id}");

			// clean up events
			$res = $this->db->run("SELECT * FROM event WHERE member_id = {$id}");
			foreach ($res as $r)
			{
				if ($r['files'] !== '')
				{
					$files = explode(";", $r['files']);
					foreach ($files as $file)
					{
						@unlink("../{$file}");
					}
				}
			}
			
			$this->db->run("DELETE FROM event WHERE member_id = {$id}");
			
			// clean up profile images
			@unlink("../uploads/profile/{$id}.jpg");
		}
		
		$this->db->run("DELETE FROM member WHERE member_id IN ({$glob['ids']})");
		
		$helper->respond(array('error' => 0, 'message' => 'Selected items deleted successfully'));
	}

	private function buildGenders($gender = '')
	{
		global $db;

		$arr = array('male', 'female');

		$out = '<option value="">select gender</option>';
		foreach ($arr as $m)
		{
			$sel = ($gender == $m) ? 'selected="selected"' : '';
			$out .= '<option value="' . $m . '" ' . $sel . '>' . $m . '</option>';
		}

		return $out;
	}
}
?>