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
# Mysql interface to the personas tables and memcache layer in front of them
#
# The tables are as follows:
#
# categories
# edits
# log
# personas
# users

#####
# users table:
#  username - login name, and name for use in urls. Unique, and used to generate a designer page
#  display_username - username to be displayed through the site
#  md5 - md5 encoded password. Should not be retrieved, just looked up based on a hash of the password provided
#  email - user email address
#  privs - privilege level: 0) disabled, 1) active, 2) approver, 3) admin
#  change_code - if they ask to change their password, the temporary url code is stored here.
#  news - whether they checked the 'send me news' box on signup
#  description - user-written description for display on their designer page

#####
# personas table:
#  id  - autoincrementing integer for a unique persona id
#  name - name given to the persona by the user
#  header - filename of the header file. Because we don't know the type, we cannot have a uniform name
#  footer - filename of the footer file
#  category - gallery category the persona goes into
#  status - current status of the persona: 0) pending, 1) live, 2) rejected, 3) flagged for legal
#  submit - date of submission
#  approve - date of last editorial judgement
#  author - username of the submitter
#  display_username - display name of the submitter
#  accentcolor - color for the accent bars
#  textcolor - color for the text
#  popularity - nightly popularity metric calculated and put in here by the stats team
#  description - displayed descrption for the persona
#  license - creative commons (cc) or restricted
#  reason  - reason for creating the persona
#  reason_other - if they chose 'other' in reason
#  featured - no longer used, since we put the features into the config file. TODO: remove during uplift
#  locale varchar(2) - hardcoded constant for each locale (currently US and CN)
#
# The sharp-eyed among you will notice that the db is denormalized. This is for speed of lookup, and
# also so that China can import personas from the US version without importing the user table 
# (designer collisions are 'avoided' by using the locale)

#####
# edits table - we use this to hold the editable values of the persona while it's being approved
# most of this matches the persona table, and represents the user-editable fields:
#  id - persona id being edited
#  author - name of the user submitting the edit. This is usually the persona author, but can be an admin
#  name
#  header
#  footer
#  category
#  accentcolor
#  textcolor
#  description
#  reason
#  reason_other
#  submit  - date of submission

#####
# categories table:
#  id - category id
#  name - name displayed in the gallery and used in urls

#####
# favorites table
#  user - username of the user with a favorite
#  id - id of the favorite
#  added - date added

#####
# Memcache keys used by this module: 
#  p:<persona id> - data for a single persona
#  ca:<page>:<category> - gallery 'All' pages entries for a category
#  cr:<category> - gallery 'Recent' page entries for a category
#  cp:<category> - gallery 'Popular' page entries for a category
#  cm:<category> - movers and shakers for a category
#  pc:<category> - current persona count for a category
#  au:<author>:<category> - personas from an author
#  fav:<author>:<category> - favorite personas
#  categories - a list of categories

require_once 'personas_constants.php';

class PersonaStorage
{
	var $_dbh;
	var $_memcache;
	
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
# Gets the persona data for a single persona.

	function get_persona_by_id($id)
	{
		if (!$id) { return 0; }
		
		if ($this->_memcache)
		{
			$result = $this->_memcache->get("p:$id");
			if ($result)
				return $result;
		}
		
		if (!$this->_dbh)
			$this->db_connect();

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
		
		if (!$result['display_username'])
			$result['display_username'] = $result['author'];
			
		if ($this->_memcache)
			$this->_memcache->set("p:$id", $result, false, MEMCACHE_DECAY);
			
		return $result;
	}


#####
# Gets the count of personas in a category. 
# Used for pagination on the all pages. TODO: add to the head of the other pages


