<?php
/*
 * @Author: Slash Web Design
 */

class General extends Core
{
	function __construct()
	{
		parent::__construct();
	}
	
	public function search(&$ld)
	{
		$q1 = '%' . strtolower($ld['q'][0]['name']) . '%';		// name
		$q2 = '%' . strtolower($ld['q'][1]['fathername']) . '%';		// fathername
		$q3 = '%' . strtolower($ld['q'][2]['maiden']) . '%';		// mothername
		$q4 = '%' . strtolower($ld['q'][3]['city']) . '%';		// current city

		$myDateTime = DateTime::createFromFormat('m/d/Y', $ld['q'][4]['day']);
		if ($myDateTime != false && $ld['q'][4]['day']!='0000-00-00')
			$dobStr = $myDateTime->format('Y-m-d');
		else
			$dobStr = '0000-00-00';
		$q5 = $dobStr;
		$q6 = $dobStr;

		$q7 = '%' . strtolower($ld['q'][5]['alias']) . '%';		// alias, nickname, married name

		// $q4 = strtotime($ld['q'][3]['day'] . " 00:00:00");		// birthday
		// $q5 = strtotime($ld['q'][3]['day'] . " 23:59:59");
		
		$where_clause = '';
		if ($ld['q'][0]['name'] != '')
		{
			$where_clause = " 
				(LOWER(person.fname) LIKE '{$q1}' OR 
				LOWER(person.mname) LIKE '{$q1}' OR
				LOWER(person.lname) LIKE '{$q1}' OR
				LOWER(person.maiden) LIKE '{$q1}' OR
				LOWER(person.married) LIKE '{$q1}' OR
				LOWER(person.nickname) LIKE '{$q1}' OR
				LOWER(CONCAT(person.fname, ' ', person.lname)) LIKE '{$q1}' OR
				LOWER(CONCAT(person.fname, ' ', person.mname, ' ', person.lname)) LIKE '{$q1}') ";

		}
		if ($ld['q'][1]['fathername'] != '')
		{
			$where_clause .= (strlen($where_clause) == 0 ? "" : " AND ");
			$where_clause .= " LOWER(person.fathername) LIKE '{$q2}' ";
		}
		if ($ld['q'][2]['maiden'] != '')
		{
			$where_clause .= (strlen($where_clause) == 0 ? "" : " AND ");
			$where_clause .= " LOWER(person.maiden) LIKE '{$q3}' ";
		}
		if ($ld['q'][3]['city'] != '')
		{
			$where_clause .= (strlen($where_clause) == 0 ? "" : " AND ");
			$where_clause .= " (LOWER(person.current_city) LIKE '{$q4}' OR (LOWER(member.city) LIKE '{$q4}') AND person.me=1) ";
		}
		if ($ld['q'][4]['day'] != '')
		{
			$where_clause .= (strlen($where_clause) == 0 ? "" : " AND ");
			$where_clause .= " (person.dob = '{$q5}') ";
			// $where_clause .= " (person.dob >= '{$q4}' and person.dob <'{$q5}')";
		}
		if ($ld['q'][5]['alias'] != '')
		{
			$where_clause .= (strlen($where_clause) == 0 ? "" : " AND ");
			$where_clause .= " (LOWER(person.maiden) LIKE '{$q7}' OR 
								LOWER(person.married) LIKE '{$q7}' OR 
								LOWER(person.nickname) LIKE '{$q7}')";
			// $q7
		}
		$query = "
			SELECT person.person_id, person.fname, person.lname, person.mname, person.nickname, person.alive, person.pob, person.me,person.current_city 
			FROM person LEFT JOIN member ON person.member_id=member.member_id
			WHERE $where_clause
		";
		$results = $this->db->run($query);
		if (count($results) == 0)
		{
			$q1 = strtolower($ld['q'][0]['name']);		// name
			$q2 = strtolower($ld['q'][1]['fathername']);		// fathername
			$q3 = strtolower($ld['q'][2]['maiden']);		// maiden
			$q4 = strtolower($ld['q'][3]['city']);		// current city
			
			$myDateTime = DateTime::createFromFormat('m/d/Y', $ld['q'][4]['day']);
			if ($myDateTime != false && $ld['q'][4]['day']!='0000-00-00')
				$dobStr = $myDateTime->format('Y-m-d');
			else
				$dobStr = '0000-00-00';
			$q5 = $dobStr;
			$q6 = $dobStr;

			$q7 = strtolower($ld['q'][5]['alias']);
			// $q4 = strtotime($ld['q'][3]['day'] . " 00:00:00");		// birthday
			// $q5 = strtotime($ld['q'][3]['day'] . " 23:59:59");

			$where_clause = '';
			if ($ld['q'][0]['name'] != '')
			{
				$q1_arr = explode(" ", $q1);
				foreach($q1_arr as $each)
				{
					$where_clause .= (strlen($where_clause) == 0 ? " (" : " OR ");
					$where_clause .= " 
						(SOUNDEX(LOWER(person.fname)) LIKE CONCAT('%',SOUNDEX('{$each}'),'%') OR 
						SOUNDEX(LOWER(person.mname)) LIKE CONCAT('%',SOUNDEX('{$each}'),'%') OR
						SOUNDEX(LOWER(person.lname)) LIKE CONCAT('%',SOUNDEX('{$each}'),'%') OR
						SOUNDEX(LOWER(person.maiden)) LIKE CONCAT('%',SOUNDEX('{$each}'),'%') OR
						SOUNDEX(LOWER(person.married)) LIKE CONCAT('%',SOUNDEX('{$each}'),'%') OR
						SOUNDEX(LOWER(person.nickname)) LIKE CONCAT('%',SOUNDEX('{$each}'),'%') OR
						SOUNDEX(LOWER(CONCAT(person.fname, ' ', person.lname))) LIKE CONCAT('%',SOUNDEX('{$each}'),'%') OR
						SOUNDEX(LOWER(CONCAT(person.fname, ' ', person.mname, ' ', person.lname))) LIKE CONCAT('%',SOUNDEX('{$each}'),'%')) ";
				}
				$where_clause .= ") ";

			}
			if ($ld['q'][1]['fathername'] != '')
			{
				$where_clause .= (strlen($where_clause) == 0 ? "" : " AND ");
				$where_clause .= " SOUNDEX(LOWER(person.fathername)) LIKE CONCAT('%',SOUNDEX('{$q2}'),'%') ";
			}
			if ($ld['q'][2]['maiden'] != '')
			{
				$where_clause .= (strlen($where_clause) == 0 ? "" : " AND ");
				$where_clause .= " SOUNDEX(LOWER(person.maiden)) LIKE CONCAT('%',SOUNDEX('{$q3}'),'%') ";
			}
			if ($ld['q'][3]['city'] != '')
			{
				$where_clause .= (strlen($where_clause) == 0 ? "" : " AND ");
				$where_clause .= " (SOUNDEX(LOWER(person.current_city)) LIKE CONCAT('%',SOUNDEX('{$q4}'),'%') OR (SOUNDEX(LOWER(member.city)) LIKE CONCAT('%',SOUNDEX('{$q4}'),'%') AND person.me=1)) ";
			}
			if ($ld['q'][4]['day'] != '')
			{
				$where_clause .= (strlen($where_clause) == 0 ? "" : " AND ");
				$where_clause .= " (person.dob = '{$q5}') ";
				// $where_clause .= " (person.dob >= '{$q4}' and person.dob <'{$q5}')";
			}
			if ($ld['q'][5]['alias'] != '')
			{
				// $where_clause .= " (LOWER(person.maiden) LIKE '{$q7}' OR 
								// LOWER(person.married) LIKE '{$q7}' OR 
								// LOWER(person.nickname) LIKE '{$q7}')";
				$where_clause .= (strlen($where_clause) == 0 ? "" : " AND ");
				$where_clause .= " (SOUNDEX(LOWER(person.maiden)) LIKE CONCAT('%',SOUNDEX('{$q7}'),'%') OR SOUNDEX(LOWER(person.married)) LIKE CONCAT('%',SOUNDEX('{$q7}'),'%') OR SOUNDEX(LOWER(person.nickname)) LIKE CONCAT('%',SOUNDEX('{$q7}'),'%'))";
			}
			$query = "
				SELECT person.person_id, person.fname, person.lname, person.mname, person.nickname, person.alive, person.pob, person.me,person.current_city 
				FROM person LEFT JOIN member ON person.member_id=member.member_id
				WHERE $where_clause
			";
			$results = $this->db->run($query);
		}
		if (count($results) == 0)
		{
			// $query = "SELECT person.person_id, person.fname, person.lname, person.mname, person.nickname, person.alive, person.pob, person.me,person.current_city 
			// 	FROM person LEFT JOIN member ON person.member_id=member.member_id ORDER BY RAND() LIMIT 1";
			// $results = $this->db->run($query);
		}
		foreach ($results as $key => $r)
		{
			$results[$key]['image'] = file_exists("uploads/person/{$r['person_id']}.jpg") ? "uploads/person/{$r['person_id']}.jpg" : "assets/img/profile.na.png";
			$results[$key]['url'] = SITE_URL . "profile/{$r['person_id']}/" . $this->helper->sanitizeURL($r['fname'] . " " . $r['lname']);
			$results[$key]['parents'] = $this->getParentsNames($r['person_id']);
		}
		
