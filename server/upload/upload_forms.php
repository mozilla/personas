<?php

	require_once '../lib/personas_constants.php';
	require_once '../lib/personas_functions.php';
	require_once '../lib/storage.php';
	require_once '../lib/user.php';	
	
	
	#step 1: Authenticate
	$user = new PersonaUser();
	$auth_user = $user->authenticate();
	
	$upload_errors = array();
	$upload_submitted = array();
	#step 2: Terms of Service
	if (0)#if (!array_key_exists('terms', $_POST))
	{
		include '../lib/terms_form_tmpl.php';
		exit;
	}
	
	#do form validation on the terms. make sure to transfer the data into the next form.
	
	if (count($upload_errors) > 0)
	{
		include '../lib/terms_form_tmpl.php';
		exit;
	}
	
	#step 3: Upload Form
	$db = new PersonaStorage();
	$categories = $db->get_categories();

	if (!array_key_exists('name', $_POST))
	{
		include '../lib/upload_persona_tmpl.php';
		exit;
	}
	
	#ok, they've tried to submit the form. Let's look at the data...

	$upload_submitted['category'] = ini_get('magic_quotes_gpc') ? stripslashes($_POST['category']) : $_POST['category'];
	$upload_submitted['name'] = ini_get('magic_quotes_gpc') ? stripslashes($_POST['name']) : $_POST['name'];
	$upload_submitted['accent-color'] = ini_get('magic_quotes_gpc') ? stripslashes($_POST['accent-color']) : $_POST['accent-color'];
	$upload_submitted['text-color'] = ini_get('magic_quotes_gpc') ? stripslashes($_POST['text-color']) : $_POST['text-color'];
	$upload_submitted['description'] = ini_get('magic_quotes_gpc') ? stripslashes($_POST['description']) : $_POST['description'];

	if (!in_array($upload_submitted['category'], $categories))
		$upload_errors['category'] = "Unknown category";
	
	$upload_submitted['name'] = preg_replace('/[^A-Za-z0-9_\-\. \&]/', '', $upload_submitted['name']);
	if ($upload_submitted['name'][0] == '.')
		$upload_errors['name'] = "name cannot start with a period";
	
	if (!$upload_submitted['name'])
		$upload_errors['name'] = "Please use alphanumeric characters in your persona name";
		
	$collision_id = $db->check_persona_name($upload_submitted['name']);
	if ($collision_id)
		$upload_errors['name'] = "That name is already in use. Please select another one";

	$upload_submitted['accent-color'] = preg_replace('/[^a-f0-9]/i', '', strtolower($upload_submitted['accent-color']));
	if ($upload_submitted['accent-color'] && strlen($upload_submitted['accent-color']) != 3 && strlen($upload_submitted['accent-color']) != 6)
		$upload_errors['accent-color'] = "Unrecognized accent color";
	
	$upload_submitted['text-color'] = preg_replace('/[^a-f0-9]/i', '', strtolower($upload_submitted['text-color']));
	if ($upload_submitted['text-color'] && strlen($upload_submitted['text-color']) != 3 && strlen($upload_submitted['text-color']) != 6)
		$upload_errors['text-color'] = "Unrecognized text color";
	
	#basic non-committal image upload checks
	
	if (!array_key_exists('header-image', $_FILES))
		$upload_errors['header-image'] = "Please include a header image";
	elseif ($_FILES['header-image']['size'] > 307200)
		$upload_errors['header-image'] = "Please limit your header file size to 300K";
	elseif (strlen(preg_replace('/[^A-Za-z0-9_\-\.]/', '', $_FILES['header-image']['name'])) < 4)
		$upload_errors['header-image'] = "Please use alphanumeric characters in your header image name";

	if (!array_key_exists('footer-image', $_FILES))
		$upload_errors['footer-image'] = "Please include a footer image";
	elseif ($_FILES['footer-image']['size'] > 307200)
		$upload_errors['footer-image'] = "Please limit your footer file size to 300K";
	elseif (strlen(preg_replace('/[^A-Za-z0-9_\-\.]/', '', $_FILES['footer-image']['name'])) < 4)
		$upload_errors['footer-image'] = "Please use alphanumeric characters in your footer image name";
	
	if (count($upload_errors) > 0)
	{
		include '../lib/upload_persona_tmpl.php';
		exit;
	}

	#now the more complex image checks	

	$imgcommand = "identify -format \"%h %w %m\" "; 
	$header_specs = exec($imgcommand . $_FILES['header-image']['tmp_name']);
	$footer_specs = exec($imgcommand . $_FILES['footer-image']['tmp_name']);

	list($hheight, $hwidth, $htype) = explode(" ", $header_specs);
	list($fheight, $fwidth, $ftype) = explode(" ", $footer_specs);
	
	if (!($htype == 'JPEG' || $htype == 'PNG'))
		$upload_errors['header-image'] = "We do not recognize the format of your header image. Please let us know at persona-devel@mozilla.com if you think this is in error.";
	elseif ($hheight < 200 || $hwidth < 2500)
		$upload_errors['header-image'] = "Please make sure your header image is at least 2500x200 pixels (it appears to be $hwidth" . "x$hheight)";

	if (!($ftype == 'JPEG' || $ftype == 'PNG'))
		$upload_errors['footer-image'] = "We do not recognize the format of your footer image. Please let us know at persona-devel@mozilla.com if you think this is in error.";
	elseif ($fheight < 100 || $fwidth < 2500)
		$upload_errors['footer-image'] = "Please make sure your footer image is at least 2500x100 pixels (it appears to be $fwidth" . "x$fheight)";
		
	if (count($upload_errors) > 0)
	{
		include '../lib/upload_persona_tmpl.php';
		exit;
	}
		
	
	#step 4: Success
	
	
	
	echo $auth_user;




?>