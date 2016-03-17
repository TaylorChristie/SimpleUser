<?php
require 'init.php';

if($su->check())
{
	
	echo 'you are logged in.';
} else {

	echo 'not logged in';
}

