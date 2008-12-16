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
	$error = "";
	$auth_user = null;
	
	try
	{
		$db = new PersonaStorage();
		
		if (array_key_exists('user', $_POST))
		{
			#trying to log in
			$auth_user = ini_get('magic_quotes_gpc') ? stripslashes($_POST['user']) : $_POST['user'];
			$auth_pass = ini_get('magic_quotes_gpc') ? stripslashes($_POST['pass']) : $_POST['pass'];
			if (!$db->authenticate_user($auth_user, $auth_pass))
			{
				#print auth page with bad login warning
				$error = "We were unable to locate your account. Please try again or register.";
				include '../lib/auth_form.php';
				exit;
			}
			setcookie('PERSONA_USER', $auth_user . " " . md5($auth_user . $db->get_password_md5($auth_user) . PERSONAS_LOGIN_SALT . $_SERVER['REMOTE_ADDR']), time() + 60*60*24*365);
		}
		else if (!array_key_exists('PERSONA_USER', $_COOKIE))
		{
			#print auth page
			include '../lib/auth_form.php';
			exit;
		}

		if (!$auth_user)
		{
			#authenticate the user off their cookie
			$auth_cookie = ini_get('magic_quotes_gpc') ? stripslashes($_COOKIE['PERSONA_USER']) : $_COOKIE['PERSONA_USER'];
			list($auth_user, $token) = explode(' ', $auth_cookie);
		
			if (md5($auth_user . $db->get_password_md5($auth_user) . PERSONAS_LOGIN_SALT . $_SERVER['REMOTE_ADDR']) != $token)
			{
				#print auth page
				include '../lib/auth_form.php';
				exit;
			}
		}
		
		$categories = $db->get_categories();

		if (array_key_exists('name', $_POST))
		{
			#upload a persona

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
			if (!$name || $db->check_persona_name($name))
			{
				throw new Exception("This name is already in use");
			}
	

			#some sort of uploaded file
			$h_name = $_FILES['header']['name'];
			$f_name = $_FILES['footer']['name'];

			#sanitized for your protection
			$h_name = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $h_name);
			$f_name = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $f_name);
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

			
			$second_folder = $persona_id%10;
			$first_folder = ($persona_id%100 - $second_folder)/10;

			$persona_path = PERSONAS_PENDING_PREFIX . "/" . $first_folder;
			if (!is_dir($persona_path)) { mkdir($persona_path); }
			$persona_path .= "/" . $second_folder;
			if (!is_dir($persona_path)) { mkdir($persona_path); }
			$persona_path .= "/" . $persona_id;
			if (!is_dir($persona_path)) { mkdir($persona_path); }
			
				
			if (move_uploaded_file($_FILES['header']['tmp_name'], $persona_path . "/" . $h_name)
			  && move_uploaded_file($_FILES['footer']['tmp_name'], $persona_path . "/" . $f_name))
			{
				$error = "Files uploaded successfully";
			}
			else
			{
				throw new exception("<div class=\"message\">An error occured. Please try again later</div>");
				#need to remove the db record, too.
			}
		
			#add a json descriptor

			file_put_contents($persona_path . '/index_1.json', json_encode(array('id' => $persona_id, 
						'name' => $name,
						'accentcolor' => $accentcolor ? $accentcolor : null,
						'textcolor' => textcolor ? $textcolor : null,
						'header' => $persona_path . '/' . $h_name, 
						'footer' => url_prefix($id) . '/' . $f_name)));
						
						
						
			$imgcommand = "convert \( " . $persona_path . "/" . $h_name . " -gravity NorthEast -crop 600x200+0+0 \) \( " . $persona_path . "/" . $f_name . " -gravity NorthEast -crop 600x100+0+0 \)  -append -scale 200x100 " . $persona_path . "/preview.jpg";
			exec($imgcommand);
		}
	}
	catch (Exception $e)
	{
		$error = "An error occured: " . $e->getMessage();
	}
	
?>
<!DOCTYPE HTML>
<html>
  <head>
    <title>Personas for Firefox</title>
	<script type="text/javascript" src="include/jquery.js"></script>
	<script type="text/javascript" src="include/farbtastic.js"></script>
	<link rel="stylesheet" href="include/farbtastic.css" type="text/css" />
</head>

  <body>
<script type="text/javascript">
$(document).ready(
	function()
	{
    	$('#colorpicker').farbtastic('#textcolor');
    	$('#colorpicker2').farbtastic('#accentcolor');
  	
  		$('#cp1').toggle(function() 
  		{
    		$('#colorpicker').slideDown("slow");
    		return false;
  		},
  		function() 
  		{
    		$('#colorpicker').slideUp("slow");
    		return false;
  		});

  		$('#cp2').toggle(function() 
  		{
    		$('#colorpicker2').slideDown("slow");
    		return false;
  		},
  		function() 
  		{
    		$('#colorpicker2').slideUp("slow");
    		return false;
  		});
  	}
);

</script>


<h1>Designing Personas - Persona Submission</h1>
<?php if ($error) { echo "<div class=\"error\">$error</div>"; } ?>
Welcome <?php echo $auth_user ?>
<p>

<form method=POST enctype='multipart/form-data' action="submit.php">

Persona Title: <input type=text name="name">
<p>
Category: <select name="category">
<?php 
	foreach ($categories as $category)
	{
		print "<option>$category</option>";
	}
?>
</select>
<p>
Text Color (optional): <input type="text" id="textcolor" name="textcolor" value=" ">
<input type="submit" id="cp1" value="toggle text color picker" />
<div id="colorpicker" align="left" style="display:none;"></div>
<p>
Accent Color (optional): <input type="text" id="accentcolor" name="accentcolor" value=" ">
<input type="submit" id="cp2" value="toggle accent color picker" />
<div id="colorpicker2" align="left" style="display:none"></div>
<p>
Header: <input type=file name=header>
<p>
Footer: <input type=file name=footer>
<p>
<input type=submit>

</form>
<p>By uploading your Persona to this site, you agree that
 the following are true:</p>
 <ul>
   <li>you have the right to distribute this Persona,
   including any rights required for material that may be
   trademarked or copyrighted by someone else; and</li>
   <li>if any information about the user or usage of this
   Persona is collected or transmitted outside of the user's
   computer, the details of this collection will be provided
   in the description of the software, and you will provide a
   link to a privacy policy detailing how the information is
   managed and protected; and</li>
   <li>your Persona may be removed from the site,
   re-categorized, have its description or other information
   changed, or otherwise have its listing changed or removed,
   at the sole discretion of Mozilla and its authorized
   agents; and</li>
   <li>the descriptions and other data you provide about the
   Persona are true to the best of your knowledge.</li>
 </ul>
  </body>

</html>
