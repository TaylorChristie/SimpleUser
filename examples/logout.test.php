<?php

require 'init.php';


if($su->check())
{
	if($su->logout())
	{
		echo 'you have been logged out.';
	}
	
} else {
	echo 'you need to be logged in in order to logout.';
}