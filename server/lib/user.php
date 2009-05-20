<?php

require_once 'personas_constants.php';
require_once 'recaptcha.php';

class PersonaUser
{
	var $_dbh;

	var $_username = null;
	var $_display_username = null;
	var $_unauthed_username = null;
	var $_cookie_value = null;
	var $_email = null;
	var $_privs = 0;
	var $_errors = array();
	var $_no_signup = 0;
	
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

		if (array_key_exists('PERSONA_USER', $_COOKIE))
		{
			list($username, $token) = explode(' ', $_COOKIE['PERSONA_USER']);
			$this->_unauthed_username = $username;
		}
		
	}
	
	function get_username()
	{
		return $this->_username;
	}
	
	function get_display_username()
	{
		return $this->_display_username;
	}

	function get_description($username = null)
	{
		if (!$username)
			$username = $this->_username;
			
		try
		{
			$select_stmt = 'select description from users where username = :username';
			$sth = $this->_dbh->prepare($select_stmt);
			$sth->bindParam(':username', $username);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log("get_description: " . $exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		return $sth->fetchColumn();		
	}
	
	function get_unauthed_username()
	{
		return $this->_unauthed_username;
	}
	
	function get_cookie()
	{
		return $this->_cookie_value;
	}
	
	function get_email($username = null)
	{
		if (!$username)
			return $this->_email;
			
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
	
	function get_errors()
	{
		return $this->_errors;
	}
	
	function has_admin_privs()
	{
		return $this->_privs >= 3;
	}
	
	function has_approval_privs()
	{
		return $this->_privs >= 2;
	}
	
	function create_user($username, $password, $display_username = null, $email = "", $description = "", $news = 0)
	{ 
		if (!$username)
			throw new Exception("No username", 404);

		if (!$password)
			throw new Exception("No password", 404);

		if (!$display_username)
			$display_username = $username;		
		
		try
		{
			$insert_stmt = 'insert into users (username, display_username, md5, email, description, news, privs) values (:username, :display_username, :md5, :email, :description, :news, 1)';
			$sth = $this->_dbh->prepare($insert_stmt);
			$sth->bindParam(':username', $username);
			$sth->bindParam(':display_username', $display_username);
			$sth->bindParam(':md5', md5($password));
			$sth->bindParam(':email', $email);
			$sth->bindParam(':description', $description);
			$sth->bindParam(':news', $news);
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

	function update_display_username($username, $display_username)
	{
		if (!$username)
		{
			throw new Exception("No username", 404);
		}

		if (!$display_username)
		{
			$display_username = $username;
		}
		try
		{
			$insert_stmt = 'update users set display_username = :display_username where username = :username';
			$sth = $this->_dbh->prepare($insert_stmt);
			$sth->bindParam(':username', $username);
			$sth->bindParam(':display_username', $display_username);
			if ($sth->execute() == 0)
			{
				throw new Exception("User not found", 404);
			}
		}
		catch( PDOException $exception )
		{
			error_log("update_display_username: " . $exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		return 1;
	
	}

	function update_description($username, $description = '')
	{
		if (!$username)
		{
			throw new Exception("No username", 404);
		}

		try
		{
			$insert_stmt = 'update users set description = :description where username = :username';
			$sth = $this->_dbh->prepare($insert_stmt);
			$sth->bindParam(':username', $username);
			$sth->bindParam(':description', $description);
			if ($sth->execute() == 0)
			{
				throw new Exception("User not found", 404);
			}
		}
		catch( PDOException $exception )
		{
			error_log("update_description: " . $exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		return 1;
	
	}

	function authenticate()
	{
		if (array_key_exists('PERSONA_USER', $_COOKIE))
		{
			$this->authenticate_user_from_cookie($_COOKIE['PERSONA_USER']);
		}

		if (!$this->_username && $this->_unauthed_username)
		{
			#we have a bad cookie.
			$this->log_out();
			$this->force_signin();
		}
		
		return $this->_username;
	}
	
	function force_signin($admin = null)
	{
		if (!$this->_username)
		{
			header('Location: /signin?return=' . $_SERVER['SCRIPT_URL'] . ($admin ? "&admin=1" : ""));
			exit;
		}
	}		
	
	function log_out()
	{
		setcookie('PERSONA_USER', '', time() - 3600, '/');		
		$this->_errors['success_message'] = "You have been logged out. <a href=\"http://www.getpersonas.com/\">Return to the Personas Homepage</a>";
#		$this->auth_form();
#		exit;
	}

	function auth_form()
	{
		#deprecated, I think...
		#include '../templates/signup_form.php';
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
			$this->_username = $result['username'];
			$this->_display_username = $result['display_username'];
			$this->_privs = $result['privs'];
			$this->_cookie_value = $result['username'] . " " . md5($result['username'] . $result['md5'] . PERSONAS_LOGIN_SALT . $_SERVER['REMOTE_ADDR']);
			$this->_email = $result['email'];	
			return 1;
		}
		return 0;
	}
	
	function authenticate_user_from_cookie($auth_cookie) 
	{
		if (!$auth_cookie)
			return 0;
		
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
			$this->_display_username = $result['display_username'];
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
			$stmt = 'update users set privs = 3 where username = :username';
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

		
	function promote_approver($username)
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
			error_log("promote_approver: " . $exception->getMessage());
			throw new Exception("Database unavailable");
		}
		return 1;
	}
}


?>
