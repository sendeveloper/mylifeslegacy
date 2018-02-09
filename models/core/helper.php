<?php
/*
 * @Author: Slash Web Design
 */

class Helper
{
	protected $db;
	protected $timer;
	
	function __construct()
	{
		global $db;
		
		$this->db = $db;
		$this->startUp();
	}
	
	public function genderDD($g)
	{
		$out = '';
		$arr = array('male', 'female');
		foreach ($arr as $a)
		{
			$sel = ($a === $g) ? 'selected="selected"' : '';
			$out .= '<option value="' . $a . '" ' . $sel . '>' . $a . '</option>';
		}
		
		return $out;
	}

	public function timePassed($ts)
	{
		$diff = time() - $ts;
		
		if ($diff < 3600)
		{
			if ($diff < 60)
			{
				return ($diff == 1) ? "{$v} second ago" : "{$v} seconds ago";
			}
			
			$v = round($diff / 60);
			return ($v == 1) ? "{$v} minute ago" : "{$v} minutes ago";
		}
		
		$v = round($diff / 3600);
		return ($v == 1) ? "{$v} hour ago" : "{$v} hours ago";
	}

	public function getRatingHTML($value)
	{
		$out = '';
		for ($i = 1; $i <= 5; $i++)
		{
			if ($value >= 1)
			{
				$out .= '<span class="ico ico-star"></span>';
				$value--;
			}
			else if ($value >= 0.5)
			{
				$out .= '<span class="ico ico-star-half"></span>';
				$value = 0;
			}
			else
			{
				$out .= '<span class="ico ico-star-outline"></span>';
			}
		}
		return $out;
	}

	protected function mailWrap($content)
	{
		return '
		<table width="100%" style="background-color: #e0e0e0; margin: 0px;">
			<tr>
				<td height="100">&nbsp;</td>
			</tr>
			<tr>
				<td>
					<table style="font-family: Arial; font-size: 14px; background-color: #ffffff; color: #636363; border-bottom: 2px solid #d0d0d0" width="80%" align="center" cellpadding="20" cellspacing="0">
						<tr>
							<td align="center"><img src="' . SITE_URL . 'assets/img/logo.png" /></td>
						</tr>
						<tr>
							<td>' . $content . '</td>
						</tr>
						<tr>
							<td align="center">
								<div style="border-top: 1px solid #e0e0e0; font-size: 0px; margin-bottom: 20px">&nbsp;</div>
								<a style="color: #62a8ea; text-decoration: none" href="' . SITE_URL . '">' . SITE_NAME . ' &copy; ' . date("Y", time()) . '</a>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td height="100">&nbsp;</td>
			</tr>
		</table>';
	}
	public function sendMailTemplate($code, $in, $out, $to)
	{
		$res = $this->db->run("SELECT * FROM email WHERE code = '{$code}'");
		$m = $res[0];
		if ($code == 'user.welcome')
		{
			$body = str_replace($in, $out, $m['content']);
			$subject = $m['subject'];
			$from = array('name' => $m['from_name'], 'email' => $m['from_address']);
			$attach = 'models/certificates/' . $to['file_name'];
			$mail = new Mail();
			$mail->setOptions(array(
				'to'		=>	array(
									array('email' => $to['email'], 'name' => $to['name'], 'type' => 'to')
								),
				'from'		=>	$from,
				'subject'	=>	$subject,
				'body'		=>	$this->mailWrap($body),
				'attachment'=>	$attach
			));
			
			return $mail->send();
		}
		else
		{
			$this->sendMail(
				str_replace($in, $out, $m['content']),
				$m['subject'],
				$to,
				array('name' => $m['from_name'], 'email' => $m['from_address'])
			);
		}
	}
	public function sendMail($body, $subject, $to, $from = null, $wrap = true)
	{
		$mail = new Mail();
		
		$mail->setOptions(array(
			'to'		=>	array(
								array('email' => $to['email'], 'name' => $to['name'], 'type' => 'to')
							),
			'from'		=>	$from,
			'subject'	=>	$subject,
			'body'		=>	($wrap) ? $this->mailWrap($body) : $body
		));
		
		return $mail->send();
	}
	
	public function sendMailMultiple($body, $subject, $to, $tag, $attach = null)
	{
		$mail = new Mail();
		
		$mail->setOptions(array(
			'to'		=>	$to,
			'from'		=>	null,
			'subject'	=>	$subject,
			'body'		=>	$this->mailWrap($body),
			'tags'		=>	array($tag),
			'attachment'=>	$attach
		));
		
		$mail->send();
	}
	
	public function encrypt($plain)
	{
		$key = pack('H*', "bcb04b7e103a0cd8b54763051cef08bc55abe029fdebae5e1d417e2ffb2a00a3");
		$key_size = strlen($key);

		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

		$ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $plain, MCRYPT_MODE_CBC, $iv);
		$ciphertext = $iv . $ciphertext;

