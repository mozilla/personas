<?php
	require_once 'lib/personas_constants.php';
	require_once 'lib/user.php';	

	header('Cache-Control: no-store, must-revalidate, post-check=0, pre-check=0, private, max-age=0');
	header('Pragma: private');
	
	$user = new PersonaUser();
	$error = null;
	
	
	#initial request page
	if (array_key_exists('userreq', $_POST))
	{
		try
		{
			$username = array_key_exists('userreq', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['userreq']) : $_POST['userreq']) : null;
			if (!$user->user_exists($username))
			{
				$error = "Oops!  We are unable to locate the username you entered.  Please try again, or <a href='https://personas.services.mozilla.com/upload'>create a new one</a>.";
				include "lib/forgot_password_tmpl.php";
				exit;
			}
			
			$email = $user->get_email($username);
			if (!$email)
			{
				$error = "We have no email address on file for you. Please contact personas-devel@mozilla.com";
				include "lib/forgot_password_tmpl.php";
				exit;
			}
			
			$code = $user->generate_password_change_code($username);
			$mail_message = "So many passwords to remember! You asked to reset your personas password. To do so, please visit:\n\n";
			$mail_message .= "https://personas.services.mozilla.com/forgot_password?username=$username&code=$code\n\n";
			$mail_message .= "This link will let you change your password to something new. If you didn't ask for this, don't worry, we'll keep your password safe.\n\n";
			$mail_message .= "Best Wishes,\n";
			$mail_message .= "The Personas Team\n";
			
			if (!mail($email, 'Resetting your personas password', $mail_message, "From: personas-devel@mozilla.com\r\n"))
			{
				$error = "There was a problem with our mail server. Please try again in a few minutes. If it continues to not work, please contact personas-devel@mozilla.com";
				include "lib/forgot_password_tmpl.php";
				exit;
			}
			
			include "lib/forgot_password_thanks_tmpl.php";
			exit;
		}
		catch (Exception $e)
		{
			$error = "There was an internal error. Please contact personas-devel@mozilla.com";
			error_log($e->getMessage());
			include "lib/forgot_password_tmpl.php";
			exit;
		}
	}
	
	#here's a code, so give the password form
	if (array_key_exists('username', $_GET))
	{
		try
		{
			$username = array_key_exists('username', $_GET) ? (ini_get('magic_quotes_gpc') ? stripslashes($_GET['username']) : $_GET['username']) : null;
			$code = array_key_exists('code', $_GET) ? (ini_get('magic_quotes_gpc') ? stripslashes($_GET['code']) : $_GET['code']) : null;
	
			if (!$user->check_password_change_code($username, $code))
			{
				$error = "The code you submitted is not valid for that username. Please request another one";
				include "lib/forgot_password_tmpl.php";
				exit;
			}
			
			include "lib/forgot_password_reset_tmpl.php";
			exit;
		}
		catch (Exception $e)
		{
			$error = "There was an internal error. Please contact personas-devel@mozilla.com";
			error_log($e->getMessage());
			include "lib/forgot_password_reset_tmpl.php";
			exit;
		}
	}
	
	if (array_key_exists('user', $_POST))
	{
		try
		{
			$username = array_key_exists('user', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['user']) : $_POST['user']) : null;
			$code = array_key_exists('code', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['code']) : $_POST['code']) : null;
			$password = array_key_exists('password', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['password']) : $_POST['password']) : null;
			$conf = array_key_exists('password-verify', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['password-verify']) : $_POST['password-verify']) : null;
	
			if (!$code)
			{
				include "lib/forgot_password_tmpl.php";
				exit;
			}
			if (!$user->check_password_change_code($username, $code))
			{
				$error = "The code you submitted is not valid for that username. Please request another one";
				include "lib/forgot_password_tmpl.php";
				exit;
			}
	
			if (strlen($password) < 6)
			{
				$error = "Password must be at least 6 characters long";
				include "lib/forgot_password_reset_tmpl.php";
				exit;
			}

			if (!preg_match('/[A-Z]/i', $password) || !preg_match('/[^A-Z]/i', $password))
			{
				$error = "The password should contain at least one alphabetic character and at least one non-alphabetic character";
				include "lib/forgot_password_reset_tmpl.php";
				exit;
			}
			
			if ($password != $conf)
			{
				$error = "The password and confirmation do not match. Please try again";
				include "lib/forgot_password_reset_tmpl.php";
				exit;
			}			
	
			$user->update_password($username, $password);
			
			include "lib/forgot_password_done_tmpl.php";
			exit;			
		}
		catch (Exception $e)
		{
			$error = "There was an internal error. Please contact personas-devel@mozilla.com";
			error_log($e->getMessage());
			include "lib/forgot_password_reset_tmpl.php";
			exit;			
		}
	}		

	include "lib/forgot_password_tmpl.php";
	exit;

?>