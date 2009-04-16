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
	

require_once 'personas_constants.php';

class PersonaStorage
{
	var $_dbh;
	var $_memcache;
	
	function __construct($username = null, $password = null, $hostname = null, $dbname = null) 
	{
		if (MEMCACHE_PORT)
		{
			$this->memcache_connect();
		}
	}
	
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
	
	function memcache_connect()
	{
		$memc = new Memcache;
		if ($memc->connect('localhost', MEMCACHE_PORT))
			$this->_memcache = $memc;
	}
		
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
			$this->_memcache->delete("p$id");
			$persona = $this->get_persona_by_id($id);
			
			$this->_memcache->delete('ca0' . $persona['category']); #category first all page. Rest will rebuild soon
			$this->_memcache->delete('cr0' . $persona['category']); #category recent page
			$this->_memcache->delete('crAll'); #All recent page			
			$this->_memcache->delete('apcAll'); #All persona count			
			$this->_memcache->delete('apc' . $persona['category']); #Category persona count			
		}

		return 1;
		
	}

	function reject_persona($id)
	{
		if (!$id) { return 0; }

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
		return 1;
	}

	function pull_persona($id)
	{
		$this->reject_persona($id);
		if ($this->_memcache)
		{
			$this->_memcache->delete("p$id");
		}		
	}
		
	function get_persona_by_id($id)
	{
		if (!$id) { return 0; }
		
		if ($this->_memcache)
		{
			$result = $this->_memcache->get("p$id");
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
		
		if ($this->_memcache)
			$this->_memcache->set("p$id", $result, MEMCACHE_DECAY);
			
		return $result;
	}


	#gets the count of personas in a category. Usually used for pagination, back when we had pagination
	#May make a comeback. Also used in compile.php
	function get_active_persona_count($category = null)
	{
		if ($category == 'All')
			$category = null;
			
		if ($this->_memcache)
		{
			$result = $this->_memcache->get("apc" . ($category ? $category : 'All'));
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
			$this->_memcache->set("apc" . ($category ? $category : 'All'), $result, MEMCACHE_DECAY);

		return $sth->fetchColumn();
	}
	
		
	function get_persona_by_author($author, $category = null, $sort = null)
	{
		if (!$author) { return 0; }
		if (!$sort) { $sort = 'all'; }
		$sortkeys = array('all' => 'popularity desc', 'recent' => 'submit desc', 'popular' => 'popularity desc');
		
		if ($this->_memcache)
		{
			$result = $this->_memcache->get("a$author");
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
			$personas[] = $result;
		}		

		if ($this->_memcache)
			$this->_memcache->set("a$author", $personas, MEMCACHE_DECAY);

		return $personas;
	}
	
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
			$personas[] = $result;
		}		
		return $personas;
	}
	
	
	function get_recent_personas($category = null, $limit = null, $offset = null)
	{
		$key = null;
		
		if ($this->_memcache)
		{
			if ($limit)
				$key = "cr$offset" . ($category ? $category : 'All');
			else
				$key = "ca" . ($category ? $category : 'All');
				
			$result = $this->_memcache->get($key);
			if ($result)
				return $result;
		}

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
			$personas[] = $result;
		}	
		
		if ($this->_memcache)
			$this->_memcache->set($key, $personas, MEMCACHE_DECAY);
		
		return $personas;
	}

	function get_popular_personas($category = null, $limit = null, $offset = null)
	{
		if ($category == 'All')
			$category = null;
		
		if ($this->_memcache)
		{
			$result = $this->_memcache->get("cp" . ($category ? $category : 'All'));
			if ($result)
				return $result;
		}

		if (!$this->_dbh)
			$this->db_connect();		
		
		try
		{
			$statement = 'select * from personas where status = 1' . ($category ? " and category = :category" : "") . ' and (popularity > 0 or license = "restricted") order by popularity desc' . ($limit ? " limit $limit" : "") . ($offset ? " offset $offset" : "");
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

		if ($this->_memcache)
			$this->_memcache->set("cp" . ($category ? $category : 'All'), $personas, MEMCACHE_DECAY);

		return $personas;
	}
	
	function get_featured_personas()
	{
		if ($this->_memcache)
		{
			$result = $this->_memcache->get("feat");
			if ($result)
				return $result;
		}

		if (!$this->_dbh)
			$this->db_connect();		

		try
		{
			$statement = 'select * from personas where status = 1 and featured > 0 order by featured desc';
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

		if ($this->_memcache)
			$this->_memcache->set("feat", $personas, MEMCACHE_DECAY);

		return $personas;
	}

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
	
	function change_persona_category($id, $category)
	{
		#no need for memcache here, as it's usually pre-approval.
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
	
	
	
	#see if we're going to get a namespace collision with a persona
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
	
	
	function submit_persona($name, $category, $header, $footer, $author, $accent, $text, $desc, $license, $reason, $reasonother)
	{
		if (!$this->_dbh)
			$this->db_connect();		

		try
		{
			$statement = 'insert into personas (name, status, header, footer, category, submit, author, accentcolor, textcolor, description, license, reason, reason_other) values (:name, 0, :header, :footer, :category, NOW(), :author, :accentcolor, :textcolor, :description, :license, :reason, :reasonother)';
			$sth = $this->_dbh->prepare($statement);
			$sth->bindParam(':name', $name);
			$sth->bindParam(':header', $header);
			$sth->bindParam(':footer', $footer);
			$sth->bindParam(':category', $category);
			$sth->bindParam(':author', $author);
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
	
	function approve_persona_edit($id)
	{
		if (!$this->_dbh)
			$this->db_connect();		

		$edits = $this->get_edits_by_id($id);
		
		#Don't need memcache deletes here. Odds are it isn't on any front pages that require urgency
		
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
			$this->_memcache->set("categories", $categories, MEMCACHE_DECAY);

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

		if ($this->_memcache)
		{
			$this->_memcache->delete("categories");
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

		if ($this->_memcache)
		{
			$this->_memcache->delete("categories");
		}
		return 1;
	}
	
}
?>