<?php
session_start();

require '../src/SimpleUser.php';


$database = ['host' => 'localhost', 'name' => 'simpleuser', 'user' => 'root', 'password' => ''];

$su = new \MineSQL\SimpleUser($database);