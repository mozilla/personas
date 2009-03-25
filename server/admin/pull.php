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

	header('Cache-Control: no-store, must-revalidate, post-check=0, pre-check=0, private, max-age=0');
	header('Pragma: private');
	
	try 
	{
		$user = new PersonaUser();
		$user->authenticate(1);
		if (!$user->has_admin_privs())
		{
			$error = "This account does not have privileges for this operation. Please log in with an account that does.";
			$user->auth_form();
			exit;
		}
	}
	catch(Exception $e)
	{
		error_log($e->getMessage());
		print("Database problem. Please try again later.");
		exit;
	}

?>

<html>
<body>
	
<?
	try
	{
		$db = new PersonaStorage();
		
		if (array_key_exists('id', $_GET))
		{
			$persona = $db->get_persona_by_id($_GET['id']);
			$persona_id = $persona['id'];
			$second_folder = $persona_id%10;
			$first_folder = ($persona_id%100 - $second_folder)/10;	
			$persona_path = "/" . $first_folder;
			if (!is_dir(PERSONAS_PENDING_PREFIX . $persona_path)) { mkdir(PERSONAS_PENDING_PREFIX . $persona_path); }
			$persona_path .= "/" . $second_folder;
			if (!is_dir(PERSONAS_PENDING_PREFIX . $persona_path)) { mkdir(PERSONAS_PENDING_PREFIX . $persona_path); }
			$persona_path .= "/" . $persona_id;
			rename (PERSONAS_STORAGE_PREFIX . $persona_path, PERSONAS_PENDING_PREFIX . $persona_path);
			$db->reject_persona($persona{'id'});
			print "<div>Persona $persona_id pulled</div>";
		}
	}
	catch(Exception $e)
	{
		error_log($e->getMessage());
		print("Database problem. Please try again later.");
		exit;
	}
	
?>	
<form action="pull.php" method="POST">
Pull persona id: <input type=text name=id>
<input type="submit" name="verdict" value="pull">
</form>
</body>
</html>
