<?php

require 'init.php';


if(isset($_POST['registerBtn']))
{
	$info = ['username' => $_POST['username'], 'password' => $_POST['password'], 'confirm_password' => $_POST['cpassword'], 'miscVar' => '234'];

	if($su->register($info))
	{
		echo 'successfully registered.';
	} else {
		echo $su->registerError;
	}
}
?>


<form method="POST">
username<input name="username" type="text" /><br />
password<input type="password" name="password" /><br />
confirm password<input type="password" name="cpassword" /><br />
<input type="submit" name="registerBtn" value="Login" />
</form>