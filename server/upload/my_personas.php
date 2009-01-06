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
	
	require_once '../lib/personas_constants.php';
	require_once '../lib/html_generation.php';	
	require_once '../lib/storage.php';
	require_once '../lib/user.php';

	try 
	{
		$user = new PersonaUser();
		$user->authenticate();
	}
	catch(Exception $e)
	{
		error_log($e->getMessage());
		print("Database problem. Please try again later.");
		exit;
	}

	$error = "";
	
	try
	{
		$db = new PersonaStorage();
		$categories = $db->get_categories();
				
		$category = array_key_exists('category', $_GET) ? $_GET['category'] : null;
		$sort = array_key_exists('type', $_GET) ? $_GET['type'] : null;
		$personas = $db->get_persona_by_author($user->get_username(), $category, $sort);
	}
	catch (Exception $e)
	{
		$error = "An error occured: " . $e->getMessage();
	}
	print html_page('recent', '', $personas, $_SERVER['PHP_SELF'], "<form method=POST enctype='multipart/form-data' 
								action=\"" . $_SERVER['REQUEST_URI']  . "\">
								Personas by " . $user->get_username() . " <input type=submit name=\"logout\" value=\"not " . $user->get_username() . "?\">
								</form>", 1);
	
?>

