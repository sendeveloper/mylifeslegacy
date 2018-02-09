<?php
/*
 * @Author: Slash Web Design
 */

class Profile extends Core
{
	public		 $template = 'template-app.html';
	public        $loadCSS = array();
	public         $loadJS = array('profile.js', 'facebook.js', 'client.js');
	public   $loadExternal = array('https://checkout.stripe.com/checkout.js');
	public		$hasAccess = false;
	protected $accessLevel = 4;
	
	function __construct()
	{
		parent::__construct();
		
		$this->hasAccess = $this->canAccess($this->accessLevel);
	}
	
	public function fetch()
	{
		global $glob;
		
		$p = new Parser("profile.html");
		$ft = '';
		$tl = '';
		
		$res = $this->db->run("SELECT person.*, member.city FROM person LEFT JOIN member on person.member_id=member.member_id WHERE person_id = {$glob['id']}");
		$profile = $res[0];
		
		$ft = "family-tree/{$profile['person_id']}/" . $this->helper->sanitizeURL($profile['fname'] . ' ' . $profile['lname']);
		
		if ($profile['me'] === '1')
		{
			$res = $this->db->run("SELECT released FROM member WHERE member_id = {$profile['member_id']}");
			if ($res[0]['released'] === '1')
			{
				$tl = "timeline/{$profile['member_id']}/" . $this->helper->sanitizeURL($profile['fname'] . ' ' . $profile['lname']);
			}
		}

		$gender_arr = array('m' => 'male', 'f' => 'female');
		$city = ($profile['me'] == 1) ? $profile['city'] : $profile['current_city'];

		$dobStr = DateTime::createFromFormat("Y-m-d", $profile['dob']);
		if ($dobStr!=false && $profile['dob']!='0000-00-00'){
			$cur_dob = $dobStr->format("Y");
		}
		else
			$cur_dob = '';


		if ($profile['gender'] == 'f')
			$fullname = $profile['fname'] . ' ' . $profile['maiden'];
		else
			$fullname = $profile['fname'] . ' ' . $profile['lname'];
		$p->parseValue(array(
			'ALERT'			=>	$this->helper->prefill('error'),
			'NAME'			=>	$profile['fname'],
			'FULLNAME'		=>	$fullname,
			'FATHERNAME'	=>	$profile['fathername'],
			'FMAIDEN'		=>	$profile['fmaiden'],
			'MOTHERNAME'	=>	$profile['mothername'],
			'MAIDEN'		=> 	$profile['maiden'],
			'MARRIED_NAME'		=> 	$profile['married'],
			'NICKNAME'		=> 	$profile['nickname'],
			'GENDER'		=> 	isset($profile['gender']) ? $gender_arr[$profile['gender']] : '',
			'DOB'			=>	$cur_dob,
			'POB'			=>	$profile['pob'],
			'CC'			=>	$city,
			'DESCRIPTION'	=>	nl2br($profile['description']),
			'URL_FT'		=>	($ft !== '') ? '<a href="' . $ft . '" class="btn btn-success">Family tree</a>' : '',
			'URL_TL'		=>	($tl !== '') ? '<a href="' . $tl . '" class="btn btn-success">Timeline</a>' : '',
			'IMAGE'			=>  file_exists("uploads/person/{$glob['id']}.jpg") ? "uploads/person/{$glob['id']}.jpg" : "assets/img/profile.na.png"
		));

		return $p->fetch();
	}
}
?>