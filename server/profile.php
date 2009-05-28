<?php
	require_once 'lib/personas_constants.php';
	require_once 'lib/storage.php';
	require_once 'lib/user.php';
	
	$db = new PersonaStorage();

	$user = new PersonaUser();
	$user->authenticate();
	$user->force_signin(1);
	
	
	$_errors = array();
	$return_url = null;
	
	$updated = 0;
	$create = array();
	
	if (array_key_exists('update', $_POST))
	{
		#trying to create an account
		$create['email'] = array_key_exists('create_email', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['create_email']) : $_POST['create_email']) : null;
		$create['display_username'] = array_key_exists('create_display_username', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['create_display_username']) : $_POST['create_display_username']) : null;
		$create['description'] = array_key_exists('create_description', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['create_description']) : $_POST['create_description']) : null;
		$create['news'] = array_key_exists('news', $_POST);
		$create['display_username'] = trim($create['display_username']);

		$create['display_username'] = htmlspecialchars($create['display_username']);
		$create['description'] = htmlspecialchars($create['description']);

		if (!preg_match('/^[A-Z0-9\._%+-]+@[A-Z0-9\.-]+\.[A-Z]{2,4}$/i', $create['email'])) 
			$_errors['create_email'] = "Invalid email address";
		
		if (strlen($create['display_username']) > 32)
			$_errors['create_display_username'] = "Please limit your display name to 32 characters or less";

		if (strlen($create['description']) > 256)
			$_errors['create_description'] = "Please limit your description to 256 characters or less";

		if (count($_errors) == 0 && $user->update_user($user->get_username(), $create['display_username'], $create['email'], $create['description'], $create['news']))
		{
			$db->update_display_username($user->get_username(), $create['display_username']);
			$updated = 1;
		}
	}
	else
	{
		$create['email'] = $user->_email;
		$create['display_username'] = $user->_display_username;
		$create['description'] = $user->_description;
		$create['news'] = $user->_news;
	}
	
	$title = "Change User Details"; 
	include 'templates/header.php'; 
?>
<body class="forgot-password">
    <div id="outer-wrapper">
        <div id="inner-wrapper">
<?php include 'templates/nav.php'; ?>
            <div id="header">
                <h2>Change User Details</h2>
            </div>
            <div id="maincontent">
                <div id="breadcrumbs">
                    Personas Home : User Details  
                </div>
<?php 
		if ($updated)
		{
?>
                <div id="signup">
                    <h4>Change User Details</h3>
                	You have successfully updated your user profile. Thanks for keeping us up to date!
                </div>
<?php
		}
		else
			include 'templates/change_user_details.php'; 
?>
            </div>
        </div>
    </div>
<?php include "templates/footer.php" ?>
</body>
</html>

