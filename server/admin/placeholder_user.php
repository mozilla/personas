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
	require_once '../lib/storage.php';
	require_once '../lib/user.php';
		
	$username = array_key_exists('create_username', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['create_username']) : $_POST['create_username']) : null;
	$secret = array_key_exists('secret', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['secret']) : $_POST['secret']) : null;

	if (!$secret == ADMIN_SECRET)
		exit("Bad Secret");
	
	if (!$username)
		exit("No username provided");

	if (!preg_match('/^[A-Z0-9\._-]+$/i', $username)) 
		exit("Illegal characters in the login name (alphanumerics, period, underscore and dash only)");
	elseif (strlen($username) > 32)
		exit("Please limit your login name to 32 characters or less");
	elseif (strlen($username) < 6)
		exit("Please use at least 6 characters in your login name");
		
	try 
	{
		$user = new PersonaUser();
		if ($user->user_exists($username))
			exit("User Exists");

		echo $user->create_placeholder_user($username);

	}
	catch(Exception $e)
	{
		error_log($e->getMessage());
		exit("Database problem. Please try again later.");
	}

?>

