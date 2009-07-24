<?php

# ***** BEGIN LICENSE BLOCK *****
# Version: MPL 1.1/GPL 2.0/LGPL 2.1
#
# The contents of this file are subject to the Mozilla Public License Version
# 1.1 (the "License"); you may not use this file except in compliance with
# the License. You may obtain a copy of the License at
# http://www.mozilla.org/MPL/
#
# Software distributed under the License is distributed on an "AS IS" basis,
# WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
# for the specific language governing rights and limitations under the
# License.
#
# The Original Code is Personas Server
#
# The Initial Developer of the Original Code is
# Mozilla Labs.
# Portions created by the Initial Developer are Copyright (C) 2008
# the Initial Developer. All Rights Reserved.
#
# Contributor(s):
#	Toby Elliott (telliott@mozilla.com)
#
# Alternatively, the contents of this file may be used under the terms of
# either the GNU General Public License Version 2 or later (the "GPL"), or
# the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
# in which case the provisions of the GPL or the LGPL are applicable instead
# of those above. If you wish to allow use of your version of this file only
# under the terms of either the GPL or the LGPL, and not to allow others to
# use your version of this file under the terms of the MPL, indicate your
# decision by deleting the provisions above and replace them with the notice
# and other provisions required by the GPL or the LGPL. If you do not delete
# the provisions above, a recipient may use your version of this file under
# the terms of any one of the MPL, the GPL or the LGPL.
#
# ***** END LICENSE BLOCK *****
	

#####
# Mysql interface to the persona user tables and memcache layer in front of them
#
# The tables are documented in storage.php:
#

#####
# Memcache keys used by this module: 
#  user:<username> - user record containing 'display_username', 'description' and 'privs'

require_once 'personas_constants.php';
require_once 'recaptcha.php';

class PersonaUser
{
	var $_dbh;
	var $_memcache;

	var $_username = null;
	var $_display_username = null;
	var $_unauthed_username = null;
	var $_cookie_value = null;
	var $_email = null;
	var $_news = 0;
	var $_description = null;
	var $_privs = 0;
	var $_errors = array();
	var $_no_signup = 0;
	
	function __construct($username = null, $password = null, $hostname = null, $dbname = null) 
	{
		# We don't attempt to connect to the db at this stage, since many calls will end up just 
		# hitting memcache. This means that all calls that might need a db handle should make 
		# sure to check for one in $_dbh if the code falls past memcache. The handle is then
		# cached for future calls.
		
		if (MEMCACHE_PORT)
		{
			$this->memcache_connect();
		}

		if (array_key_exists('PERSONA_USER', $_COOKIE))
		{
			list($username, $token) = explode(' ', $_COOKIE['PERSONA_USER']);
			$this->_unauthed_username = $username;
		}
	}
	
#####
# Connect to mysql. All connection parameters are defined in personas_constants.php. Connected
# handle will be held in $_dbh
	
	function db_connect()
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

	
#####
# Connect to memcache. All connection parameters are defined in personas_constants.php. Connected
# handle will be held in $_memcache
	
	function memcache_connect()
	{
		$memc = new Memcache;
		if ($memc->connect('localhost', MEMCACHE_PORT))
			$this->_memcache = $memc;
	}
	
	
#####
# Core function to get and store the user information in memcache. Shouldn't need to be called directly.

