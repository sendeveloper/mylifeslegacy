<?php
/*
 * @Author: Slash Web Design
 */

session_start();
$pageLoadTimeStart = microtime(true);

error_reporting(E_ALL);

require 'models/core/autoloader.php';
require 'config.php';

$glob	= array();
$db		= new Database("mysql:host={$config['host']};dbname={$config['database']}", $config['username'], $config['password']);
$helper = new Helper();
$user	= isset($_SESSION['user_id']) ? new User() : null;
$page   = new Controller();

header("Content-type: text/html; charset=utf-8;");

?>