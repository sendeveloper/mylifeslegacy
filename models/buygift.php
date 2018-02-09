<?php
/*
 * @Author: Slash Web Design
 */
class BuyGift extends Core
{
	public		 $template = 'template-app.html';
	public        $loadCSS = array('datepicker.css', 'buygift.css');
	public         $loadJS = array('datepicker.js', 'buygift.js', 'facebook.js', 'client.js');
	public   $loadExternal = array('https://checkout.stripe.com/checkout.js');
	public		$hasAccess = true;
	protected $accessLevel = 4;
	
	function __construct()
	{
		parent::__construct();
	}
	
	public function fetch()
	{
		$p = new Parser("buy-gift.html");
		return $p->fetch();
	}
	public function checkpayment(&$ld)
	{
		$ret_arr = array('result' => '', 'promo' => 0);
		$res1 = $this->db->run("SELECT * FROM buygift WHERE LOWER(email) = '" . strtolower($ld['email']) . "' OR promocode = '" . $ld['promocode'] . "'");
		if (count($res1) > 0)
		{
			if (strtolower($res1[0]['email']) == strtolower($ld['email']))
				$ret_arr['result'] = 'Same email is already existed';
			else
				$ret_arr['result'] = 'Same promocode is already existed';
			$ret_arr['promo'] = $res1[0]['promonumber'];
		}
		else
			$ret_arr = array('result' => 'Success');
		$this->helper->respond(array(
			'status'	=>	true,
			'results'	=>	$ret_arr
		));
	}
	public function dopayment(&$ld)
	{
		$ret_arr = array('msg' => 'success');
		if (isset($ld['q'][18]['value']) && $ld['q'][3]['name'] == 'stripeToken'
			&& $ld['q'][0]['value'] >0 || $ld['q'][0]['value'] < 9)
		{
			$token = $ld['q'][18]['value'];
			if (isset($_SESSION['stripe_token']) && ($_SESSION['stripe_token'] == $token)) {
				$ret_arr['msg'] = 'You have apparently resubmitted the form. Please do not do that.';
			}
			else
			{
				$_SESSION['stripe_token'] = $token;
				$payment_values = [99.99, 189.99, 274.99, 359.99, 444.99, 529.99, 614.99, 699.99];
				$amount = $payment_values[$ld['q'][0]['value'] - 1];
				$email = $ld['q'][10]['value'];

				$pay = array();
				$pay['amount'] = $amount;
				$pay['token'] = $token;
				$pay['status'] = '';
				$pay['gift_id'] = '-1';

				try {
					global $db, $helper;
					Stripe::setApiKey(STRIPE_PRIVATE_KEY);
					Stripe_Charge::create(array(
						"amount"		=> $amount * 100,
						"currency"		=> "USD",
						"card"			=> $token
					));
						
					$db->insert("buygift", array(
						'amount'		=>	$amount,
						'promonumber'	=> 	$ld['q'][0]['value'],
						'promocode'		=>	$ld['q'][1]['value'],
						'first_name'	=>	$ld['q'][3]['value'],
						'last_name'		=>	$ld['q'][4]['value'],
						'address'		=>	$ld['q'][5]['value'],
						'address2'		=>	$ld['q'][6]['value'],
						'city'			=>	$ld['q'][7]['value'],
						'country'		=>	$ld['q'][8]['value'],
						'zipcode'		=>	$ld['q'][9]['value'],
						'email'			=>	$ld['q'][10]['value'],
						'phone_number'	=>	$ld['q'][11]['value'],
						'card_number'	=>	$ld['q'][12]['value'],
						'exp_month'		=>	$ld['q'][13]['value'],
						'exp_year'		=>	$ld['q'][14]['value'],
						'cvv'			=>	$ld['q'][15]['value'],
						'how_hear'		=>	$ld['q'][16]['value'],
						'date'			=>	time()
					));
					$pay['gift_id'] = $db->lastInsertId();

					$ret_arr['msg'] = 'Your payment was processed';
					$pay['status'] = 'Success';

					$helper->sendMailTemplate(
						'buy.gift',
						array('[NAME]', '[MEMBER_COUNT]','[MEMBER_CODE]'),
						array($ld['q'][3]['value'], $ld['q'][0]['value'], $ld['q'][1]['value']),
						array('name' => $ld['q'][3]['value'] . ' ' . $ld['q'][4]['value'], 'email' => $ld['q'][10]['value'])
					);
				} catch (Stripe_CardError $e){
					$pay['status'] = 'Failed (card was declined)';
					$ret_arr['msg'] = "This card has been declined";
				}

				$this->saveTransaction($pay);
			}
		}
		else
		{
			$ret_arr['msg'] = 'The order cannot be processed.';
		}

		$this->helper->respond(array(
			'status'	=>	true,
			'results'	=>	$ret_arr
		));
	}
	protected function saveTransaction(&$pay)
	{
		global $db;
		
		$db->insert("gift_transaction", array(
			'gift_id'		=>	$pay['gift_id'],
			'date'			=>	time(),
			'token'			=>	$pay['token'],
			'amount'		=>	$pay['amount'],
			'status'		=>	$pay['status']
		));
		$pay['transaction_id'] = $db->lastInsertId();
		
		return true;
	}
}
?>