<?php

require_once 'personas_constants.php';
require_once 'recaptcha.php';

class PersonaUser
{
	var $_dbh;

	var $_username = null;
	var $_cookie_value = null;
	var $_email = null;
	var $_privs = 0;
	var $_errors = array();
	
	function __construct($username = null, $password = null, $hostname = null, $dbname = null) 
	{
		try
		{
			$this->_dbh = new PDO('mysql:host=' . PERSONAS_HOST . ';dbname=' . PERSONAS_DB, PERSONAS_USERNAME, PERSONAS_PASSWORD);
			$this->_dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		catch( PDOException $exception )
		{
				error_log($exception->getMessage());
				throw new Exception("Database unavailable", 503);
		}
	}
	
	function get_username()
	{
		return $this->_username;
	}
	
	function get_cookie()
	{
		return $this->_cookie_value;
	}
	
	function get_email($username = null)
	{
		if ($username)
		{
			try
			{
				$select_stmt = 'select email from users where username = :username';
				$sth = $this->_dbh->prepare($select_stmt);
				$sth->bindParam(':username', $username);
				$sth->execute();
			}
			catch( PDOException $exception )
			{
				error_log("get_email: " . $exception->getMessage());
				throw new Exception("Database unavailable", 503);
			}
			return $sth->fetchColumn();
		}
		else
		{
			return $this->_email;
		}
		
	}
	
	function get_errors()
	{
		return $this->_errors;
	}
	
	function has_admin_privs()
	{
		return $this->_privs == 2;
	}
	
	function create_user($username, $password, $email = "")
	{ 
		if (!$username)
		{
			throw new Exception("No username", 404);
		}
		if (!$password)
		{
			throw new Exception("No password", 404);
		}

		
		try
		{
			$insert_stmt = 'insert into users (username, md5, email, privs) values (:username, :md5, :email, 1)';
			$sth = $this->_dbh->prepare($insert_stmt);
			$sth->bindParam(':username', $username);
			$sth->bindParam(':md5', md5($password));
			$sth->bindParam(':email', $email);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log("create_user: " . $exception->getMessage());
			#need to add a subcatch here for user already existing
			$this->_errors['create_username'] = "A database problem occured. Please try again later.";
			return 0;
		}

		$this->_username = $username;
		$this->_email = $email;
		$this->_cookie_value = $username . " " . md5($username . md5($password) . PERSONAS_LOGIN_SALT . $_SERVER['REMOTE_ADDR']);
		setcookie('PERSONA_USER', $this->_cookie_value, time() + 60*60*24*365, '/');

		return 1;
	}

	function update_password($username, $password)
	{
		if (!$username)
		{
			throw new Exception("No username", 404);
		}
		if (!$password)
		{
			throw new Exception("No password", 404);
		}

		try
		{
			$insert_stmt = 'update users set md5 = :md5, change_code = NULL where username = :username';
			$sth = $this->_dbh->prepare($insert_stmt);
			$sth->bindParam(':username', $username);
			$sth->bindParam(':md5', md5($password));
			if ($sth->execute() == 0)
			{
				throw new Exception("User not found", 404);
			}			
		}
		catch( PDOException $exception )
		{
			error_log("update_password: " . $exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}

		$this->_cookie_value = $username . " " . md5($username . md5($password) . PERSONAS_LOGIN_SALT . $_SERVER['REMOTE_ADDR']);

		return 1;	
	}

	function update_email($username, $email = "")
	{
		if (!$username)
		{
			throw new Exception("No username", 404);
		}

		try
		{
			$insert_stmt = 'update users set email = :email where username = :username';
			$sth = $this->_dbh->prepare($insert_stmt);
			$sth->bindParam(':username', $username);
			$sth->bindParam(':email', $email);
			if ($sth->execute() == 0)
			{
				throw new Exception("User not found", 404);
			}
		}
		catch( PDOException $exception )
		{
			error_log("update_email: " . $exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		return 1;
	
	}

	function authenticate()
	{
		
		if (array_key_exists('logout', $_POST))
		{
			$this->log_out();
		}
		
		if (array_key_exists('create_username', $_POST) && $_POST['create_username'])
		{
			#trying to create an account
			$username = array_key_exists('create_username', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['create_username']) : $_POST['create_username']) : null;
			$password = array_key_exists('create_password', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['create_password']) : $_POST['create_password']) : null;
			$passwordconf = array_key_exists('create_passconf', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['create_passconf']) : $_POST['create_passconf']) : null;
			$email = array_key_exists('create_email', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['create_email']) : $_POST['create_email']) : null;

			$captcha_response = recaptcha_check_answer(
				RECAPTCHA_PRIVATE_KEY,
				$_SERVER['REMOTE_ADDR'],
				$_POST['recaptcha_challenge_field'],
				$_POST['recaptcha_response_field']
			);
			
			if (!$captcha_response->is_valid) 
				$this->_errors['captcha'] = "Invalid captcha response. Please try again.";

			if (!preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $email)) 
				$this->_errors['create_email'] = "Invalid email address";

			if (!preg_match('/^[A-Z0-9._-]+/i', $username)) 
				$this->_errors['create_username'] = "Illegal characters in the username (alphanumerics, period, underscore and dash only)";
			
			if (strlen($password) < 6)
				$this->_errors['create_password'] = "Password must be at least 6 characters long";
			
			if ($password != $passwordconf)
				$this->_errors['create_passconf'] = "Password does not match confirmation";
			
			if ($this->user_exists($username))
				$this->_errors['create_username'] = "Username already in use";
				
			if (count($this->_errors) == 0)
			{
				if ($this->create_user($username, $password, $email))
				{
					setcookie('PERSONA_USER', $this->_cookie_value, null, '/');
					return $this->_username;
				}
			}
		}
		
