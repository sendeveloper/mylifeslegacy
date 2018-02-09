<?php
/*
 * @Author: Slash Web Design
 */

require 'startup.php';

if (isset($glob['act']) && ($glob['act'] != ''))
{
	list($classString, $methodString) = explode("-", $glob['act']);

	$object = new $classString;
	$object->$methodString($glob);	
}

$helper->cleanUp();
unset($helper);
?>