		return base64_encode($ciphertext);
	}
	
	public function decrypt($cipher)
	{
		$ciphertext_dec = base64_decode($cipher);

		$key = pack('H*', "bcb04b7e103a0cd8b54763051cef08bc55abe029fdebae5e1d417e2ffb2a00a3");

		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
		$iv_dec = substr($ciphertext_dec, 0, $iv_size);

		$ciphertext_dec = substr($ciphertext_dec, $iv_size);

		$plaintext_dec = @mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $ciphertext_dec, MCRYPT_MODE_CBC, $iv_dec);

		return trim($plaintext_dec);
	}
	
	public function respond($obj, $html = false)
	{
		//sleep(5);
		if ($html === true)
		{
			header("Content-type: text/html; charset=utf-8;");
			echo $obj;
		}
		else
		{
			header("Content-type: application/json; charset=utf-8;");
			echo json_encode($obj);
		}
		die();
	}
	
	public function p($obj, $die = false, $dump = false, $return = false)
	{
		if ($return === true)
		{
			return '<pre>' . print_r($obj, true) . '</pre>';
		}
		
		echo '<pre>';
		if ($dump) var_dump($obj); else print_r($obj);
		echo '</pre>';
		if ($die) die();
	}
	
	public function prefill($key)
	{
		global $glob;
		return isset($glob[$key]) ? $glob[$key] : '';
	}
	
	public function esc($str)
	{
		return str_replace("'", "\'", $str);
	}
	
	public function startUp()
	{
		global $config, $glob;
		
		$this->db->run('SET NAMES utf8');
		$this->db->run('SET CHARACTER SET utf8');
		$this->db->run('SET COLLATION_CONNECTION="utf8_general_ci"');

		$res = $this->db->run("SELECT name, value FROM settings");
		foreach ($res as $r)
		{
			$config[$r['name']] = $r['value'];
		}
		
		// turns all config data to constants
		foreach ($config as $key => $value)
		{
			define(strtoupper($key), $value);
		}
		
		$glob['fee'] = FEE;

		foreach($_POST as $key => $value)
		{
			$glob[$key] = $this->sanitizeInput($value);
		}
		foreach($_GET as $key => $value)
		{
			$glob[$key] = $this->sanitizeInput($value);
		}
	}
	
	public function timerStart()
	{
		$this->timer = microtime(1);
	}
	
	public function timerEnd($return = false)
	{
		$time = round(microtime(1) - $this->timer, 8);
		if ($return === true)
		{
			return $time;
		}
		p($time, 1);
	}
	
	public function sanitizeInput($param)
	{
		if (is_array($param))
		{
			$arr = array();
			foreach ($param as $key => $value)
			{
				if (is_array($value))
				{
					$arr2 = array();
					foreach ($value as $key2 => $value2)
					{
						$arr2[$key2] = strip_tags($value2);
					}
					
					$arr[$key] = $arr2;
				}
				else
				{
					$arr[$key] = strip_tags($value);
				}
			}
			
			return $arr;
		}
		
		return strip_tags($param);
	}
	
	public function sanitizeURL($str)
	{
		$out = '';
		
		$allowedChars = array(
			"0", "1", "2", "3", "4", "5", "6", "7", "8", "9",
			"a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z" 
		);
		
		for ($i = 0; $i < strlen($str); $i++)
		{
			if (in_array(strtolower($str[$i]), $allowedChars))
			{
				$out .= strtolower($str[$i]);
			}
			else
			{
				switch ($str[$i])
				{
					case " ": $out .= '-'; break;
					case "-": $out .= '-'; break;
					case "&": $out .= '-'; break;
					case "!": $out .= '-'; break;
					case "?": $out .= '-'; break;
					case "@": $out .= '-'; break;
					case "$": $out .= '-'; break;
					case "*": $out .= '-'; break;
					case "/": $out .= '-'; break;
					case "|": $out .= '-'; break;
				}
			}
		}
		
		return $this->stripMultipleDashes($out);
	}
	
	protected function stripMultipleDashes($str)
	{
		return str_replace(array('------', '-----', '----', '---', '--'), '-', $str);
	}

	public function buildPagination($num_rows, $row_per_page, $offset)
	{
		global $glob;

		$visible = 3; //both sides
		$pages = ceil($num_rows / $row_per_page);
		$pn = ($offset / $row_per_page) + 1;

		//debug data
		$glob['pag-data']['rpp'] = $row_per_page;
		$glob['pag-data']['num_rows'] = $num_rows;
		$glob['pag-data']['pn'] = $pn;
		$glob['pag-data']['pages'] = $pages;

		if ($pages > 1)
		{
			$out_str = '<div class="pagination"><ul>';

			if ($pn > 1)
			{
				$out_str .= '<li><a href="#" data-offset="' . ($offset - $row_per_page) . '">Prev</a></li>';
			}
			else
			{
				$out_str .= '<li class="disabled"><span>Prev</span></li>';
			}

			if ((($pn - $visible) > 1) && (($pn + $visible) < $pages))
			{
				$out_str .= '<li class="disabled"><span>...</span></li>';
				for ($i = $pn - $visible; $i <= $pn + $visible; $i++)
				{
					($i == $pn) ? $sel = 'class="active"' : $sel = '';
					$out_str .= '<li ' . $sel . '><a href="#" data-offset="' . (($i - 1) * $row_per_page) . '">' . $i . '</a></li>';
				}
				$out_str .= '<li class="disabled"><span>...</span></li>';
			}

			if ($pn - $visible <= 1)
			{
				$to = ($pages > 7) ? 7 : $pages;
				for ($i = 1; $i <= $to ; $i++)
				{
					($i == $pn) ? $sel = 'class="active"' : $sel = '';
					$out_str .= '<li ' . $sel . '><a href="#" data-offset="' . (($i - 1) * $row_per_page) . '">' . $i . '</a></li>';
				}
				if ($pages > 7)	$out_str .= '<li class="disabled"><span>...</span></li>';
			}
			if (($pn + $visible >= $pages) && ($pages > 7))
			{
				$from = ($pages > 7) ? $pages - 7 : 1;
				if ($pages > 7)	$out_str .= '<li class="disabled"><span>...</span></li>';
				for ($i = $from; $i <= $pages; $i++)
				{
					($i == $pn) ? $sel = 'class="active"' : $sel = '';
					$out_str .= '<li ' . $sel . '><a href="#" data-offset="' . (($i - 1) * $row_per_page) . '">' . $i . '</a></li>';
				}
			}

			if (($pn * $row_per_page) < $num_rows)
			{
				$out_str .= '<li><a href="#" data-offset="' . ($offset + $row_per_page) . '">Next</a></li>';
			}
			else
			{
				$out_str .= '<li class="disabled"><span>Next</span></li>';
			}


			$out_str .= '</ul></div>';
			return $out_str;
		}
		return "";
	}

	public function strLimit($str, $limit = 128)
	{
		if (strlen($str) < $limit)
		{
			return $str;
		}
		else
		{
			return substr($str, 0, $limit) . "...";
		}
	}

	public function buildUserMenu()
	{
		global $user, $glob, $site_url;

		if ($user === null) return '';

		$menu = array(
			array('src' => 'account', 'label' => 'My Account', 'icon' => 'ico-verified-user'),
			array('src'	=> 'family-tree', 'label' => 'Family Tree',	'icon' => 'ico-tree'),
			array('src'	=> 'timeline', 'label' => 'Timeline', 'icon' => 'ico-history'),
			array('src'	=> 'lockbox', 'label' => 'Lock box settings', 'icon' => ' ico-lock'),
			array('src'	=> '#', 'label' => '', 'icon' => ''),
			array('src'	=> 'sign-out', 'label' => 'Sign out', 'icon' => 'ico-exit-to-app')
		);

		$temp = explode("-", $glob['pag']);
		$page = $temp[0];
		$out = '<ul>';
		foreach ($menu as $m)
		{
			$class = ($page === $m['src']) ? 'class="active"' : '';
			$out .= '<li><a href="' . $site_url . $m['src'] . '" ' . $class . '><span class="ico ' . $m['icon'] . '"></span>' . $m['label'] . '</a></li>';
		}
		$out .= '</ul>';

		$desktop = 
			'<div class="user-menu pull-right">' .
				'<img src="' . $user->image . '" class="profile h30" />' .
				'<div class="dropdown">' .
					'<a class="dropdown-toggle" data-toggle="dropdown" href="#">' . $user->fname . ' <span class="caret"></span></a>' . 
					'<ul class="dropdown-menu pull-right" role="menu" aria-labelledby="dLabel">'
		;
		$mobile = 
			'<ul class="mobile-user-menu nav">'
		;

		$out = '';
		foreach ($menu as $m)
		{
			$out .= ($m['src'] === '#') ? '<li class="divider"></li>' : '<li><a href="' . $site_url . $m['src'] . '"><span class="ico ' . $m['icon'] . '"></span>' . $m['label'] . '</a></li>';
		}
		$out .= '</ul>';

		$desktop .= $out . '</div></div>';
		$mobile .= $out;

		return $desktop . $mobile;

		return $out;
	}

	public function buildMainMenu($parent_id = 0, $addUser = false)
	{
		global $db, $user, $helper;

		$i = 0;
		$out = ($parent_id == 0) ? '<ul class="nav">' : '<ul class="dropdown-menu">';
		$class = '';
		$items = $db->run("SELECT * FROM menu WHERE parent_id = $parent_id ORDER BY sort_order ASC");
		foreach ($items as $m)
		{
			$submenu = $this->buildMainMenu($m['menu_id']);
			if ($submenu == '<ul class="dropdown-menu"></ul>')
			{
				$out .= '<li><a href="' . $m['url'] . '">' . $m['label'] . '</a></li>';
			}
			else
			{
				$type = ($parent_id == 0) ? "dropdown" : "dropdown-submenu";
				$out .= '<li class="' . $type . '"><a href="' . $m['url'] . '" class="dropdown-toggle" data-toggle="dropdown">' . $m['label'] . '</a>' . $submenu . '</li>';
			}
			$i++;
		}

		//add user menu
		if ($addUser === true)
		{
			$out .= ($user != null) ? '<li><a href="dashboard">Dashboard</a></li>' : '<li><a href="sign-up">Sign up</a></li><li><a href="sign-in">Log in</a></li>';
		}

		$out .= '</ul>';

		return $out;
	}

	public function buildPadding($level)
	{
		$out = '';

		if ($level == 0) return $out;
		if ($level == 1) return "└─";

		for ($i = 0; $i < $level; $i++)
		{
			$out .= "&nbsp;";
		}
		return $out . "└─";
	}

	public function buildParentDD($id = 0, $sp = -1, $parent = 0, $level = 0)
	{
		global $db;
		$out = ($level == '') ? '<option value="0">Top Level (no parent)</option>' : '';
		$res = $db->run("SELECT * FROM menu WHERE parent_id = $parent ORDER BY sort");
		foreach ($res as $m)
		{
			if (($id == 0) || (($id > 0) && ($id != $m['menu_id'])))
			{
				$sel = ($m['menu_id'] == $sp) ? 'selected="selected"' : '';
				$out .= '<option value="' . $m['menu_id'] . '" ' . $sel . '>' . buildPadding($level) . $m['label'] . '</option>';
			}

			$out .= buildParentDD($id, $sp, $m['menu_id'], $level + 1);
		}
		return $out;
	}

	public function buildMessageBoxWithExclamation($type, $text, $block = true)
	{
		$ab = ($block) ? 'alert-block' : '';
		$title = ($block) ? '<h4>' . ucfirst($type) . '!</h4>' : '';
		return '<div class="alert alert-' . $type . ' ' . $ab . '"><button type="button" class="close" data-dismiss="alert">&times;</button>' . $title . $text . '</div>';
	}

	public function buildMessageBox($type, $text, $block = true)
	{
		$ab = ($block) ? 'alert-block' : '';
		$title = ($block) ? '<h4>' . ucfirst($type) . '</h4>' : '';
		return '<div class="alert alert-' . $type . ' ' . $ab . '"><button type="button" class="close" data-dismiss="alert">&times;</button>' . $title . $text . '</div>';
	}
	
	public function debug()
	{
		global $user, $glob, $pageLoadTimeStart;
		
		$pageLoadTimeEnd = microtime(true);
		$userObj = isset($user) ? print_r($user, true) : '';
		
		$ret = '
			<div class="debug-holder">
			<div class="controller"></div>
				<table class="debug">
					<tr>
						<td colspan="4"><strong>Current Directory: </strong>' . getcwd() . '</td>
					</tr>
					<tr>
						<td colspan="4"><strong>Session ID: </strong>' . session_id() . '</td>
					</tr>
					<tr>
						<td colspan="4"><strong>Load time: </strong>' . round($pageLoadTimeEnd - $pageLoadTimeStart, 10) . '</td>
					</tr>
					<tr>
						<td><strong>$glob</strong></td>
						<td><strong>$_FILES</strong></td>
						<td><strong>$_SESSION</strong></td>
						<td><strong>$user</strong></td>
					</tr>
					<tr>
						<td valign="top" width="25%"><pre>' . print_r($glob, true) . '</pre></td>
						<td valign="top" width="25%"><pre>' . print_r($_FILES, true) . '</pre></td>
						<td valign="top" width="25%"><pre>' . print_r($_SESSION, true) . '</pre></td>
						<td valign="top" width="25%"><pre>' . $userObj . '</pre></td>
					</tr>
					<tr>
						<td colspan="4"><strong>MySQL: </strong></td>
					</tr>
					<tr>
						<td colspan="4"><pre>';
							if ((isset($this->db->qs)) && (is_array($this->db->qs)))
							{
								foreach ($this->db->qs as $index => $query)
								{
									$ret .= '' . $index . '. ' . $query . '<br />';
								}
							}
						$ret .= '</pre>
						</td>
					</tr>
				</table>
			</div>';
						
		return $ret;
	}
	
	public function cleanUp()
	{
		global $glob, $user;
		
		unset($this->db);
		unset($glob);
		unset($user);
	}
}
?>