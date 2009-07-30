<?php
	require_once 'lib/personas_constants.php';
	require_once 'lib/user.php';	
	
	$_GET['no_my'] = 1;
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
				$error = sprintf(_("Oops!  We are unable to locate the username you entered.  Please try again, or <a href=\"%s\">create a new one</a>."), $locale_conf->url('/update'));
				include "templates/forgot_password_tmpl.php";
				exit;
			}
			
			$email = $user->get_email($username);
			if (!$email)
			{
				$error = _("We have no email address on file for you. Please contact personas-devel@mozilla.com");
				include "templates/forgot_password_tmpl.php";
				exit;
			}
			
			$code = $user->generate_password_change_code($username);
			$mail_message = sprintf(_("So many passwords to remember! You asked to reset your personas password. To do so, please visit:\n\n
						%s\n\n
						This link will let you change your password to something new. If you didn't ask for this, don't worry, we'll keep your password safe.\n\n
						Best Wishes,\n
						The Personas Team\n"), $locale_conf->url("/forgot_password?username=$username&code=$code"));
			
			if (!mail($email, _('Resetting your personas password'), $mail_message, "From: personas-devel@mozilla.com\r\n"))	// TODO
			{
				$error = _("There was a problem with our mail server. Please try again in a few minutes. If it continues to not work, please contact personas-devel@mozilla.com");
				include "templates/forgot_password_tmpl.php";
				exit;
			}
			
			include "templates/forgot_password_thanks_tmpl.php";
			exit;
		}
		catch (Exception $e)
		{
			$error = _("There was an internal error. Please contact personas-devel@mozilla.com");
			error_log($e->getMessage());
			include "templates/forgot_password_tmpl.php";
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
				$error = _("The code you submitted is not valid for that username. Please request another one");
				include "templates/forgot_password_tmpl.php";
				exit;
			}
			
			include "templates/forgot_password_reset_tmpl.php";
			exit;
		}
		catch (Exception $e)
		{
			$error = _("There was an internal error. Please contact personas-devel@mozilla.com");
			error_log($e->getMessage());
			include "templates/forgot_password_reset_tmpl.php";
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
				include "templates/forgot_password_tmpl.php";
				exit;
			}
			if (!$user->check_password_change_code($username, $code))
			{
				$error = _("The code you submitted is not valid for that username. Please request another one");
				include "templates/forgot_password_tmpl.php";
				exit;
			}
	
			if (strlen($password) < 6)
			{
				$error = _("Password must be at least 6 characters long");
				include "templates/forgot_password_reset_tmpl.php";
				exit;
			}

			if (!preg_match('/[A-Z]/i', $password) || !preg_match('/[^A-Z]/i', $password))
			{
				$error = _("The password should contain at least one alphabetic character and at least one non-alphabetic character");
				include "templates/forgot_password_reset_tmpl.php";
				exit;
			}
			
			if ($password != $conf)
			{
				$error = _("The password and confirmation do not match. Please try again");
				include "templates/forgot_password_reset_tmpl.php";
				exit;
			}			
	
			$user->update_password($username, $password);
			
			include "templates/forgot_password_done_tmpl.php";
			exit;			
		}
		catch (Exception $e)
		{
			$error = _("There was an internal error. Please contact personas-devel@mozilla.com");
			error_log($e->getMessage());
			include "templates/forgot_password_reset_tmpl.php";
			exit;			
		}
	}		

	include "templates/forgot_password_tmpl.php";
	exit;

?>