		if (array_key_exists('login_user', $_POST) && $_POST['login_user'])
		{
			#trying to log in with password
			$auth_user = ini_get('magic_quotes_gpc') ? stripslashes($_POST['login_user']) : $_POST['login_user'];
			$auth_pass = array_key_exists('login_pass', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['login_pass']) : $_POST['login_pass']) : null;
			$auth_remember = array_key_exists('login_remember', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['login_remember']) : $_POST['login_remember']) : null;
			if ($this->authenticate_user_from_password($auth_user, $auth_pass))
			{
				setcookie('PERSONA_USER', $this->_cookie_value, $auth_remember ? time() + 60*60*24*365 : null, '/');
				return $this->_username;
			}
			else
			{
				$this->_errors['login_user'] = "Invalid username or password. Please try again";
			}
		}
		
		if (!$this->_username && array_key_exists('PERSONA_USER', $_COOKIE))
		{
			$this->authenticate_user_from_cookie($_COOKIE['PERSONA_USER']);
		}
				
		if (!$this->_username)
		{
			$this->auth_form();
			exit;
		}

		return $this->_username;
	}		
	
	function log_out()
	{
		setcookie('PERSONA_USER', '', time() - 3600, '/');		
		$this->auth_form();
		exit;
	}

	function auth_form()
	{
		include 'signup_login_tmpl.php';
	}
	
	function authenticate_user_from_password($username, $password) 
	{
		try
		{
			$select_stmt = 'select * from users where username = :username and md5 = :md5';
			$sth = $this->_dbh->prepare($select_stmt);
			$sth->bindParam(':username', $username);
			$sth->bindParam(':md5', md5($password));
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log("authenticate_user from password: " . $exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}

		$result = $sth->fetch(PDO::FETCH_ASSOC);
		if ($result && $result['privs'] > 0) #0 is disabled
		{
			$this->_username = $username;
			$this->_privs = $result['privs'];
			$this->_cookie_value = $username . " " . md5($result['username'] . $result['md5'] . PERSONAS_LOGIN_SALT . $_SERVER['REMOTE_ADDR']);
			$this->_email = $result['email'];	
			return 1;
		}
		return 0;
	}
	
	function authenticate_user_from_cookie($auth_cookie) 
	{
		list($username, $token) = explode(' ', $auth_cookie);
		
		try
		{
			$select_stmt = 'select * from users where username = :username';
			$sth = $this->_dbh->prepare($select_stmt);
			$sth->bindParam(':username', $username);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log("authenticate_user_from cookie: " . $exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}

		$result = $sth->fetch(PDO::FETCH_ASSOC);
		
		#verify the cookie
		
		if ($result['privs'] > 0 && md5($username . $result['md5'] . PERSONAS_LOGIN_SALT . $_SERVER['REMOTE_ADDR']) == $token)
		{
			$this->_username = $result['username'];
			$this->_privs = $result['privs'];
			$this->_cookie_value = $auth_cookie;
			$this->_email = $result['email'];	
			return 1;
		}
		return 0;
	}

	function generate_password_change_code($username)
	{
		if (!$username)
		{
			throw new Exception("No username", 404);
		}
		$string = '';
		for ($i = 1; $i <= 16; $i++) 
		{
			$number = rand(0,35) + 48;
			if ($number > 57) { $number += 7; }
			$string .= chr($number);
			if ($i == 4 || $i == 8 || $i == 12) { $string .= '-'; }
		}
		
		try
		{
			$insert_stmt = 'update users set change_code = :code where username = :username';
			$sth = $this->_dbh->prepare($insert_stmt);
			$sth->bindParam(':username', $username);
			$sth->bindParam(':code', $string);
			if ($sth->execute() == 0)
			{
				throw new Exception("User not found", 404);
			}			
		}
		catch( PDOException $exception )
		{
			error_log("generate_password_change_code: " . $exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		return $string;
	}

	function check_password_change_code($username, $code)
	{
		if (!$username)
		{
			throw new Exception("No username", 404);
		}
		if (!$code)
		{
			return 0;
		}
		try
		{
			$select_stmt = 'select 1 from users where username = :username and change_code = :code';
			$sth = $this->_dbh->prepare($select_stmt);
			$sth->bindParam(':username', $username);
			$sth->bindParam(':code', $code);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log("check_password_change_code: " . $exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		return $sth->fetchColumn();
		
	}
	


	function user_exists($username) 
	{
		try
		{
			$select_stmt = 'select count(*) from users where username = :username';
			$sth = $this->_dbh->prepare($select_stmt);
			$sth->bindParam(':username', $username);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log("user_exists: " . $exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}

		$result = $sth->fetchColumn();
		return $result ? 1 : 0;
	}
	
	function delete_user($username)
	{
		if (!$username)
		{
			throw new Exception("3", 404);
		}

		try
		{
			$delete_stmt = 'delete from users where username = :username';
			$sth = $this->_dbh->prepare($delete_stmt);
			$sth->bindParam(':username', $username);
			$sth->execute();

		}
		catch( PDOException $exception )
		{
			error_log("delete_user: " . $exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		return 1;
	}
		
	function promote_admin($username)
	{
		if (!$username)
		{
			return 0;
		}

		try
		{
			$stmt = 'update users set privs = 2 where username = :username';
			$sth = $this->_dbh->prepare($stmt);
			$sth->bindParam(':username', $username);
			$sth->execute();

		}
		catch( PDOException $exception )
		{
			error_log("promote_admin: " . $exception->getMessage());
			throw new Exception("Database unavailable");
		}
		return 1;
	}
}


?>
