<?php
/*
 * @Author: Slash Web Design
 */

class Core
{
	public $db;
	public $helper;
	public $user;
	
	function __construct()
	{
		global $db, $helper, $user;
		
		$this->db = $db;
		$this->helper = $helper;
		$this->user = $user;
	}
	
	public function canAccess($accessLevel)
	{
		if ($accessLevel === 4 || isset($_SESSION['a_id'])) return true;

		if (!isset($_SESSION['access_level'])) return false;
		
		if ($_SESSION['access_level'] <= $this->accessLevel) return true;
	}	
}
?>