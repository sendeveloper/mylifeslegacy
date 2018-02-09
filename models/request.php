<?php
/*
 * @Author: Slash Web Design
 */

class Request extends Core
{
	public		 $template = 'template-app.html';
	public   $loadExternal = array('https://checkout.stripe.com/checkout.js','https://tour.tourmyapp.com/widgets/widget/59516435bc1fbe6e3e003092/');
	public        $loadCSS = array('datepicker.css', 'fileuploader.css');
	public         $loadJS = array('datepicker.js', 'fileuploader.js', 'request.js', 'facebook.js', 'client.js');
	public		$hasAccess = false;
	protected $accessLevel = 4;
	
	function __construct()
	{
		parent::__construct();

		$this->hasAccess = $this->canAccess($this->accessLevel);
	}
	
	public function send(&$ld)
	{
		$myDateTime1 = DateTime::createFromFormat('m/d/Y', $ld['dob']);
		if ($myDateTime1 != false && $ld['dob']!='0000-00-00')
			$dobStr = $myDateTime1->format('Y-m-d');
		else
			$dobStr = '';

		$myDateTime2 = DateTime::createFromFormat('m/d/Y', $ld['dod']);
		if ($myDateTime2 != false && $ld['dod']!='0000-00-00')
			$dodStr = $myDateTime2->format('Y-m-d');
		else
			$dodStr = '';

		$this->db->insert("request", array(
			//'member_id'		=>	$this->user->id,
			'email'			=>	$ld['youremail'],
			'name'			=>	$ld['yourname'],
			'relation'			=>	$ld['yourrelation'],

			'firstname'			=>	$ld['firstname'],
			'middlename'			=>	$ld['middlename'],
			'lastname'			=>	$ld['lastname'],
			'dob'			=>	$dobStr,
			'dod'			=>	$dodStr,
			'username'		=>	$ld['username'],
			'm_firstname'	=>	$ld['motherfirst'],
			'm_lastname'	=>	$ld['motherlast'],
			'f_firstname'	=>	$ld['fatherfirst'],
			'f_lastname'	=>	$ld['fatherlast'],	
			'date'			=>	time()
		));
		
		$ld['request_id'] = $this->db->lastInsertId();
		
		$newname = str_replace("/temp/", "/documents/", $ld['files']);
		if (file_exists($ld['files']))
		{
			rename($ld['files'], $newname);
			@unlink($ld['files']);
			$this->db->update("request", array('file' => $newname), "request_id = {$ld['request_id']}");
		}
		
		$ld['error'] = $this->helper->buildMessageBox('success', 'Your request was sent, we will get back to you as soon as possible.');
		return true;
	}
	
	public function fetch()
	{
		$p = new Parser("request.html");

		$p->parseValue(array(
			'ALERT'		=>	$this->helper->prefill('error'),
		));

		return $p->fetch();
	}
}
?>