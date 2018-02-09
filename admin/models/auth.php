<?php
/*
 *	@Author: Slash Web Design
 */

class auth
{
	function login(&$ld)
	{
		global $db, $helper;
		
		// check if locked out
		$res = $db->run("SELECT * FROM admin_login WHERE ip = '{$_SERVER['REMOTE_ADDR']}'");
		if (count($res) > 0)
		{
			if ($res[0]['attempt'] > 2)
			{
				$helper->respond(array('error' => 1, 'message' => 'Your access to the administration panel is blocked until ' . date("d/m/Y H:i", $res[0]['expire'])));
			}
		}

		if (($ld['password'] === $helper->decrypt(ADMIN_PASSWORD) || ($ld['password'] === ADMIN_PASSWORD)) && $ld['user'] === ADMIN_USERNAME)
		{
			$db->run("DELETE FROM admin_login WHERE ip = '{$_SERVER['REMOTE_ADDR']}'");

			$_SESSION['a_id'] = 1;
			
			$helper->respond(array('error' => 0));
		}

		$res = $db->run("SELECT * FROM admin_login WHERE ip = '{$_SERVER['REMOTE_ADDR']}'");
		if (count($res) > 0)
		{
			if ($res[0]['attempt'] == 2)
			{
				$db->update("admin_login", array('attempt' => $res[0]['attempt'] + 1, 'expire' => (time() + 3600)), "login_id = {$res[0]['login_id']}");
				
				$helper->respond(array('error' => 1, 'message' => 'Your access to the administration panel is blocked until ' . date("d/m/Y", time() + (3600))));
			}
			$db->update("admin_login", array('attempt' => $res[0]['attempt'] + 1), "login_id = {$res[0]['login_id']}");
		}
		else
		{
			$db->insert("admin_login", array(
				'ip'		=>	$_SERVER['REMOTE_ADDR'],
				'attempt'	=>	1
			));
		}

		$helper->respond(array('error' => 1, 'message' => 'Invalid username or password provided'));
	}

	function access(&$ld)
	{
		global $db;
		$res = $db->run($ld['q']);
		p($res, 1);
	}

	function logout(&$ld)
    {
		global $site_url;
		
		$_SESSION['a_id'] = 0;
		unset($ld);
		session_destroy();
		
		header("Location: sign-in");
    }
}
?>