<?php

	require_once 'lib/personas_constants.php';
	require_once 'lib/personas_functions.php';
	require_once 'lib/storage.php';
	require_once 'lib/user.php';	
	
	
	#step 1: Authenticate
	$user = new PersonaUser();
	$auth_user = $user->authenticate();

	$db = new PersonaStorage();
	
	$upload_errors = array();
	$upload_submitted = array();

	#is this an edit?
	
	$id == null;
	
	if (array_key_exists('id', $_GET))
		$id = $_GET['id'];
		
	if (array_key_exists('id', $_POST))
		$id = $_POST['id'];
		
	if ($id)	
	{
		if (!$user->has_admin_privs() && $upload_submitted['author'] != $auth_user)
		{
			#include something bad here
			echo "You don't have permission to edit that";
			exit;
		}
		$upload_submitted = $db->get_persona_by_id($id);
		$upload_submitted['agree'] = 1;
	}
	else
	{
		#step 2: Terms of Service
		if (!array_key_exists('agree', $_POST) && !array_key_exists('firstterms', $_POST))
		{
			include 'lib/upload_tos_tmpl.php';
			exit;
		}
		
		#do form validation on the terms. make sure to transfer the data into the next form.
		
		$upload_submitted['agree'] = ini_get('magic_quotes_gpc') ? stripslashes($_POST['agree']) : $_POST['agree'];
		$upload_submitted['license'] = ini_get('magic_quotes_gpc') ? stripslashes($_POST['license']) : $_POST['license'];

		if ($upload_submitted['agree'] != 1)
			$upload_errors['agree'] = "Please make sure to agree to the licensing agreement";
		
		if ($upload_submitted['license'] != 'cc' && $upload_submitted['license'] != 'restricted')
			$upload_errors['license'] = "Please make sure to choose the appropriate license";

		if (count($upload_errors) > 0)
		{
			include 'lib/upload_tos_tmpl.php';
			exit;
		}
	}
	
	#step 3: Upload Form
	$categories = $db->get_categories();

	if (!array_key_exists('name', $_POST))
	{
		include 'lib/upload_persona_tmpl.php';
		exit;
	}
	
	#ok, they've tried to submit the form. Let's look at the data...

	$upload_submitted['category'] = ini_get('magic_quotes_gpc') ? stripslashes($_POST['category']) : $_POST['category'];
	$upload_submitted['name'] = trim(ini_get('magic_quotes_gpc') ? stripslashes($_POST['name']) : $_POST['name']);
	$upload_submitted['accentcolor'] = ini_get('magic_quotes_gpc') ? stripslashes($_POST['accentcolor']) : $_POST['accentcolor'];
	$upload_submitted['textcolor'] = ini_get('magic_quotes_gpc') ? stripslashes($_POST['textcolor']) : $_POST['textcolor'];
	$upload_submitted['description'] = ini_get('magic_quotes_gpc') ? stripslashes($_POST['description']) : $_POST['description'];
	$upload_submitted['reason'] = ini_get('magic_quotes_gpc') ? stripslashes($_POST['reason']) : $_POST['reason'];
	$upload_submitted['other-reason'] = ini_get('magic_quotes_gpc') ? stripslashes($_POST['other-reason']) : $_POST['other-reason'];

	if (!in_array($upload_submitted['category'], $categories))
		$upload_errors['category'] = "Unknown category";
	
	$upload_submitted['name'] = preg_replace('/[^A-Za-z0-9_\-\. \&]/', '', $upload_submitted['name']);
	if ($upload_submitted['name'][0] == '.')
		$upload_errors['name'] = "name cannot start with a period";
	
	if (!$upload_submitted['name'])
		$upload_errors['name'] = "Please use alphanumeric characters in your persona name";
		
	$collision_id = $db->check_persona_name($upload_submitted['name']);
	if ($collision_id != $id)
		$upload_errors['name'] = "That name is already in use. Please select another one";

	$upload_submitted['accentcolor'] = preg_replace('/[^a-f0-9]/i', '', strtolower($upload_submitted['accentcolor']));
	if ($upload_submitted['accentcolor'] && strlen($upload_submitted['accentcolor']) != 3 && strlen($upload_submitted['accentcolor']) != 6)
		$upload_errors['accentcolor'] = "Unrecognized accent color";
	
	$upload_submitted['textcolor'] = preg_replace('/[^a-f0-9]/i', '', strtolower($upload_submitted['textcolor']));
	if ($upload_submitted['textcolor'] && strlen($upload_submitted['textcolor']) != 3 && strlen($upload_submitted['textcolor']) != 6)
		$upload_errors['textcolor'] = "Unrecognized text color";
	
	if ($upload_submitted['license'] == 'restricted' && !$upload_submitted['reason'])
		$upload_errors['reason'] = "Please provide a reason for creating this persona";
	
	#basic non-committal image upload checks

	if (!(array_key_exists('id', $upload_submitted) && $_FILES['header-image']['size'] == 0)) #images are optional on edit
	{
		if (!array_key_exists('header-image', $_FILES))
			$upload_errors['header-image'] = "Please include a header image";
		elseif ($_FILES['header-image']['size'] > 307200)
			$upload_errors['header-image'] = "Please limit your header file size to 300K";
		elseif (strlen(preg_replace('/[^A-Za-z0-9_\-\.]/', '', $_FILES['header-image']['name'])) < 4)
			$upload_errors['header-image'] = "Please use alphanumeric characters in your header image name";
		$upload_submitted['header'] = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $_FILES['header-image']['name']);
	}
	
	if (!(array_key_exists('id', $upload_submitted) && $_FILES['footer-image']['size'] == 0)) #images are optional on edit
	{
		if (!array_key_exists('footer-image', $_FILES))
			$upload_errors['footer-image'] = "Please include a footer image";
		elseif ($_FILES['footer-image']['size'] > 307200)
			$upload_errors['footer-image'] = "Please limit your footer file size to 300K";
		elseif (strlen(preg_replace('/[^A-Za-z0-9_\-\.]/', '', $_FILES['footer-image']['name'])) < 4)
			$upload_errors['footer-image'] = "Please use alphanumeric characters in your footer image name";
		$upload_submitted['footer'] = preg_replace('/[^A-Za-z0-9_\-\.]/', '', $_FILES['footer-image']['name']);
	}
	
	if ($upload_submitted['header'] == $upload_submitted['footer'])
		$upload_errors['footer-image'] = "Please use different names for the header and the footer";
	
	if (count($upload_errors) > 0)
	{
		include 'lib/upload_persona_tmpl.php';
		exit;
	}
	
	#now the more complex image checks		
	
	
	if (!(array_key_exists('id', $upload_submitted) && $_FILES['header-image']['size'] == 0)) #images are optional on edit
	{
		$imgcommand = "identify -format \"%h %w %m\" "; 

		$header_specs = exec($imgcommand . $_FILES['header-image']['tmp_name']);	
		list($hheight, $hwidth, $htype) = explode(" ", $header_specs);		
		if (!($htype == 'JPEG' || $htype == 'PNG'))
			$upload_errors['header-image'] = "We do not recognize the format of your header image. Please let us know at persona-devel@mozilla.com if you think this is in error.";
		elseif ($hheight < 200 || $hwidth < 2500)
			$upload_errors['header-image'] = "Please make sure your header image is at least 2500x200 pixels (it appears to be $hwidth" . "x$hheight)";
	}
	
	if (!(array_key_exists('id', $upload_submitted) && $_FILES['footer-image']['size'] == 0)) #images are optional on edit
	{
		$footer_specs = exec($imgcommand . $_FILES['footer-image']['tmp_name']);
		list($fheight, $fwidth, $ftype) = explode(" ", $footer_specs);
		if (!($ftype == 'JPEG' || $ftype == 'PNG'))
			$upload_errors['footer-image'] = "We do not recognize the format of your footer image. Please let us know at persona-devel@mozilla.com if you think this is in error.";
		elseif ($fheight < 100 || $fwidth < 2500)
			$upload_errors['footer-image'] = "Please make sure your footer image is at least 2500x100 pixels (it appears to be $fwidth" . "x$fheight)";
	}	
			
	if (count($upload_errors) > 0)
	{
		include 'lib/upload_persona_tmpl.php';
		exit;
	}
	
	#step 4: Success

	if (array_key_exists('id', $upload_submitted))
	{
		$db->submit_persona_edit($upload_submitted['id'], $auth_user, $upload_submitted['name'], $upload_submitted['category'], $upload_submitted['accentcolor'], $upload_submitted['textcolor'], $upload_submitted['header'], $upload_submitted['footer'], $upload_submitted['description'], $upload_submitted['reason'], $upload_submitted['reason-other']);
		$db->log_action($auth_user, $id, "Edited");
	}
	else
	{
		$upload_submitted['id'] = $db->submit_persona($upload_submitted['name'], $upload_submitted['category'], $upload_submitted['header'], $upload_submitted['footer'], $auth_user, $upload_submitted['accentcolor'], $upload_submitted['textcolor'], $upload_submitted['description'], $upload_submitted['license'], $upload_submitted['reason'], $upload_submitted['reason-other']);
		$db->log_action($auth_user, $upload_submitted['id'], "Added");
	}
	$persona_path = make_persona_pending_path($upload_submitted['id']);
	
	if ($_FILES['footer-image']['size'] > 0 && !move_uploaded_file($_FILES['footer-image']['tmp_name'], $persona_path . "/" . $upload_submitted['footer']))
	{
		$upload_errors['footer-image'] = "A problem occured uploading your persona. Please contact persona-devel@mozilla.com to let us know about this issue. Thank you.";
		if (!array_key_exists('id', $_POST))
			$db->reject_persona($upload_submitted['id']);
		include 'lib/upload_persona_tmpl.php';
		exit;					
	}

	if ($_FILES['header-image']['size'] > 0)
	{
		if (!move_uploaded_file($_FILES['header-image']['tmp_name'], $persona_path . "/" . $upload_submitted['header']))
		{
			$upload_errors['header-image'] = "A problem occured uploading your persona. Please contact persona-devel@mozilla.com to let us know about this issue. Thank you.";
			if (!array_key_exists('id', $_POST))
				$db->reject_persona($upload_submitted['id']);
			include 'lib/upload_persona_tmpl.php';
			exit;					
		}

		$imgcommand = "convert " . $persona_path . "/" . $upload_submitted['header'] . " -gravity NorthEast -crop 600x200+0+0  -scale 200x100 " . $persona_path . "/preview.jpg";
		exec($imgcommand);
		$imgcommand2 = "convert " . $persona_path . "/" . $upload_submitted['header'] . " -gravity NorthEast -crop 1360x200+0+0 -scale 680x100" . $persona_path . "/preview_large.jpg";
		exec($imgcommand2);
		$imgcommand3 = "convert " . $persona_path . "/" . $upload_submitted['header'] . " -gravity NorthEast -crop 320x220+0+0  -scale 64x44 " . $persona_path . "/preview_popular.jpg";
		exec($imgcommand3);
	}
	
	
		
	file_put_contents($persona_path . '/index_1.json', json_encode(extract_record_data($upload_submitted)));
	
	include 'lib/upload_success_tmpl.php';


?>