	function get_active_persona_count($category = null)
	{
		if ($category == 'All')
			$category = null;
			
		if ($this->_memcache)
		{
			$result = $this->_memcache->get("pc:" . ($category ? $category : 'All'));
			if ($result)
				return $result;
		}

		if (!$this->_dbh)
			$this->db_connect();

		try
		{
			$statement = 'select count(*) from personas where status = 1' . ($category ? " and category = :category" : "");
			$sth = $this->_dbh->prepare($statement);
			if ($category)
				$sth->bindParam(':category', $category);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log($exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		
		if ($this->_memcache)
			$this->_memcache->set("pc:" . ($category ? $category : 'All'), $result, false, MEMCACHE_DECAY);

		return $sth->fetchColumn();
	}
	
#####
# Gets all personas associated with an author. Category and sort are optional filters. Will only
# memcache if the sort is popular (which is the null default)

	function get_persona_by_author($author, $category = null, $sort = null)
	{
		if (!$author) { return array(); }
		if (!$sort) { $sort = 'all'; }
		$sortkeys = array('all' => 'popularity desc', 'recent' => 'submit desc', 'popular' => 'popularity desc');
		
		if ($this->_memcache && $sort != 'recent')
		{
			$result = $this->_memcache->get("au:$author:$category");
			if ($result)
				return $result;
		}

		if (!$this->_dbh)
			$this->db_connect();		
		
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
			if (!$result['display_username'])
				$result['display_username'] = $result['author'];
			$personas[] = $result;
		}		

		if ($this->_memcache && $sort != 'recent')
			$this->_memcache->set("au:$author:$category", $personas, false, MEMCACHE_DECAY);

		return $personas;
	}


#####
# Returns the last PERSONA_GALLERY_PAGE_SIZE personas to be approved (filtered by category)


	function get_recent_personas($category = null)
	{
		if ($category == 'All')
			$category = null;

		if ($this->_memcache)
		{
			$result = $this->_memcache->get('cr:' . ($category ? $category : 'All'));
			if ($result)
				return $result;
		}

		if (!$this->_dbh)
			$this->db_connect();		
		
		try
		{
			$statement = 'select * from personas where status = 1' . ($category ? " and category = :category" : "") . ' order by approve desc limit ' . PERSONA_GALLERY_PAGE_SIZE;
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
			if (!$result['display_username'])
				$result['display_username'] = $result['author'];
			$personas[] = $result;
		}	
		
		if ($this->_memcache)
			$this->_memcache->set('cr:' . ($category ? $category : 'All'), $personas, false, MEMCACHE_DECAY);
		
		return $personas;
	}


#####
# Returns a page (PERSONA_GALLERY_ALL_PAGE_SIZE) worth of active personas, 
# possibly filtered by $category. Specify a greater $page for later pages

	function get_all_personas($category = null, $page = 0)
	{
		if ($category == 'All')
			$category = null;
		
		if ($this->_memcache)
		{				
			$result = $this->_memcache->get('ca:' . $page . ':' . ($category ? $category : 'All'));
			if ($result)
				return $result;
		}

		$offset = $page * PERSONA_GALLERY_ALL_PAGE_SIZE;
		$limit = PERSONA_GALLERY_ALL_PAGE_SIZE;
		
		if (!$this->_dbh)
			$this->db_connect();		
		
		try
		{
			$statement = 'select * from personas where status = 1' . ($category ? " and category = :category" : "") . ' order by approve desc' . ($limit ? " limit $limit" : "") . ($offset ? " offset $offset" : "");
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
			if (!$result['display_username'])
				$result['display_username'] = $result['author'];
			$personas[] = $result;
		}	
		
		if ($this->_memcache)
			$this->_memcache->set('ca:' . $page . ':' . ($category ? $category : 'All'), $personas, false, MEMCACHE_DECAY);
		
		return $personas;
	}

#####
# Returns the most popular PERSONA_GALLERY_PAGE_SIZE personas (optionally filtered by category)

	function get_popular_personas($category = null)
	{
		if ($category == 'All')
			$category = null;
		
		if ($this->_memcache)
		{
			$result = $this->_memcache->get("cp:" . ($category ? $category : 'All'));
			if ($result)
				return $result;
		}

		if (!$this->_dbh)
			$this->db_connect();		
		
		try
		{
			$statement = 'select * from personas where status = 1' . ($category ? " and category = :category" : "") . ' and (popularity > 0 or license = "restricted") order by popularity desc limit ' . PERSONA_GALLERY_PAGE_SIZE;
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
			if (!$result['display_username'])
				$result['display_username'] = $result['author'];
			$personas[] = $result;
		}		

		if ($this->_memcache)
			$this->_memcache->set("cp:" . ($category ? $category : 'All'), $personas, false, MEMCACHE_DECAY);

		return $personas;
	}

#####
# Returns the most active movers and shakers from the db - personas that are seeing big changes in numbers

	function get_movers($category = null)
	{
		if ($category == 'All')
			$category = null;
		
		if ($this->_memcache)
		{
			$result = $this->_memcache->get("cm:" . ($category ? $category : 'All'));
			if ($result)
				return $result;
		}

		if (!$this->_dbh)
			$this->db_connect();		
		
		try
		{
			$statement = 'select * from personas where status = 1' . ($category ? " and category = :category" : "") . ' order by movers desc limit ' . PERSONA_GALLERY_PAGE_SIZE;
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
			if (!$result['display_username'])
				$result['display_username'] = $result['author'];
			$personas[] = $result;
		}		

		if ($this->_memcache)
			$this->_memcache->set("cm:" . ($category ? $category : 'All'), $personas, false, MEMCACHE_DECAY);

		return $personas;
		
	}

#####
# Searches through the name and description for the requested keywords. Will give you a 
# PERSONA_GALLERY_PAGE_SIZE worth unless you specify another limit
	
	function search_personas($string, $category = null, $limit = null)
	{
		$string = str_replace(',', ' ', $string);

		if (!$this->_dbh)
			$this->db_connect();		

		try
		{
			$statement = 'select *, match(name, description) AGAINST(:string1 in boolean mode) AS score from personas where status = 1 ';
			if ($category && $category != 'All')
				$statement .= 'and category = :category ';
			$statement .= 'and match(name, description) against(:string2 in boolean mode)';
			$statement .= ' order by score desc limit ' . ($limit ? $limit : PERSONA_GALLERY_PAGE_SIZE);
			$sth = $this->_dbh->prepare($statement);
			$sth->bindParam(':string1', $string);
			$sth->bindParam(':string2', $string);
			if ($category && $category != 'All')
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
			if (!$result['display_username'])
				$result['display_username'] = $result['author'];
			$personas[] = $result;
		}		
		return $personas;
	}


#####
# Checks to see if a persona name exists. Useful for preempting possible namespace collisions
# Also checks the edit table to make sure nobody has asked to change to the requested name

	function check_persona_name($name)
	{
		if (!$this->_dbh)
			$this->db_connect();		

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


#####
# Updates the display username for an author in the personas table. Needed to preserve the 
# denormalization of the table, and called when a user updates their entry in the user table.
	
	function update_display_username($author, $display_username)
	{
		if (!$this->_dbh)
			$this->db_connect();		

		try
		{
			$statement = 'update personas set display_username = :display where author = :author';
			$sth = $this->_dbh->prepare($statement);
			$sth->bindParam(':display', $display_username);
			$sth->bindParam(':author', $author);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log("update_display_username: " . $exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		return 0;
	}
	

#####
# Write persona data into the table. Assumes all the appropriate namespace collisions, etc, have
# been checked for

	function submit_persona($name, $category, $header, $footer, $author, $display_username, $accent, $text, $desc, $license, $reason, $reasonother)
	{
		if (!$this->_dbh)
			$this->db_connect();		

		try
		{
			$statement = 'insert into personas (name, status, header, footer, category, submit, author, display_username, accentcolor, textcolor, description, license, reason, reason_other, locale) values (:name, 0, :header, :footer, :category, NOW(), :author, :display_username, :accentcolor, :textcolor, :description, :license, :reason, :reasonother, "' . PERSONAS_LOCALE . '")';
			$sth = $this->_dbh->prepare($statement);
			$sth->bindParam(':name', $name);
			$sth->bindParam(':header', $header);
			$sth->bindParam(':footer', $footer);
			$sth->bindParam(':category', $category);
			$sth->bindParam(':author', $author);
			$sth->bindParam(':display_username', $display_username);
			$sth->bindParam(':accentcolor', $accent);
			$sth->bindParam(':textcolor', $text);
			$sth->bindParam(':description', $desc);
			$sth->bindParam(':license', $license);
			$sth->bindParam(':reason', $reason);
			$sth->bindParam(':reasonother', $reasonother);
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
	
#####
# Adds edit data to the edit table
	
	function submit_persona_edit($id, $author, $name, $category, $accent, $text, $desc, $header = null, $footer = null, $reason = null, $reason_other = null)
	{
		if (!$this->_dbh)
			$this->db_connect();		

		try
		{
			$statement = 'replace into edits (id, author, name, header, footer, category,  accentcolor, textcolor, description, reason, reason_other, submit) values (:id, :author, :name, :header, :footer, :category,  :accentcolor, :textcolor, :description, :reason, :reasonother, NOW())';
			$sth = $this->_dbh->prepare($statement);
			$sth->bindParam(':id', $id);
			$sth->bindParam(':author', $author);
			$sth->bindParam(':name', $name);
			$sth->bindParam(':header', $header);
			$sth->bindParam(':footer', $footer);
			$sth->bindParam(':category', $category);
			$sth->bindParam(':accentcolor', $accent);
			$sth->bindParam(':textcolor', $text);
			$sth->bindParam(':description', $desc);
			$sth->bindParam(':reason', $reason);
			$sth->bindParam(':reasonother', $reasonother);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log($exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		return 1;

	}
	

#####
# Logs any action in the system that writes to the tables - submits, approvals, edits, deletes, etc
# Does not log enough detail to reconstruct, just enough to monitor
# Logs user, approver and admin actions

	function log_action($name, $id, $action)
	{
		if (!$this->_dbh)
			$this->db_connect();		

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
	

#####
# Get a list of all the categories

	function get_categories()
	{
		if ($this->_memcache)
		{
			$result = $this->_memcache->get("categories");
			if ($result)
				return $result;
		}

		if (!$this->_dbh)
			$this->db_connect();		

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

		if ($this->_memcache)
			$this->_memcache->set("categories", $categories, false, MEMCACHE_DECAY);

		return $categories;
	}
	
	
#####
# Gets all personas that a user has 'favorited'. Category is optional
	
	function get_user_favorites($username, $category = null)
	{
		if (!$username)
			return array();
		
		if ($category == 'All')
			$category = null;
			
		if ($this->_memcache)
		{
			$result = $this->_memcache->get('fav:' . $username . ':' . ($category ? $category : 'All'));
			if ($result)
				return $result;
		}

		if (!$this->_dbh)
			$this->db_connect();		

		try
		{
			$statement = 'select personas.*, favorites.added from personas, favorites where personas.id = favorites.id and favorites.username = ?';
			$params = array($username);
			if ($category)
			{
				$statement .= " and personas.category = ?";
				$params[] = $category;
			}
			$statement .= ' order by favorites.added desc';
			$sth = $this->_dbh->prepare($statement);
			$sth->execute($params);
		}
		catch( PDOException $exception )
		{
			error_log($exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		
		$results = array();
		
		while ($result = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$results[] = $result;
		}		

		if ($this->_memcache)
			$this->_memcache->set('fav:' . $username . ':' . ($category ? $category : 'All'), $results, false, MEMCACHE_DECAY);

		return $results;
		
	}


#####
# Returns a boolean if a user has favorited a persona
# Memcache isn't terribly useful here, since we can't really decay it usefully. Fortunately, this 
# only gets called on detail pages, so it shouldn't be hammered. If we start using it elsewhere,
# may need to reconsider

	function is_favorite_persona($username, $persona_id)
	{
		if (!$username || !$persona_id)
			return 0;

		if (!$this->_dbh)
			$this->db_connect();		

		try
		{
			$select_stmt = 'select * from favorites where username = :username and id = :id limit 1';
			$sth = $this->_dbh->prepare($select_stmt);
			$sth->bindParam(':username', $username);
			$sth->bindParam(':id', $persona_id);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log("is_favorite: " . $exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}

		$result = $sth->fetchColumn();
		return $result ? 1 : 0;
	
	}

#####
# Add a persona to a user's favorites. Will simply overwrite with a new timestamp if it's 
# already there (which makes sense from the user perspective, as it moves it to the top)


	function add_user_favorite($username, $persona_id)
	{
		if (!$username || !$persona_id)
			return 0;
			
		if (!$this->_dbh)
			$this->db_connect();		

		try
		{
			$stmt = 'replace into favorites (username, id, added) values (:username, :id, NOW())';
			$sth = $this->_dbh->prepare($stmt);
			$sth->bindParam(':username', $username);
			$sth->bindParam(':id', $persona_id);
			$sth->execute();

		}
		catch( PDOException $exception )
		{
			error_log("add_user_favorite: " . $exception->getMessage());
			throw new Exception("Database unavailable");
		}

		if ($this->_memcache)
		{
			$persona = $this->get_persona_by_id($persona_id);
			$this->_memcache->delete('fav:' . $username . ':' . $persona['category']);
			$this->_memcache->delete('fav:' . $username . ':' . 'All');
		}

		return 1;		
		
	}


#####
# Delete a persona from a user's favorites


	function delete_user_favorite($username, $persona_id)
	{
		if (!$username || !$persona_id)
			return 0;

		if (!$this->_dbh)
			$this->db_connect();		

		try
		{
			$stmt = 'delete from favorites where username = :username and id = :id';
			$sth = $this->_dbh->prepare($stmt);
			$sth->bindParam(':username', $username);
			$sth->bindParam(':id', $persona_id);
			$sth->execute();

		}
		catch( PDOException $exception )
		{
			error_log("delete favorite: " . $exception->getMessage());
			throw new Exception("Database unavailable");
		}

		if ($this->_memcache)
		{
			$persona = $this->get_persona_by_id($persona_id);
			$this->_memcache->delete('fav:' . $username . ':' . $persona['category']);
			$this->_memcache->delete('fav:' . $username . ':' . 'All');
		}
		return 1;
		
	}


########################################
# ADMIN FUNCTIONS


	
#####
# Flip the status bit on the persona to 1. Also need to purge a bunch of memcache categories

	function approve_persona($id)
	{
		if (!$id) { return 0; }

		if (!$this->_dbh)
			$this->db_connect();
		
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

		if ($this->_memcache)
		{
			$this->_memcache->delete("p:$id");
			$persona = $this->get_persona_by_id($id);
			
			$this->_memcache->delete('ca:0:' . $persona['category']); #category first all page. Rest will rebuild soon
			$this->_memcache->delete('cr:' . $persona['category']); #category recent page
			$this->_memcache->delete('cr:All'); #All recent page			
			$this->_memcache->delete('pc:All'); #All persona count			
			$this->_memcache->delete('pc:' . $persona['category']); #Category persona count			
			$this->_memcache->delete('au:' . $persona['author'] . ':'); #author all 
			$this->_memcache->delete('au:' . $persona['author'] . ':' . $persona['category']); #author by category
		}

		return 1;
		
	}


#####
# Flips a persona to rejected status and pulls it from memcache if necessary


	function reject_persona($id)
	{
		if (!$id) { return 0; }
		$persona = $this->get_persona_by_id($id);

		if (!$this->_dbh)
			$this->db_connect();
		
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

		if ($this->_memcache)
		{
			$this->_memcache->delete("p:$id");
			
			if ($persona['status'] != 0)
			{			
				#if the persona was previously live, we need to purge the caches. If it wasn't, 
				#then these would be unchanged
				
				$this->_memcache->delete('ca:0:' . $persona['category']); #category first all page. Rest will rebuild soon
				$this->_memcache->delete('cr:' . $persona['category']); #category recent page
				$this->_memcache->delete('cr:All'); #All recent page			
				$this->_memcache->delete('pc:All'); #All persona count			
				$this->_memcache->delete('pc:' . $persona['category']); #Category persona count			
				$this->_memcache->delete('au:' . $persona['author'] . ':'); #author all 
				$this->_memcache->delete('au:' . $persona['author'] . ':' . $persona['category']); #author by category
			}
		}

		return 1;
	}

#####
# Flip the status of a persona to be flagged for legal.

	function flag_persona_for_legal($id)
	{
		#no need for memcache here, as it's usually pre-approval.
		if (!$this->_dbh)
			$this->db_connect();		

		try
		{
			$statement = 'update personas set status = 3 where id = :id';
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
	

#####
# And the inverse, getting legal all those flagged personas.

	function get_legal_flagged_personas()
	{

		if (!$this->_dbh)
			$this->db_connect();		
		
		try
		{
			$statement = 'select * from personas where status = 3';
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
	

#####
# Given a partial string, return all personas that have that in their name

	function find_persona_by_name($partial_name)
	{
		if (!$partial_name) { return array(); }
		if (!$this->_dbh)
			$this->db_connect();		
		
		try
		{
			$statement = 'select * from personas where name like ? order by id desc';
			
			$sth = $this->_dbh->prepare($statement);
			$sth->execute(array('%' . $partial_name . '%'));
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



#####
# This gets all personas done by an author, including the rejected ones. For admin use


	function get_all_submissions($author)
	{
		if (!$author) { return array(); }
		if (!$this->_dbh)
			$this->db_connect();		
		
		try
		{
			$statement = 'select * from personas where author = ? order by id desc';
			
			$sth = $this->_dbh->prepare($statement);
			$sth->execute(array($author));
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

#####
# Changes a persona category. This is separate from editing because a) it doesn't require approval
# if an admin is doing it and b) it's a lot more common. Should only be available to admins
	

	function change_persona_category($id, $category)
	{
		#no need for memcache here, as it's usually pre-approval and will work 
		#itself out reasonably quickly.
		if (!$this->_dbh)
			$this->db_connect();		

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
		
		return 1;
	}
	

#####
# Gets a list of all personas pending approval

	function get_pending_personas($category = null)
	{
		if (!$this->_dbh)
			$this->db_connect();		

		try
		{
			$statement = 'select * from personas where status = 0' . ($category ? " and category = :category" : "") . ' order by submit';
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
	
#####
# Gets a list of all edits pending approval

	function get_pending_edits($category = null)
	{
		if (!$this->_dbh)
			$this->db_connect();		

		try
		{
			$statement = 'select * from edits' . ($category ? " where category = :category" : "");
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
		
		$edits = array();
		while ($result = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$edits[] = $result;
		}		
		return $edits;
	}

#####
# Gets the specific information about a requested edit. Returns an object that looks a lot like 
# a persona object, only with some fields potentially empty

	function get_edits_by_id($id)
	{
		if (!$this->_dbh)
			$this->db_connect();		

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
	
#####
# Approves an edit. Currently, not purging memcache - odds of an edit being an emergency and
# on the front pages is very low, and a brief delay due to cache decay doesn't seem like a problem
	
	function approve_persona_edit($id)
	{
		if (!$this->_dbh)
			$this->db_connect();		

		$edits = $this->get_edits_by_id($id);
				
		$update = "update personas set name = ?, category = ?, accentcolor = ?, textcolor = ?, description = ?";
		$params = array($edits['name'], $edits['category'], $edits['accentcolor'], $edits['textcolor'], $edits['description']);
		
		
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


#####
# Rejects a persona edit. Note that this rejects the edit, not the persona. It leaves the persona
# in its original (presumably approved) state
	
	function reject_persona_edit($id)
	{
		if (!$this->_dbh)
			$this->db_connect();		

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

#####
# Get the log history for a particular persona
	
	function get_log_by_persona_id($id)
	{
		if (!$this->_dbh)
			$this->db_connect();		

		try
		{
			$statement = 'select * from log where id = :id order by date';
			$sth = $this->_dbh->prepare($statement);
			$sth->bindParam(':id', $id);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log($exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		
		$logs = array();
		
		while ($result = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$logs[] = $result;
		}		

		return $logs;
		
	}

#####
# Adds a category to the category list. Use with great caution

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

		if ($this->_memcache)
		{
			$this->_memcache->delete("categories");
		}

		return 1;		
	}

#####
# Checks to see if a category exists and returns a boolean

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
	
#####
# Deletes a category. Use with great caution. Note that it does not change the category of personas
# so any persona in a deleted category will simply disappear from the gallery.

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

		if ($this->_memcache)
		{
			$this->_memcache->delete("categories");
		}
		return 1;
	}
	
#######################################################
# FUNCTIONS TO TRY TO MAKE CHINA WORK 
# Still messing with these some. It's seriously hacky

	function get_log_by_date($date)
	{
		if (!$this->_dbh)
			$this->db_connect();		

		try
		{
			$statement = 'select * from log where date(date) = :dt order by date';
			$sth = $this->_dbh->prepare($statement);
			$sth->bindParam(':dt', $date);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log($exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		
		$logs = array();
		
		while ($result = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$logs[] = $result;
		}		

		return $logs;
	
	}
    
    function get_detailed_admin_logs($limit = 100) {
        if (!$this->_dbh)
			$this->db_connect();		

        $limit = (int)$limit;
		try
		{
			$statement = "select * from log, personas where log.id = personas.id and action != 'Added' order by date DESC limit $limit";
			$sth = $this->_dbh->prepare($statement);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log($exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		
		$logs = array();
		
		while ($result = $sth->fetch(PDO::FETCH_ASSOC))
		{
			$logs[] = $result;
		}		

		return $logs;
    }
    

	function direct_persona_input($id, $name, $category, $header, $footer, $author, $display_username, $accent, $text, $desc, $license, $reason, $reasonother) #used for imports
	{
		if (!$this->_dbh)
			$this->db_connect();		

		try
		{
			$statement = 'replace into personas (id, name, status, header, footer, category, submit, approve, author, display_username, accentcolor, textcolor, description, license, reason, reason_other, locale) values (:id, :name, 1, :header, :footer, :category, NOW(), NOW(), :author, :accentcolor, :textcolor, :description, :license, :reason, :reasonother, "' . PERSONAS_LOCALE . '")';
			$sth = $this->_dbh->prepare($statement);
			$sth->bindParam(':id', $id);
			$sth->bindParam(':name', $name);
			$sth->bindParam(':header', $header);
			$sth->bindParam(':footer', $footer);
			$sth->bindParam(':category', $category);
			$sth->bindParam(':author', $author);
			$sth->bindParam(':display_username', $display_username);
			$sth->bindParam(':accentcolor', $accent);
			$sth->bindParam(':textcolor', $text);
			$sth->bindParam(':description', $desc);
			$sth->bindParam(':license', $license);
			$sth->bindParam(':reason', $reason);
			$sth->bindParam(':reasonother', $reasonother);
			$sth->execute();
			return 1;
		}
		catch( PDOException $exception )
		{
			error_log($exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		return 0;
	}

#######################################################
# PRE-UPLIFT HELPER FUNCTIONS

#####
# This function is just used by the compile script to know which pages to build. Once we're 
# uplifted, this function can be deleted


	function get_active_persona_ids($category = null)
	{
		#no memcache here, this is just used for site compilation
		if (!$this->_dbh)
			$this->db_connect();		
		
		try
		{
			$statement = 'select id from personas where status = 1' . ($category ? " and category = :category" : "");
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
		
		while ($result = $sth->fetchColumn())
		{
			if (!$result['display_username'])
				$result['display_username'] = $result['author'];
			$personas[] = $result;
		}		
		return $personas;
	}

#####
# This function is just for the compilation script and can be removed after uplift
	function get_active_designers()
	{
		if (!$this->_dbh)
			$this->db_connect();		

		try
		{
			$statement = 'select distinct(author) from personas where status = 1';
			$sth = $this->_dbh->prepare($statement);
			$sth->execute();
		}
		catch( PDOException $exception )
		{
			error_log($exception->getMessage());
			throw new Exception("Database unavailable", 503);
		}
		
		$result = $sth->fetchAll(PDO::FETCH_COLUMN);
		return $result;
	
	}



}
?>