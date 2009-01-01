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
	
	
# create table personas
# (
#  id integer primary key not null auto_increment,
#  name varchar(32) unique,
#  header varchar(64),
#  footer varchar(64),
#  category varchar(32),
#  status tinyint,
#  submit varchar(32),
#  approve varchar(32),
#  author varchar(32),
#  accentcolor varchar(10),
#  textcolor varchar(10),
#  popularity integer
# );
#
# create table categories
# (
#  id integer primary key not null auto_increment,
#  name varchar(32)
# );
#
# create table users
# (
#  username varchar(32) primary key,
#  md5 varchar(32),
#  email varchar(64),
#  admin tinyint default 0
# );
#

require_once 'personas_constants.php';

class PersonaStorage
{
	var $_dbh;

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
	
	function add_popularity($id)
	{
		if (!$name) { return 0; }

		try
		{
			$statement = 'update personas set popularity = popularity + 1 where name = :id';
			$sth = $this->_dbh->prepare($statement);
			$sth->bindParam(':id', $id);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log($exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		return 1;
		
	}
	
	
	function approve_persona($id)
	{
		if (!$id) { return 0; }

		try
		{
			$statement = 'update personas set status = 1, approve = current_timestamp where id = :id';
			$sth = $this->_dbh->prepare($statement);
			$sth->bindParam(':id', $id);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log($exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		return 1;
		
	}

	function reject_persona($id)
	{
		if (!$id) { return 0; }
		
		try
		{
			$statement = 'update personas set status = 2, approve = current_timestamp where id = :id';
			$sth = $this->_dbh->prepare($statement);
			$sth->bindParam(':id', $id);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log($exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		return 1;
		
	}
	
	function get_persona_by_id($id)
	{
		if (!$id) { return 0; }
		try
		{
			$statement = 'select * from personas where id = :id';
			$sth = $this->_dbh->prepare($statement);
			$sth->bindParam(':id', $id);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log($exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}

		$result = $sth->fetch(PDO::FETCH_ASSOC);
		
		return $result;
	}
	
	function get_persona_by_author($author, $category = null, $sort)
	{
		if (!$author) { return 0; }
		if (!$sort) { $sort = 'all'; }
		$sortkeys = array('all' => 'name', 'recent' => 'submit desc', 'popular' => 'popularity desc');
		try
		{
			$statement = 'select * from personas where status = 1 and author = ?';
			$params = array($author);
			
			if ($category)
			{
				$statement .= " and category = ?";
				$params[] = $category;
			}
			
			$statement .= ' order by ' . $sortkeys[$sort];
			
			$sth = $this->_dbh->prepare($statement);
			$sth->execute($params);
		}
		catch( PDOException $exception )
		{
			error_log($exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}

		$personas = array();
		
		while ($result = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$personas[] = $result;
		}		
		return $personas;
	}
	
	function get_recent_personas($category = null, $limit = null)
	{
		try
		{
			$statement = 'select * from personas where status = 1' . ($category ? " and category = :category" : "") . ' order by approve desc' . ($limit ? " limit  $limit" : "");
			$sth = $this->_dbh->prepare($statement);
			if ($category)
			{
				$sth->bindParam(':category', $category);
			}
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log($exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		
		$personas = array();
		
		while ($result = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$personas[] = $result;
		}		
		return $personas;
	}

	function get_popular_personas($category = null, $limit = null)
	{
		try
		{
			$statement = 'select * from personas where status = 1' . ($category ? " and category = :category" : "") . ' order by popularity desc' . ($limit ? " limit $limit" : "");
			$sth = $this->_dbh->prepare($statement);
			if ($category)
			{
				$sth->bindParam(':category', $category);
			}
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log($exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		
		$personas = array();
		
		while ($result = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$personas[] = $result;
		}		
		return $personas;
	}
	
	function change_persona_category($id, $category)
	{
		try
		{
			$statement = 'update personas set category = :category where id = :id';
			$sth = $this->_dbh->prepare($statement);
			$sth->bindParam(':id', $id);
			$sth->bindParam(':category', $category);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log($exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		
		$personas = array();
		
		while ($result = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$personas[] = $result;
		}		
		return $personas;
	}
	
	function get_pending_personas()
	{
		try
		{
			$statement = 'select * from personas where status = 0 order by submit';
			$sth = $this->_dbh->prepare($statement);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log($exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		
		$personas = array();
		
		while ($result = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$personas[] = $result;
		}		
		return $personas;
	}
	
	
	function get_pending_edits()
	{
		try
		{
			$statement = 'select * from edits limit 1';
			$sth = $this->_dbh->prepare($statement);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log($exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		
		$result = $sth->fetch(PDO::FETCH_ASSOC);
		return $result;
	}
	
	function get_edits_by_id($id)
	{
		try
		{
			$statement = 'select * from edits where id = :id';
			$sth = $this->_dbh->prepare($statement);
			$sth->bindParam(':id', $id);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log($exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		
		$result = $sth->fetch(PDO::FETCH_ASSOC);
		return $result;
	}
	
	
	function get_personas_by_category($category)
	{
		try
		{
			$statement = 'select * from personas where status = 1 and category = :category order by name';
			$sth = $this->_dbh->prepare($statement);
			$sth->bindParam(':category', $category);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log($exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		
		$personas = array();
		
		while ($result = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$personas[] = $result;
		}		
		return $personas;
	}
	
	#see if we're going to get a namespace collision with a persona
	function check_persona_name($name)
	{
		try
		{
			$statement = 'select id from personas where name = :name limit 1';
			$sth = $this->_dbh->prepare($statement);
			$sth->bindParam(':name', $name);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log($exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}

		$id = $sth->fetchColumn();	

		#now make sure someone isn't trying to change to that
		if (!$id)
		{
			try
			{
				$statement = 'select id from edits where name = :name limit 1';
				$sth = $this->_dbh->prepare($statement);
				$sth->bindParam(':name', $name);
				$sth->execute();
			}
			catch( PDOException $exception )
			{
				error_log($exception->getMessage());
				throw new Exception("Database unavailable", 503);
			}			
			$id = $sth->fetchColumn();	
		}
		return $id;
	}
	
	
	function submit_persona($name, $category, $header, $footer, $author, $accent, $text)
	{
		try
		{
			$statement = 'insert into personas (name, status, header, footer, category, submit, author, accentcolor, textcolor) values (:name, 0, :header, :footer, :category, current_timestamp, :author, :accentcolor, :textcolor)';
			$sth = $this->_dbh->prepare($statement);
			$sth->bindParam(':name', $name);
			$sth->bindParam(':header', $header);
			$sth->bindParam(':footer', $footer);
			$sth->bindParam(':category', $category);
			$sth->bindParam(':author', $author);
			$sth->bindParam(':accentcolor', $accent);
			$sth->bindParam(':textcolor', $text);
			$sth->execute();
			return $this->_dbh->lastInsertId();
		}
		catch( PDOException $exception )
		{
			error_log($exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		return 0;
	}
	
	function submit_persona_edit($id, $author, $name, $category, $accent, $text, $header = null, $footer = null)
	{
		try
		{
			$statement = 'replace into edits (id, author, name, header, footer, category,  accentcolor, textcolor) values (:id, :author, :name, :header, :footer, :category,  :accentcolor, :textcolor)';
			$sth = $this->_dbh->prepare($statement);
			$sth->bindParam(':id', $id);
			$sth->bindParam(':author', $author);
			$sth->bindParam(':name', $name);
			$sth->bindParam(':header', $header);
			$sth->bindParam(':footer', $footer);
			$sth->bindParam(':category', $category);
			$sth->bindParam(':accentcolor', $accent);
			$sth->bindParam(':textcolor', $text);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log($exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		return 1;

	}
	
	function approve_persona_edit($id)
	{
		$edits = $this->get_edits_by_id($id);
		
		$update = "update personas set name = ?, category = ?, accentcolor = ?, textcolor = ?";
		$params = array($edits['name'], $edits['category'], $edits['accentcolor'], $edits['textcolor']);
		
		
		if ($edits['header'])
		{
			$update .= ", header = ?";
			$params[] = $edits['header'];
		}
		
		if ($edits['footer'])
		{
			$update .= ", footer = ?";
			$params[] = $edits['footer'];
		}
		
		$update .= " where id = ?";
		$params[] = $id;
		try
		{
			$sth = $this->_dbh->prepare($update);
			$sth->execute($params);
		}
		catch( PDOException $exception )
		{
			error_log($exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}

		try
		{
			$statement = 'delete from edits where id = :id';
			$sth = $this->_dbh->prepare($statement);
			$sth->bindParam(':id', $id);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log($exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		return 1;
		
	}
	
	function reject_persona_edit($id)
	{
		try
		{
			$statement = 'delete from edits where id = :id';
			$sth = $this->_dbh->prepare($statement);
			$sth->bindParam(':id', $id);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log($exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		return 1;
		
	}
	
	function log_action($name, $id, $action)
	{
		try
		{
			$statement = 'insert into log (id, username, action) values (:id, :username, :action)';
			$sth = $this->_dbh->prepare($statement);
			$sth->bindParam(':id', $id);
			$sth->bindParam(':username', $name);
			$sth->bindParam(':action', $action);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log($exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		return 1;
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
			$insert_stmt = 'insert into users (username, md5, email) values (:username, :md5, :email)';
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


	function authenticate_user($username, $password) 
	{
		try
		{
			$select_stmt = 'select count(*) from users where username = :username and md5 = :md5';
			$sth = $this->_dbh->prepare($select_stmt);
			$sth->bindParam(':username', $username);
			$sth->bindParam(':md5', md5($password));
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log("authenticate_user: " . $exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}

		$result = $sth->fetchColumn();
		return $result;
	}
	
	function get_password_md5($username) 
	{
		try
		{
			$select_stmt = 'select md5 from users where username = :username';
			$sth = $this->_dbh->prepare($select_stmt);
			$sth->bindParam(':username', $username);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log("authenticate_user: " . $exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}

		$result = $sth->fetchColumn();
		return $result;
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

	#admin is a flag in the user db that can be set by other admins
	function authenticate_admin($username, $password) 
	{
		try
		{
			$select_stmt = 'select admin from users where username = :username and md5 = :md5';
			$sth = $this->_dbh->prepare($select_stmt);
			$sth->bindParam(':username', $username);
			$sth->bindParam(':md5', md5($password));
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log("authenticate_user: " . $exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}

		$result = $sth->fetchColumn();
		return $result;
	}
	
	function promote_admin($username)
	{
		if (!$username)
		{
			return 0;
		}

		try
		{
			$stmt = 'update users set admin = 1 where username = :username';
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
	
	function get_categories()
	{
		try
		{
			$statement = 'select name from categories order by name';
			$sth = $this->_dbh->prepare($statement);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log($exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		
		$categories = array();
		
		while ($result = $sth->fetch())
		{
			$categories[] = $result[0];
		}		
		return $categories;
	}
	
	function add_category($cname)
	{
		if (!$cname)
		{
			return 0;
		}

		try
		{
			$stmt = 'insert into categories (name) values (:category)';
			$sth = $this->_dbh->prepare($stmt);
			$sth->bindParam(':category', $cname);
			$sth->execute();

		}
		catch( PDOException $exception )
		{
			error_log("add_category: " . $exception->getMessage());
			throw new Exception("Database unavailable");
		}
		return 1;
		
	}

	function category_exists($category) 
	{
		try
		{
			$select_stmt = 'select count(*) from categories where name = :category';
			$sth = $this->_dbh->prepare($select_stmt);
			$sth->bindParam(':category', $category);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log("category_exists: " . $exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}

		$result = $sth->fetchColumn();
		return $result ? 1 : 0;
	}
	
	
	function delete_category($cname)
	{
		if (!$cname)
		{
			return 0;
		}

		try
		{
			$stmt = 'delete from categories where name = :cname';
			$sth = $this->_dbh->prepare($stmt);
			$sth->bindParam(':cname', $cname);
			$sth->execute();

		}
		catch( PDOException $exception )
		{
			error_log("delete category: " . $exception->getMessage());
			throw new Exception("Database unavailable");
		}
		return 1;
	}
	
	
	
	
}
?>