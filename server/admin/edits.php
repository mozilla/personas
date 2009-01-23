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
		
	try 
	{
		$user = new PersonaUser();
		$user->authenticate();
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

	$db = new PersonaStorage();
	$categories = $db->get_categories();

	if (array_key_exists('verdict', $_POST) && array_key_exists('id', $_POST))
	{
		$persona = $db->get_edits_by_id($_POST['id']);
		$original = $db->get_persona_by_id($_POST['id']);

		switch ($_POST['verdict'])
		{
			case 'accept':
				$persona_id = $persona['id'];
				$second_folder = $persona_id%10;
				$first_folder = ($persona_id%100 - $second_folder)/10;	
				
				$persona_path = "/" . $first_folder;
				if (!is_dir(PERSONAS_STORAGE_PREFIX . $persona_path)) { mkdir(PERSONAS_STORAGE_PREFIX . $persona_path); }
				$persona_path .= "/" . $second_folder;
				if (!is_dir(PERSONAS_STORAGE_PREFIX . $persona_path)) { mkdir(PERSONAS_STORAGE_PREFIX . $persona_path); }
				$persona_path .= "/" . $persona_id;

				$db->approve_persona_edit($persona_id);
				if ($persona['header'])
				{
					#remove the old header and replace it
					unlink(PERSONAS_STORAGE_PREFIX . $persona_path . '/' . $original['header']);
					rename(PERSONAS_PENDING_PREFIX . $persona_path . '/' . $persona['header'], PERSONAS_STORAGE_PREFIX . $persona_path . '/' . $persona['header']);
					rename(PERSONAS_PENDING_PREFIX . $persona_path . '/preview.jpg', PERSONAS_STORAGE_PREFIX . $persona_path . '/preview.jpg');
				}
				
				if ($persona['footer'])
				{
					unlink(PERSONAS_STORAGE_PREFIX . $persona_path . '/' . $original['footer']);
					rename(PERSONAS_PENDING_PREFIX . $persona_path . '/' . $persona['footer'], PERSONAS_STORAGE_PREFIX . $persona_path . '/' . $persona['footer']);
				}
				
				break;
			case 'reject':
				$db->reject_persona_edit($persona{'id'});
				break;
			default:
				print "Could not understand the verdict";	
				exit;
		}
	}
	
	
	$result = $db->get_pending_edits();
	if (!$result[0])
	{
		print "There are no more pending edits";
	}
	else
	{
		$result = $result[0];
		$id = $result['id'];
		
		$second_folder = $id%10;
		$first_folder = ($id%100 - $second_folder)/10;
		
		$header_url = null;
		$footer_url = null;
		$preview_url = null;
		$path = PERSONAS_URL_PREFIX . '/' .  $first_folder . '/' . $second_folder . '/' . $id;
		
		if ($result['header'])
		{
			$header_url = $path . "/" . $result['header'];
			$preview_url = $path . "/preview.jpg";
		}
		
		if ($result['footer'])
		{
			$footer_url = $path . "/" . $result['header'];
		}
		
		
		
		$original_data = $db->get_persona_by_id($id);
		$old_path = PERSONAS_LIVE_PREFIX . '/' .  $first_folder . '/' . $second_folder . '/' . $id;
		$old_preview_url =  $old_path . "/preview.jpg";
		$old_header_url =  $old_path . "/" . $original_data['header'];
		$old_footer_url =  $old_path . "/" . $original_data['footer'];
?>
		<html>
		<body>
		<form action="edits.php" method="POST">
		<input type=hidden name="id" value="<?= $result['id'] ?>">
		Internal ID: <?= $result['id'] ?>
		<p>
<?php	
		if ($original_data['name'] != $result['name'])
		{
			print "<div style=\"outline: red solid thin\">Name: " . $result['name'] . " (original: " . $original_data['name'] . ")</div>";
		}
		else 
		{ 
			print "Name: " . $result['name']; 
		}
		print "<p>";
		
		print "User: " . $original_data['author'] . " (edit submitted by: " . $result['author'] . ")";
		print "<p>";

		if ($original_data['category'] != $result['category'])
		{
			print "<div style=\"outline: red solid thin\">Category: " . $result['category'] . " (original: " . $original_data['category'] . ")</div>";
		}
		else 
		{ 
			print "Category: " . $result['category']; 
		}
		print "<p>";

		if ($original_data['textcolor'] != $result['textcolor'])
		{
			print "<div style=\"outline: red solid thin\">Text Color: " . $result['textcolor'] . " (original: " . $original_data['textcolor'] . ")</div>";
		}
		else 
		{ 
			print "Text Color: " . $result['textcolor']; 
		}
		print "<p>";

		if ($original_data['accentcolor'] != $result['accentcolor'])
		{
			print "<div style=\"outline: red solid thin\">Accent color: " . $result['accentcolor'] . " (original: " . $original_data['accentcolor'] . ")</div>";
		}
		else 
		{ 
			print "Accent Color: " . $result['accentcolor']; 
		}
		print "<p>";

		if ($result['header'])
		{
			print "<div style=\"outline: red solid thin\">Preview: <img src=\"$preview_url\"> Original: <img src=\"$old_preview_url\">";
			print "<P>Header: <br><img src=\"$header_url\"> <br>Original: <br><img src=\"$old_header_url\"></div>";
		}
		if ($result['footer'])
		{
			print "<div style=\"outline: red solid thin\">Footer: <br><img src=\"$footer_url\"> <br>Original: <br><img src=\"$old_footer_url\"></div>";
		}
?>
		<p>
		<input type="submit" name="verdict" value="accept">
		<input type="submit" name="verdict" value="reject">
		</form>
		</body>
		</html>
<?php
	}
?>
