<?php
$pageLoadTimeStart = microtime(true);

error_reporting(E_ALL);

require "core/stripe_info.php";
require 'core/autoloader.php';
require '../config.php';

$glob	= array();
$db		= new Database("mysql:host={$config['host']};dbname={$config['database']}", $config['username'], $config['password']);
$helper = new Helper();
$user	= isset($_SESSION['user_id']) ? new User() : null;

$sql = "SELECT subscription.* FROM subscription LEFT JOIN member ON member.member_id=subscription.member_id where member.subscripted=1 and member.active=1";
$result = $db->run($sql);
$count = count($result);
// if ($count > 0)
// {
// 	for ($i=0;$i<$count;$i++)
// 	{
// 		$amount = 995;
// 		// $free_days = 15 * 24 * 60 * 60; // 15 days;
// 		$free_days = 15 * 60;
// 		$register_date = $result[$i]['register_date'];
// 		$now = time() - strtotime($register_date);
// 		if ($now > $free_days)
// 		{
// 		}
// 	}
// }
try {
	Stripe::setApiKey(STRIPE_PRIVATE_KEY);
	$amount = 995;
	$trial_days = 15;
	$plan = Stripe_Plan::create(array(
		"amount" 			=> $amount,
		"interval" 			=> "month",
		"name" 				=> "Basic Plan ",
		"currency" 			=> "usd",
		"trial_period_days" => $trial_days,
	));

}
catch (Stripe_CardError $e){
	$msg = "This card has been declined";
	$status = false;
}
?>