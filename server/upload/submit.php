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
	$error = "";

	function get_basic_form_data($id = null)
	{
		global $categories;
		global $db;
		if (array_key_exists('category', $_POST))
		{
			$category = ini_get('magic_quotes_gpc') ? stripslashes($_POST['category']) : $_POST['category'];
			if (!in_array($category, $categories))
			{
				throw new Exception("Unknown Category");
			}
		}
		else
		{
			throw new Exception("Missing Category");
		}

		$accentcolor = ini_get('magic_quotes_gpc') ? stripslashes($_POST['accentcolor']) : $_POST['accentcolor'];
		$accentcolor = preg_replace('/[^a-f0-9]/i', '', strtolower($accentcolor));
		if ($accentcolor && strlen($accentcolor) != 3 && strlen($accentcolor) != 6)
		{
			throw new Exception("Unrecognized Accent Color");
		}
		
		$textcolor = ini_get('magic_quotes_gpc') ? stripslashes($_POST['textcolor']) : $_POST['textcolor'];
		$textcolor = preg_replace('/[^a-f0-9]/i', '', strtolower($textcolor));
		if ($textcolor && strlen($textcolor) != 3 && strlen($textcolor) != 6)
		{
			throw new Exception("Unrecognized Text Color");
		}
		

		#check to see if the name is already in use
		$name = ini_get('magic_quotes_gpc') ? stripslashes($_POST['name']) : $_POST['name'];
		$name = preg_replace('/[^A-Za-z0-9_\-\. ]/', '', $name);
		
		if ($name{0} == '.')
		{
			throw new Exception("Filename cannot start with a period.");
		}
		if (!$name)
		{
			throw new Exception("Please provide a persona name");
		}
		$collision_id = $db->check_persona_name($name);
		if ($collision_id && $collision_id != $id)
		{
			throw new Exception("This name is already in use");
		}
		return array($name, $textcolor, $accentcolor, $category);
	}

	function make_persona_path($persona_id)
	{
		$second_folder = $persona_id%10;
		$first_folder = ($persona_id%100 - $second_folder)/10;

		$persona_path = PERSONAS_PENDING_PREFIX . "/" . $first_folder;
		if (!is_dir($persona_path)) { mkdir($persona_path); }
		$persona_path .= "/" . $second_folder;
		if (!is_dir($persona_path)) { mkdir($persona_path); }
		$persona_path .= "/" . $persona_id;
		if (!is_dir($persona_path)) { mkdir($persona_path); }
		return $persona_path;
	}
	
	
	
	
	
	try
	{
		$db = new PersonaStorage();
		$user = new PersonaUser();
		$auth_user = $user->authenticate();
		
		$categories = $db->get_categories();

		if (array_key_exists('id', $_POST) && ($_POST['id']))
		{
			#edit a persona
			$id = preg_replace('/[^0-9]/', '', $_POST['id']);
			list($name, $textcolor, $accentcolor, $category) = get_basic_form_data($id);

			if (array_key_exists('header', $_FILES) && $_FILES['header']['name'])
			{
				$h_name = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $_FILES['header']['name']);
				if ($_FILES['header']['size'] > 307200) { throw new Exception("Header file too large"); }
				$persona_path = make_persona_path($id);
				if (!move_uploaded_file($_FILES['header']['tmp_name'], $persona_path . "/" . $h_name))
				{
					throw new Exception("An error occured uploading the header. Please try again later");
				}
				$imgcommand = "convert " . $persona_path . "/" . $h_name . " -gravity NorthEast -crop 600x200+0+0  -scale 200x100 " . $persona_path . "/preview.jpg";
				exec($imgcommand);
			}
			else
			{
				$h_name = null;
			}

			if (array_key_exists('footer', $_FILES) && $_FILES['footer']['name'])
			{
				$f_name = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $_FILES['footer']['name']);
				if ($_FILES['footer']['size'] > 307200) { throw new Exception("Footer file too large"); }
				$persona_path = make_persona_path($id);
				if (!move_uploaded_file($_FILES['footer']['tmp_name'], $persona_path . "/" . $f_name))
				{
					throw new Exception("An error occured uploading the footer. Please try again later");
				}
			}
			else
			{
				$f_name = null;
			}
			
			$db->log_action($auth_user, $id, "Edited");
			$db->submit_persona_edit($id, $auth_user, $name, $category, $accentcolor, $textcolor, $h_name, $f_name);
			$error = "Edits successfully submitted";
			
		}
		else if (array_key_exists('name', $_POST))
		{
			#upload a persona
			list($name, $textcolor, $accentcolor, $category) = get_basic_form_data();

			#some sort of uploaded file
			#sanitized for your protection
			$h_name = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $_FILES['header']['name']);
			$f_name = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $_FILES['footer']['name']);
			if ($h_name == $f_name)
			{
				throw new Exception("The two files need different names");
			}

			#limiting files to 300K
			if ($_FILES['header']['size'] > 307200)
			{
				throw new Exception("Header file too large");
			}
			if ($_FILES['footer']['size'] > 307200)
			{
				throw new Exception("Footer file too large");
			}
			
			$persona_id = $db->submit_persona($name, $category, $h_name, $f_name, $auth_user, $accentcolor, $textcolor);

			$persona_path = make_persona_path($persona_id);
			
				
			if (move_uploaded_file($_FILES['header']['tmp_name'], $persona_path . "/" . $h_name)
			  && move_uploaded_file($_FILES['footer']['tmp_name'], $persona_path . "/" . $f_name))
			{
				$error = "Files uploaded successfully. Persona added to the approval queue.";
			}
			else
			{
				throw new Exception("An error occured. Please try again later");
				#need to remove the db record, too.
			}
			
			$db->log_action($auth_name, $persona_id, "Added");
		
			#add a json descriptor

			$second_folder = $persona_id%10;
			$first_folder = ($persona_id%100 - $second_folder)/10;
			file_put_contents($persona_path . '/index_1.json', json_encode(array('id' => $persona_id, 
						'name' => $name,
						'accentcolor' => $accentcolor ? $accentcolor : null,
						'textcolor' => $textcolor ? $textcolor : null,
						'header' => $first_folder . '/' . $second_folder .  '/'. $persona_id . '/' . $h_name, 
						'footer' => $first_folder . '/' . $second_folder .  '/'. $persona_id . '/' . $f_name)));
						
						
						
			$imgcommand = "convert " . $persona_path . "/" . $h_name . " -gravity NorthEast -crop 600x200+0+0  -scale 200x100 " . $persona_path . "/preview.jpg";
			#error_log($imgcommand);
			exec($imgcommand);
		}
	}
	catch (Exception $e)
	{
		$error = "An error occured: " . $e->getMessage();
	}
	
	if (array_key_exists('edit_id', $_GET) && ($_GET['edit_id']))
	{
		$form_title = 'Edit a Persona';
		$form_id = preg_replace('/[^0-9]/', '', $_GET['edit_id']);
		$persona = $db->get_persona_by_id($form_id);
		if ($persona['author'] == $auth_user || $user->has_admin_privs())
		{
			$form_name = $persona['name'];
			$form_category = $persona['category'];
			$form_accent = $persona['accentcolor'] ? $persona['accentcolor'] : ' ';
			$form_text = $persona['textcolor'] ? $persona['textcolor'] : ' ';
		}
		else
		{
			$error = "You do not have permission to edit that persona";
			$form_name = '';
			$form_category = '';
			$form_accent = ' ';
			$form_text = ' ';
		}
	}
	else
	{
		$form_title = 'Persona Submission';
		$form_id = '';
		$form_name = '';
		$form_category = '';
		$form_accent = ' ';
		$form_text = ' ';
	}
	
	include '../lib/upload_form.php';
?>

