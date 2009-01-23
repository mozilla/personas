<?php

require_once 'personas_constants.php';

class PersonaUser
{
	var $_dbh;

	var $_username = null;
	var $_cookie_value = null;
	var $_email = null;
	var $_privs = 0;

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
			throw new Exception("Database unavailable", 503);
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
			$insert_stmt = 'update users set md5 = :md5 where username = :username';
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
		
		if (array_key_exists('user', $_POST))
		{
			#trying to log in
			$auth_user = ini_get('magic_quotes_gpc') ? stripslashes($_POST['user']) : $_POST['user'];
			$auth_pass = ini_get('magic_quotes_gpc') ? stripslashes($_POST['pass']) : $_POST['pass'];
			$this->authenticate_user_from_password($auth_user, $auth_pass);
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

		setcookie('PERSONA_USER', $this->_cookie_value, time() + 60*60*24*365, '/');
		return $this->_username;
	}		
	
	function log_out()
	{
		setcookie('PERSONA_USER', '', time() - 3600, '/');		
		include '../lib/auth_form.php';
		exit;
	}

	function auth_form()
	{
		include '../lib/auth_form.php';
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