		$this->helper->respond(array(
			'status'	=>	true,
			'results'	=>	$results
		));
	}

	public function contact(&$ld)
	{
		$body = 'Name: ' . $ld['name'] . '<br />Email: ' . $ld['email'] . '<br />---<br />Message: ' . $ld['message'];

		$this->helper->sendMail($body, 'New Contact from MLL website', array('name' => 'Admin', 'email' => ADMIN_EMAIL));

		$ld['error'] = $this->helper->buildMessageBox("success", "Your message has been sent. We will get back to you as soon as possible.");
		return true;
	}
	public function uploadprofile($ld){
		$uploader = new Uploader(array('jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'pdf', 'mp3', 'mp4', 'mov'), 64 * 1024 * 1024);
		$result = $uploader->handleUpload("uploads/temp/");

		if (isset($result["path"]))
		{
			$image = new Resize($result["path"]);
			$image->resizeImage(300, 300, 'crop');
			$image->saveImage("uploads/profile/{$this->user->id}.jpg", 100);
			$image->saveImage("uploads/person/{$this->user->personId}.jpg", 100);

			$result["path"] = "uploads/profile/{$this->user->id}.jpg";
		}

		header("Content-type: application/json; charset=utf-8");
		echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
		die();
	}
	public function uploadfamily($ld){
		$uploader = new Uploader(array('jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'pdf', 'mp3', 'mp4', 'mov'), 64 * 1024 * 1024);
		$result = $uploader->handleUpload("uploads/temp/");
		if (isset($result['path']))
		{
			$r = new Resize($result['path']);
			$r->resizeImage(300, 300, 'crop');

			$r->saveImage($result['path'], 75);
		}

		header("Content-type: application/json; charset=utf-8");
		echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
		die();
	}
	public function upload($ld)
	{
		$uploader = new Uploader(array('jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'pdf', 'mp3', 'mp4', 'mov'), 64 * 1024 * 1024);
		$result = $uploader->handleUpload("uploads/temp/");

		header("Content-type: application/json; charset=utf-8");
		echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
		die();
	}

	protected function getParentsNames($id)
	{
		$parents = array();
		$res = $this->db->run("SELECT b FROM relationship WHERE a = {$id} AND (r = 'mother' OR r = 'father')");
		foreach ($res as $r)
		{
			$parents[] = $r['b'];
		}
		
		if (count($parents) === 0) return '';
		
		$out = '';
		$res = $this->db->run("SELECT fname FROM person WHERE person_id IN (" . implode(",", $parents) . ") AND placeholder = 0");
		foreach ($res as $r)
		{
			if ($out !== '') $out .= ' and ';
			$out .= $r['fname'];
		}
		
		return ($out !== '') ? 'Parents: ' . $out : '';
	}
}
?>