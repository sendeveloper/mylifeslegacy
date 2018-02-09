<?php
/*
 * @Author: Slash Web Design
 */

require 'startup.php';

$page->run();

echo $page->content;

$helper->cleanUp();
unset($helper);
?>