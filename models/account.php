<?php
/*
 * @Author: Slash Web Design
 */

class Account extends Core
{
	public		 $template = 'template-app.html';
	public   $loadExternal = array('https://tour.tourmyapp.com/widgets/widget/59516367bc1fbe6e3d00301c/');
	public        $loadCSS = array('datepicker.css', 'fileuploader.css');
	public         $loadJS = array('datepicker.js', 'fileuploader.js', 'account.js');
	public		$hasAccess = false;
	protected $accessLevel = 3;
	
	function __construct()
	{
		parent::__construct();

		$this->hasAccess = $this->canAccess($this->accessLevel);
	}
	
	public function request(&$ld)
	{
		$myDateTime = DateTime::createFromFormat('m/d/Y', $ld['dod']);
		if ($myDateTime != false && $ld['dod']!='0000-00-00')
			$dobStr = $myDateTime->format('Y-m-d');
		else
			$dobStr = '';

		$this->db->insert("request", array(
			'member_id'		=>	$this->user->id,
			'email'			=>	$ld['email'],
			'name'			=>	$ld['name'],
			'username'		=>	$ld['username'],
			'dod'			=>	$dobStr,
			'date'			=>	time()
		));
		
		$ld['request_id'] = $this->db->lastInsertId();
		
		$newname = str_replace("/temp/", "/documents/", $ld['files']);
		rename($ld['files'], $newname);
		
		if (file_exists($ld['files']))
			@unlink($ld['files']);
		
		$this->db->update("request", array('file' => $newname), "request_id = {$ld['request_id']}");
		
		$ld['error'] = $this->helper->buildMessageBox('success', 'Your request was sent, we will get back to you as soon as possible.');
		return true;
	}
	public function invite(&$ld){
		$res = $this->db->run("SELECT * FROM member WHERE username='" . $ld['username'] . "' and member_id !='" . $this->user->id . "'");
		if (count($res) > 0)
		{
			$this->helper->respond(array(
				'exist'	=>	true,
				'user' => $res[0]
			));
		}else{
			$this->helper->respond(array(
				'exist'	=>	false
			));
		}
	}
	public function update(&$ld)
	{
		$res = $this->db->run("SELECT * FROM member WHERE username='" . $ld['data']['username'] . "' and member_id !='" . $this->user->id . "'");
		if (count($res) > 0)
		{
			unset($ld['data']['username']);
		}
		// else
		// {
		$ld['data']['password'] = $this->helper->encrypt($ld['data']['password']);
		$myDateTime = DateTime::createFromFormat('m/d/Y', $ld['data']['dob']);
		if ($myDateTime != false && $ld['data']['dob']!='0000-00-00')
			$dobStr = $myDateTime->format('Y-m-d');
		else
			$dobStr = '';

		$ld['data']['dob'] = $dobStr;
		// $ld['data']['dob'] = date("Y/m/d",$ld['data']['dob']);

		$this->db->update("member", $ld['data'], "member_id = {$this->user->id}");	
		$this->db->update("person", $ld['person'], "person_id = {$this->user->personId}");	
		$ld['error'] = $this->helper->buildMessageBox("success", "Account details saved");
		
		if (isset($ld['data']['image']) && ($ld['data']['image'] !== '') && file_exists($ld['data']['image']))
		{
			$image = new Resize($ld['data']['image']);
			
			$image->resizeImage(300, 300, 'crop');
			$image->saveImage("uploads/profile/{$this->user->id}.jpg", 100);
			$image->saveImage("uploads/person/{$this->user->personId}.jpg", 100);
			
			// @unlink($ld['data']['image']);
		}
		
		// update person on family tree
		$this->db->update("person", array(
			'fname'		=>	$ld['data']['fname'],
			'mname'		=>	$ld['data']['mname'],
			'lname'		=>	$ld['data']['lname'],
			'email'		=>	$ld['data']['email'],
			'gender'	=>	$ld['data']['gender'],
			'dob'		=>	$ld['data']['dob'],
			'pob'		=>	$ld['data']['pob'],
			'current_city' => $ld['data']['city']
		), "person_id = {$this->user->personId}");

		$this->user = new User($this->user->id);
		
		$ld['error'] = $this->helper->buildMessageBoxWithExclamation('success','Next, you may start building your <a href="family-tree">family tree</a> and continue building your legacy on your <a href="timeline">timeline</a>. Just go to the drop down menu in the top right hand corner of the page to get started, and have fun! This is the story of your life.');
		return true;
		// }
	}
	
	public function fetch()
	{
		global $db;
		$result = $db->run("SELECT * FROM subscription_info LIMIT 1");
		$days = 15;
		if (count($result) > 0)
		{
			$days = $result[0]['trial_days'];
		}

		$p = new Parser("account.html");
		$data = array(
			'ALERT'		=>	$this->helper->prefill('error'),
			'FNAME'		=>	$this->user->fname,
			'MNAME'		=>	$this->user->mname,
			'LNAME'		=>	$this->user->lname,
			'MAIDEN'	=>	$this->user->maiden,
			'MARRIED'	=>	$this->user->married,
			'NICKNAME'	=>	$this->user->nickname,
			'DESCRIPTION'	=>	$this->user->description,
			'USERNAME'	=>	$this->user->username,
			'EMAIL'		=>	$this->user->email,
			'PASSWORD'	=>	$this->user->password,
			'DOB'		=>	$this->user->dobStr,
			'POB'		=>	$this->user->pob,
			'GENDER'	=>	$this->helper->genderDD($this->user->gender),
			'CITY'		=>	$this->user->city,
			'IMAGE'		=>	$this->user->image,
			'FATHERNAME'=>	$this->user->fathername,
			'MOTHERNAME'=>	$this->user->mothername,
			'FATHERMAIDEN' 	=>	$this->user->fmaiden,
			'SUBSCRIPTION'	=>  $this->user->subscripted ? "Enjoy your {$days}-day free trial. Your account will be automatically charged $9.95/month in <b>{$days} days.</b>" : '',
			'SUBSCRIPT_BUTTON'	=> $this->user->subscripted ? '<button class="btn btn-cancel">Cancel subscription</button>': ''
		);
		$p->parseValue($data);
		return $p->fetch();
	}
}
?>