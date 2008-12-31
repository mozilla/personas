<html>
<body>
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

	$db = new PersonaStorage();
	
	$auth_user = array_key_exists('PHP_AUTH_USER', $_SERVER) ? $_SERVER['PHP_AUTH_USER'] : null;
	$auth_pw = array_key_exists('PHP_AUTH_PW', $_SERVER) ? $_SERVER['PHP_AUTH_PW'] : null;
		
	#Auth the user
	try 
	{
		if (!$db->authenticate_admin($auth_user, $auth_pw))
		{
			header('HTTP/1.1 Unauthorized',true,401);
			header('WWW-Authenticate: Basic realm="PersonasAdmin"');
			exit;
		}
	}
	catch(Exception $e)
	{
		throw new Exception("Database problem. Please try again later.");
	}

	if (array_key_exists('id', $_GET))
	{
		$persona = $db->get_persona_by_id($_GET['id']);
		$persona_id = $persona['id'];
		$second_folder = $persona_id%10;
		$first_folder = ($persona_id%100 - $second_folder)/10;	
		$persona_path = "/" . $first_folder . "/" . $second_folder . "/" . $persona_id;
		rename (PERSONAS_STORAGE_PREFIX . $persona_path, PERSONAS_PENDING_PREFIX . $persona_path);
		$db->reject_persona($persona{'id'});
		print "<div>Persona $persona_id pulled</div>";
	}
?>	
<form action="pull.php" method="POST">
Pull persona id: <input type=text name=id>
<input type="submit" name="verdict" value="pull">
</form>
</body>
</html>
