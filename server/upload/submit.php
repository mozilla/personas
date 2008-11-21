<?php
	require_once 'storage.inc';
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
				include 'auth_form.inc';
				exit;
			}
			setcookie('PERSONA_USER', $auth_user . " " . md5($auth_user . $db->get_password_md5($auth_user) . getenv('PERSONAS_LOGIN_SALT') . $_SERVER['REMOTE_ADDR']));
		}
		else if (!array_key_exists('PERSONA_USER', $_COOKIE))
		{
			#print auth page
			include 'auth_form.inc';
			exit;
		}

		if (!$auth_user)
		{
			#authenticate the user off their cookie
			$auth_cookie = ini_get('magic_quotes_gpc') ? stripslashes($_COOKIE['PERSONA_USER']) : $_COOKIE['PERSONA_USER'];
			list($auth_user, $token) = explode(' ', $auth_cookie);
		
			if (md5($auth_user . $db->get_password_md5($auth_user) . getenv('PERSONAS_LOGIN_SALT') . $_SERVER['REMOTE_ADDR']) != $token)
			{
				#print auth page
				include 'auth_form.inc';
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

			$persona_id = $db->submit_persona($name, $category, $h_name, $f_name, $auth_user);

			
			$second_folder = $persona_id%10;
			$first_folder = ($persona_id%100 - $second_folder)/10;

			$persona_path = getenv('PERSONAS_STORAGE_PREFIX') . "/" . $first_folder;
			if (!is_dir($persona_path)) { mkdir($persona_path); }
			$persona_path .= "/" . $second_folder;
			if (!is_dir($persona_path)) { mkdir($persona_path); }
			$persona_path .= "/" . $persona_id;
			if (!is_dir($persona_path)) { mkdir($persona_path); }
			
				
			if (move_uploaded_file($_FILES['header']['tmp_name'], $persona_path . "/" . $h_name)
			  && move_uploaded_file($_FILES['footer']['tmp_name'], $persona_path . "/" . $f_name))
			{
				$error = "<div class=\"message\">Files uploaded successfully</div>";
			}
			else
			{
				throw new exception("<div class=\"message\">An error occured. Please try again later</div>");
				#need to remove the db record, too.
			}
		
			$imgcommand = "convert \( " . $persona_path . "/" . $h_name . " -gravity NorthEast -crop 600x200+0+0 \) \( " . $persona_path . "/" . $f_name . " -gravity NorthEast -crop 600x100+0+0 \)  -append -scale 200x100 " . $persona_path . "/preview.jpg";
			error_log($imgcommand);
			exec($imgcommand);
		}
	}
	catch (Exception $e)
	{
		$error = "<div class=\"message\">An error occured: " . $e->getMessage() . "</div>";
	}
	
?>
<!DOCTYPE HTML>
<html>
  <head>
    <title>Personas for Firefox</title>
    <style type="text/css" media="screen">
      @import "personas.css";
    </style>
  </head>

  <body>

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
Header: <input type=file name=header>
<p>
Footer: <input type=file name=footer>
<p>
<input type=submit>

</form>
  </body>

</html>
