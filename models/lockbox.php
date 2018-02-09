<?php
/*
 * @Author: Slash Web Design
 */

class LockBox extends Core
{
	public		 $template = 'template-app.html';
	public   $loadExternal = array('https://tour.tourmyapp.com/widgets/widget/59516216bc1fbe6e3d003019/');
	public        $loadCSS = array();
	public         $loadJS = array('lockbox.js');
	public		$hasAccess = false;
	protected $accessLevel = 3;
	
	function __construct()
	{
		parent::__construct();

		$this->hasAccess = $this->canAccess($this->accessLevel);
	}
	
	public function delete($ld)
	{
		$this->db->run("DELETE FROM member_email WHERE email_id = {$ld['email_id']} AND member_id = {$this->user->id}");
		
		$this->helper->respond(array(
			'status'	=>	true,
			'message'	=>	'Email address was deleted',
			'emails'	=>	$this->__getEmails()
		));
	}

	public function save(&$ld)
	{
		$ld['data']['member_id'] = $this->user->id;
		$ld['data']['password'] = $this->__generate(10, 5);
		
		if ($ld['data']['email_id'] !== '0')
		{
			$this->db->update("member_email", $ld['data'], "email_id = {$ld['data']['email_id']}");
		}
		else
		{
			$this->db->insert("member_email", $ld['data']);
		}
		
		$this->helper->respond(array(
			'status'	=>	true,
			'message'	=>	'Email address was saved',
			'emails'	=>	$this->__getEmails()
		));
	}
	
	public function fetch()
	{
		$p = new Parser("lockbox.html");
		return $p->fetch();
	}
	
	public function get()
	{
		$this->helper->respond(array(
			'status'	=>	true,
			'emails'	=>	$this->__getEmails()
		));
	}
	
	protected function __getEmails()
	{
		$emails = $this->db->run("SELECT * FROM member_email WHERE member_id = {$this->user->id}");
		
		return $emails;
	}
		
	protected function __generate($length = 9, $strength = 0)
	{
		$vowels = 'aeuy';
		$consonants = 'bdghjmnpqrstvz';
		if ($strength & 1)
		{
			$consonants .= 'BDGHJLMNPQRSTVWXZ';
		}
		if ($strength & 2)
		{
			$vowels .= "AEUY";
		}
		if ($strength & 4)
		{
			$consonants .= '23456789';
		}
		if ($strength & 8)
		{
			$consonants .= '@#$%';
		}

		$password = '';
		$alt = time() % 2;
		for ($i = 0; $i < $length; $i++)
		{
			if ($alt == 1)
			{
				$password .= $consonants[(rand() % strlen($consonants))];
				$alt = 0;
			}
			else
			{
				$password .= $vowels[(rand() % strlen($vowels))];
				$alt = 1;
			}
		}
		return $password;
	}
}
?>