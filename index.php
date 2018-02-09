<?php
/*
 * @Author: Slash Web Design
 */

require 'startup.php';

if (LIVE === 0)
{
	die('The site is currently under maintenance, please check back later');
}

if ($glob['pag'] === 'familytree' && $user != null)
{
	header('Content-type: application/xhtml+xml');
}
$page->run();
$p = new Parser($page->template);
$p->parseValue(array(
	'CONTENT'			=>	$page->content,
	'MAIN_MENU'			=>	($user === null) ? $helper->buildMainMenu(0, true) : $helper->buildUserMenu(),
	'RANDOM'			=>	rand(111, 999),
	'META_TITLE'		=>	META_TITLE,
	'META_KEYWORDS'		=>	META_KEYWORDS,
	'META_DESCRIPTION'	=>	META_DESCRIPTION,
	'SITE_URL'			=>	SITE_URL,

	'META_OG_URL'			=>	SITE_URL . 'family-tree',
	'META_OG_TITLE'			=>	'My Life\'s Legacy',
	'META_OG_DESCRIPTION'	=>	'No matter who we are, we all leave a mark on the world and the lives we have touched in our short time here',
	'META_OG_IMAGE'			=>	SITE_URL . 'img/logo.png',

	'GLOBAL'			=>	json_encode(array(
								'siteName'	=>	SITE_NAME,
								'siteURL'	=>	SITE_URL,
								'isHome'	=>	(isset($glob['title']) && ($glob['title'] == "home")) ? 1 : 0,
								'glob'		=>	$glob,
								'user'		=>	$user
							)),
	'USER_IMAGE'		=>	($user !== null) ? $user->image : '',
	'USER_NAME'			=>	($user !== null) ? $user->name : '',
	'PAGE_SCRIPT'		=>	$page->assets,
	'DEBUG'				=>	(DEBUG === 1) ? $helper->debug() : '',
	'CYEAR'				=>	date('Y', time())
));

echo $p->fetch();
$helper->cleanUp();
unset($helper);
?>