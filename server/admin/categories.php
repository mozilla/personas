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
# The Original Code is Weave Basic Object Server
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
	
	require_once '../lib/personas_constants.php';
	require_once '../lib/storage.php';

	$db = new PersonaStorage();
	$categories = $db->get_categories();
	
	$auth_user = array_key_exists('PHP_AUTH_USER', $_SERVER) ? $_SERVER['PHP_AUTH_USER'] : null;
	$auth_pw = array_key_exists('PHP_AUTH_PW', $_SERVER) ? $_SERVER['PHP_AUTH_PW'] : null;
	
	$result = "";
	
	#Auth the user
	try 
	{
		if (!$db->authenticate_admin($auth_user, $auth_pw))
		{
			header('HTTP/1.1 Unauthorized',true,401);
			header('WWW-Authenticate: Basic realm="PersonasAdmin"');
			exit;
		}


		if (array_key_exists('function', $_POST))
		{
			$category = ini_get('magic_quotes_gpc') ? stripslashes($_POST['category']) : $_POST['category'];
			switch ($_POST['function'])
			{
				case 'create':
					if ($db->category_exists($category))
					{
						$result = "Category already exists";
					}
					if ($db->add_category($category))
					{
						$result = "Category created";
					}
					break;
				case 'delete':
					if ($db->delete_category($category))
					{
						$result = "Category deleted";
					}
					break;			
				default:
					$result = "Unknown function";	
					exit;
			}
		}
		
		
		$categories = $db->get_categories();
	}
	catch(Exception $e)
	{
		throw new Exception("Database problem. Please try again later.");
	}
	
?>
<html>
<body>
<div id="message"><?= $result ?></div>
Current categories:
<ul>
<?php
	foreach ($categories as $category)
	{
		print '<form action="categories.php" method="POST">';
		print "<input type=hidden name=category value=\"$category\">\n";
		print "<input type=hidden name=function value=\"delete\">\n";
		print "<input type=\"submit\" value=\"Delete\"> $category\n";
		print '</form>';
	}
?>
</ul>
<p>
<form action="categories.php" method="POST">
<input type=hidden name=function value="create">
Create a new category: <input type=text name=category><input type=submit value="Add">
</form>
</body>
</html>
