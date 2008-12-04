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
	
	require_once 'personas_libs/storage.php';

	$error = '';
	
	if (array_key_exists('user', $_POST))
	{
		$username = array_key_exists('user', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['user']) : $_POST['user']) : null;
		$password = array_key_exists('pass', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['pass']) : $_POST['pass']) : null;
		$passwordconf = array_key_exists('passconf', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['passconf']) : $_POST['passconf']) : null;
		$email = array_key_exists('email', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['email']) : $_POST['email']) : null;
		
		
		try
		{
			if (!preg_match('/^[A-Z0-9._-]+/i', $username)) 
			{
				throw new Exception("Illegal characters in username");
			}

			if ($password != $passwordconf)
			{
				throw new Exception("Password does not match confirmation");
			}
			
			$db = new PersonaStorage();
			if ($db->user_exists($username))
			{
				throw new Exception("User already exists");
			}
			
			$db->create_user($username, $password, $email);
			setcookie('PERSONA_USER', $username . " " . md5($username . $db->get_password_md5($username) . getenv('PERSONAS_LOGIN_SALT') . $_SERVER['REMOTE_ADDR']));
			print "<div class=\"message\">Username successfully created. You may start <a href=\"submit.php\">uploading a persona</a></div>";
			exit;

		}
		catch (Exception $e)
		{
			$error =  $e->getMessage();
		}
	}
?>
Create a personas account:

<?php if ($error) { echo "<div class=\"error\">$error</div>"; } ?>
<form method=POST enctype='multipart/form-data' action="user.php">
Username: <input type=text name="user">
<p>
Password: <input type=password name="pass">
<p>
Password Confirm: <input type=password name="passconf">
<p>
Email: <input type=text name="email">
<p>
<input type=submit>

</form>
