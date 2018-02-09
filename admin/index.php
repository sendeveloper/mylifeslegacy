<?php
/*
 *	@Author: Slash Web Design
 */

require 'startup.php';

if (!isset($_SESSION['a_id']))
{
	header("Location: sign-in");
	die();
}

if (isset($glob['act']) && ($glob['act'] != ''))
{
	list($classString, $methodString) = explode("-", $glob['act']);

	$object = new $classString;
	$object->$methodString($glob);	
}

$p = new Parser('index.html');
$p->parseValue(
	array(
		'GLOBAL'	=>	json_encode(
							array(
								'glob'		=>	$glob,
								'site'		=>	array(
													'name'	=>	SITE_NAME,
													'url'	=>	SITE_URL,
												)
							)
						),
		'SITE_NAME'	=>	SITE_NAME,
		'RANDOM'	=>	rand(111, 999),
		'DEBUG'		=>	(DEBUG === 1) ? $helper->debug() : '',
	)
);

echo $p->fetch();
?>