<?php

require 'init.php';

if(isset($_POST['loginBtn']))
{
	if($su->login($_POST['username'], $_POST['password'], \MineSQL\SimpleUser::SINGLE_SESSION))
	{
		echo 'logged in for one request.';
	} else {
		echo 'wrong information.';
	}
}
?>

<form method="POST">
<input name="username" type="text" />
<input type="password" name="password" />
<input type="submit" name="loginBtn" value="Login" />
</form>