	function get_user_data($username)
	{
		if ($this->_memcache)
		{
			$result = $this->_memcache->get("user:$username");
			if ($result)
				return $result;
		}
		
		if (!$this->_dbh)
			$this->db_connect();

		try
		{
			$select_stmt = 'select username, display_username, email, privs, description from users where username = :username';
			$sth = $this->_dbh->prepare($select_stmt);
			$sth->bindParam(':username', $username);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log("get_description: " . $exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		$result = $sth->fetch(PDO::FETCH_ASSOC);	

		if ($this->_memcache)
			$this->_memcache->set("user:$username", $result, false, MEMCACHE_DECAY);
		
		return $result;
	}
	

#####
# Get the users self-description. If called without a username, uses the record of the authed user.

	function get_description($username = null)
	{
		if (!$username)
			return $this->_description;
		
		$data = $this->get_user_data($username);
		return $data['description'];
	}

	
#####
# Get the users display name. If called without a username, uses the record of the authed user.
# Should not be used in persona context, as it's quicker to get that as part of the persona data.

	function get_display_username($username = null)
	{
		if (!$username)
			return $this->_display_username;
			
		$data = $this->get_user_data($username);
		return $data['display_username'];
	}
	
#####
# Returns the authorized username. Data here implies that the user has been authorized in the db

	function get_username()
	{
		return $this->_username;
	}

#####
# Returns the unauthorized username. Data here implies that the user has a username in their cookie
# but has not verified the remaining contents of the cookie

	function get_unauthed_username()
	{
		return $this->_unauthed_username;
	}
	
#####
# Returns the cookie value for an authorized user

	function get_cookie()
	{
		return $this->_cookie_value;
	}
	
#####
# Get the users email. If called without a username, uses the record of the authed user.

	function get_email($username = null)
	{
		if (!$username)
			return $this->_email;
			
		$data = $this->get_user_data($username);
		return $data['email'];
	}
	
#####
# Returns the contents of the error array, if anything has been written to it.

	function get_errors()
	{
		return $this->_errors;
	}


#####
# User is an admin?
	
	function has_admin_privs()
	{
		return $this->_privs >= 3;
	}

#####
# User is an approver? Note that an admin is automatically an approver. If we move away from that 
# model, we'll have to use bitscreens

	function has_approval_privs()
	{
		return $this->_privs >= 2;
	}

#####
# Adds a user to the database

	function create_user($username, $password, $display_username = null, $email = "", $description = "", $news = 0)
	{ 
		if (!$username)
			throw new Exception("No username", 404);

		if (!$password)
			throw new Exception("No password", 404);

		if (!$display_username)
			$display_username = $username;		
		
		if (!$this->_dbh)
			$this->db_connect();

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
		$this->_unauthed_username = $username;
		$this->_display_username = $display_username;
		$this->_description = $description;
		$this->_email = $email;
		$this->_cookie_value = $username . " " . md5($username . md5($password) . PERSONAS_LOGIN_SALT . $_SERVER['REMOTE_ADDR']);
		setcookie('PERSONA_USER', $this->_cookie_value, time() + 60*60*24*365, '/');

		return 1;
	}

#####
# Creates a placeholder username to prevent other people from creating it. Mostly used so China 
# can register accounts and avoid collisions.

	function create_placeholder_user($username)
	{ 
		if (!$username)
			throw new Exception("No username", 404);

		
		if (!$this->_dbh)
			$this->db_connect();

		try
		{
			$insert_stmt = 'insert into users (username, display_username, md5, email, description, news, privs) values (:username, :display_username, "", "", "", 0, 0)';
			$sth = $this->_dbh->prepare($insert_stmt);
			$sth->bindParam(':username', $username);
			$sth->bindParam(':display_username', $username);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log("create_placeholder_user: " . $exception->getMessage());
			throw new Exception("A database problem occured. Please try again later.");
		}

		return $username;
	}
	
#####
# Updates a user record

	function update_user($username, $display_username = null, $email = "", $description = "", $news = 0)
	{ 
		if (!$username)
			throw new Exception("No username", 404);

		if (!$display_username)
			$display_username = $username;		
		
		if (!$this->_dbh)
			$this->db_connect();

		try
		{
			$insert_stmt = 'update users set display_username = :display_username, email = :email, description = :description, news = :news where username = :username';
			$sth = $this->_dbh->prepare($insert_stmt);
			$sth->bindParam(':username', $username);
			$sth->bindParam(':display_username', $display_username);
			$sth->bindParam(':email', $email);
			$sth->bindParam(':description', $description);
			$sth->bindParam(':news', $news);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log("update_user: " . $exception->getMessage());
			#need to add a subcatch here for user already existing
			$this->_errors['create_username'] = "A database problem occured. Please try again later.";
			return 0;
		}

		$this->_email = $email;
		$this->_display_username = $display_username;
		$this->_description = $description;

		if ($this->_memcache)
			$this->_memcache->delete('user:' . $username);

		return 1;
	}

#####
# Updates a user's password and clears their change code so that they can't update again with it. 
# Sets up a new cookie for the user

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

		if (!$this->_dbh)
			$this->db_connect();

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

#####
# Most site authentication will simply be taking the data from the cookie and making sure it's
# valid. Signing in from password only happens on the login page

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
	
#####
# Checks to see if the user is signed in legitimately (as opposed to unauthed_username) and 
# redirects them to login if they haven't

	function force_signin($admin = null)
	{
		if (!$this->_username)
		{
			header('Location: /signin?return=' . $_SERVER['SCRIPT_URL'] . ($admin ? "&admin=1" : ""));
			exit;
		}
	}		

#####
# Wipe the user's cookie. Since personas is stateless, that'll be sufficient to log them out

	function log_out()
	{
		setcookie('PERSONA_USER', '', time() - 3600, '/');		
		$this->_errors['success_message'] = "You have been logged out. <a href=\"http://www.getpersonas.com/\">Return to the Personas Homepage</a>";
	}


#####
# Does a database lookup of the user, presumably from the login page. Should be pretty rare.

	function authenticate_user_from_password($username, $password) 
	{
		if (!$this->_dbh)
			$this->db_connect();

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
			$this->_description = $result['description'];	
			$this->_news = $result['news'];	
			return 1;
		}
		return 0;
	}

#####
# The more common method of logging in a user. Compares the computed cookie has against the 
# hashed expected values from the db, and populates this object in case the data is needed later
# Note that part of the hash is REMOTE_ADDR, so simple cookie theft won't be sufficient to pose as
# the user.

	function authenticate_user_from_cookie($auth_cookie) 
	{
		if (!$auth_cookie)
			return 0;
		
		list($username, $token) = explode(' ', $auth_cookie);

		if (!$this->_dbh)
			$this->db_connect();
		
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
			$this->_description = $result['description'];	
			$this->_news = $result['news'];	
			return 1;
		}
		return 0;
	}

    
#####
# Generates a random 16-character string, sticks hyphens in to make it easier to read, and stores it
# as the change password key for the user.

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
		
