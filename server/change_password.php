<?php
	require_once '../lib/personas_constants.php';
	require_once '../lib/user.php';	

	$user = new PersonaUser();
	
	if (array_key_exists('userreq', $_POST))
	{
		$username = array_key_exists('userreq', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['userreq']) : $_POST['userreq']) : null;
		$email = $user->get_email($username);
		if (!$email)
		{
			echo "We have no email address on file for you. Please contact personas-devel@mozilla.com";
			exit;
		}
		
		$code = $user->generate_password_change_code($username);
		$message = "URL to visit: http://www.getpersonas.com/upload/change_password.php?user=$username&code=$code";
		mail($email, 'Resetting your personas password', $message, "From: personas-devel@mozilla.com\r\n");	
		echo "A password change link has been mailed to the address on file for your username.";
		exit;
	}
	elseif (array_key_exists('user', $_POST))
	{
		$username = array_key_exists('user', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['user']) : $_POST['user']) : null;
		$code = array_key_exists('user', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['code']) : $_POST['code']) : null;
		$password = array_key_exists('pass', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['pass']) : $_POST['pass']) : null;
		$conf = array_key_exists('passconf', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['passconf']) : $_POST['passconf']) : null;

		if (!$code)
		{
			header('Location: /');
			exit;
		}
		if (!$user->check_password_change_code($username, $code))
		{
			echo "The code you submitted is not valid for that username. Please request another one";
			exit;
		}

		if ($password != $conf)
		{
			echo "The password and confirmation do not match. Please try again";
?>
<form action="change_password.php" method=POST>
<input type="hidden" name="user" value="<?= $username ?>">
<input type="hidden" name="code" value="<?= $code ?>">
Password: <input type=password name=pass>
<br>
Confirm Password: <input type=password name=passconf>
<br>
<input type=submit>
</form>
<?php
		}
		else
		{
			#do some password strength validation here?
			$user->update_password($username, $password);
			echo "Your password has been updated and you have been logged in. Thanks for using personas!";
			exit;
		}
	}
	elseif (array_key_exists('user', $_GET))
	{	
		$username = array_key_exists('user', $_GET) ? (ini_get('magic_quotes_gpc') ? stripslashes($_GET['user']) : $_GET['user']) : null;
		$code = array_key_exists('user', $_GET) ? (ini_get('magic_quotes_gpc') ? stripslashes($_GET['code']) : $_GET['code']) : null;

		if (!$code)
		{
			header('Location: /');
			exit;
		}
		if (!$user->check_password_change_code($username, $code))
		{
			echo "The code you submitted is not valid for that username. Please request another one";
			exit;
		}
?>
<form action="change_password.php" method=POST>
<input type="hidden" name="user" value="<?= $username ?>">
<input type="hidden" name="code" value="<?= $code ?>">
Password: <input type=password name=pass>
<br>
Confirm Password: <input type=password name=passconf>
<br>
<input type=submit>
</form>
<?php
	}
	else
	{
?>

Request a password change
<form action="change_password.php" method=POST>
Username: <input type=text name=userreq>
<br>
<input type=submit>
</form>
<?php
	}
	
	
	