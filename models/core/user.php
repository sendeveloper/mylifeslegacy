<?php
/*
 * @Author: Slash Web Design
 */

class User extends Core
{
	public $id;
	public $name;
	public $image;
	public $personId;
		
	function __construct()
	{
		parent::__construct();
		$this->id = (int) $_SESSION['user_id'];
		$res = $this->db->run("SELECT person_id FROM person WHERE member_id = {$this->id} AND me = '1'");
		if (count($res) > 0)
		{
			$this->personId = $res[0]['person_id'];

			$res = $this->db->run("SELECT * FROM member WHERE member_id = {$this->id}");
			foreach ($res[0] as $key => $value)
			{
				switch ($key)
				{
					case 'password': $value = $this->helper->decrypt($value); break;
				}
				
				$this->$key = $value;
			}

			$res = $this->db->run("SELECT description, mname, maiden, married, nickname, fathername, mothername, fmaiden FROM person WHERE person_id = {$this->personId}");
			foreach ($res[0] as $key => $value)
			{
				$this->$key = $value;
			}
			
			$this->name = trim($this->fname . " " . $this->lname);
			$myDateTime = DateTime::createFromFormat('Y-m-d', $this->dob);
			if ($myDateTime != false && $this->dob != '0000-00-00')
				$this->dobStr = $myDateTime->format('m/d/Y');
			else
				$this->dobStr = '';
			// $this->dobStr = ($this->dob !== '0000-00-00') ? date('m/d/Y', $this->dob) : '';
			$this->image = file_exists("uploads/profile/{$this->id}.jpg") ? SITE_URL . "uploads/profile/{$this->id}.jpg?v=" . rand(111, 999) : SITE_URL . "assets/img/profile.na.png";
		}
		
	}
}
?>