		if (!$this->_dbh)
			$this->db_connect();

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


#####
# Verifies that the user has the correct string from the email they were sent. Only has the last
# code requested, so if they issued another request, old codes won't be valid.

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

		if (!$this->_dbh)
			$this->db_connect();

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
	

#####
# Does a username exist? Lets you avoid collisions.

	function user_exists($username) 
	{
		if (!$this->_dbh)
			$this->db_connect();

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
	
#####
# Deletes a user from the db. Not currently exposed anywhere.

	function delete_user($username)
	{
		if (!$username)
		{
			throw new Exception("3", 404);
		}

		if (!$this->_dbh)
			$this->db_connect();

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

		if ($this->_memcache)
			$this->_memcache->delete('user:' . $username);
		return 1;
	}
		

########################################################## ADMIN FUNCTIONS


#####
# Given a partial username or partial email, looks for a user in the table and 
# returns all matching possibilities

	function find_user($partial_username, $partial_email = null)
	{
		if (!$this->_dbh)
			$this->db_connect();

		if ($partial_username)
		{
			if (!preg_match('/^[A-Z0-9\._-]+$/i', $partial_username)) 
				return array();
			$statement = 'select * from users where username like "%' . $partial_username . '%"';	
		}
		else if ($partial_email)
		{
			if (!preg_match('/^[A-Z0-9@\._%+-]+$/i', $partial_email)) 
				return array();
			$statement = 'select * from users where email like "%' . $partial_email . '%"';
		}
		else
			return array();

		try
		{
			$sth = $this->_dbh->prepare($statement);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log($exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}

		$users = array();
		
		while ($result = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$users[] = $result;
		}		
		return $users;
	}

#####
# Make username an admin

	function promote_admin($username)
	{
		if (!$username)
		{
			return 0;
		}

		if (!$this->_dbh)
			$this->db_connect();

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

		if ($this->_memcache)
			$this->_memcache->delete('user:' . $username);
		return 1;
	}

#####
# Make username an approver. Note that this is a demotion from an admin, which is already a
# superset of approver.
		
	function promote_approver($username)
	{
		if (!$username)
		{
			return 0;
		}

		if (!$this->_dbh)
			$this->db_connect();

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

		if ($this->_memcache)
			$this->_memcache->delete('user:' . $username);
		return 1;
	}
}


?>
