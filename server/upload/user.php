<?php
	require_once 'storage.inc';

	$error = '';
	
	if (array_key_exists('user', $_POST))
	{
		$username = array_key_exists('user', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['user']) : $_POST['user']) : null;
		$password = array_key_exists('pass', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['pass']) : $_POST['pass']) : null;
		$passwordconf = array_key_exists('passconf', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['passconf']) : $_POST['passconf']) : null;
		$email = array_key_exists('email', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['email']) : $_POST['email']) : null;
		
		
		try
		{
			if (!preg_match('/^[A-Z0-9._-]+/i', $username)) 
			{
				throw new Exception("Illegal characters in username");
			}

			if ($password != $passwordconf)
			{
				throw new Exception("Password does not match confirmation");
			}
			
			$db = new PersonaStorage();
			if ($db->user_exists($username))
			{
				throw new Exception("User already exists");
			}
			
			$db->create_user($username, $password, $email);
			setcookie('PERSONA_USER', $username . " " . md5($username . $db->get_password_md5($username) . getenv('PERSONAS_LOGIN_SALT') . $_SERVER['REMOTE_ADDR']));
			print "<div class=\"message\">Username successfully created. You may start <a href=\"submit.php\">uploading a persona</a></div>";
			exit;

		}
		catch (Exception $e)
		{
			$error =  $e->getMessage();
		}
	}
?>
Create a personas account:

<?php if ($error) { echo "<div class=\"error\">$error</div>"; } ?>
<form method=POST enctype='multipart/form-data' action="user.php">
Username: <input type=text name="user">
<p>
Password: <input type=password name="pass">
<p>
Password Confirm: <input type=password name="passconf">
<p>
Email: <input type=text name="email">
<p>
<input type=submit>

</form>
