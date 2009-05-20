<?php
	require_once 'lib/personas_constants.php';
	require_once 'lib/user.php';	

	$user = new PersonaUser();
	$_errors = array();
	$return_url = null;
	
	if (array_key_exists('return', $_GET))
		$return_url = $_GET['return'];
	elseif (array_key_exists('return', $_POST))
		$return_url = $_POST['return'];

	if (!preg_match('/^\//', $return_url))
		$return_url = null;


	if (array_key_exists('action', $_GET) && $_GET['action'] == 'signout')
	{
		$user->log_out();
	
		if ($return_url)
		{
			header('Location: ' . $return_url . '?signout_success=1');
			exit;
		}
	}


	if (array_key_exists('login_user', $_POST) && $_POST['login_user'])
	{
		#trying to log in with password
		$auth_user = ini_get('magic_quotes_gpc') ? stripslashes($_POST['login_user']) : $_POST['login_user'];
		$auth_pass = array_key_exists('login_pass', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['login_pass']) : $_POST['login_pass']) : null;
		$auth_remember = array_key_exists('login_remember', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['login_remember']) : $_POST['login_remember']) : null;
		if ($user->authenticate_user_from_password($auth_user, $auth_pass))
		{
			setcookie('PERSONA_USER', $user->get_cookie(), $auth_remember ? time() + 60*60*24*365 : null, '/');
			if ($return_url)
				header('Location: ' . $return_url);
			else
				header('Location: /');
			exit;
		}
		else
		{
			$_errors['login_user'] = "Invalid username or password. Please try again";
		}
	}
	
	if (array_key_exists('create_username', $_POST) && $_POST['create_username'])
	{
		#trying to create an account
		$create['username'] = array_key_exists('create_username', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['create_username']) : $_POST['create_username']) : null;
		$password = array_key_exists('create_password', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['create_password']) : $_POST['create_password']) : null;
		$passwordconf = array_key_exists('create_passconf', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['create_passconf']) : $_POST['create_passconf']) : null;
		$create['email'] = array_key_exists('create_email', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['create_email']) : $_POST['create_email']) : null;
		$create['display_username'] = array_key_exists('create_display_username', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['create_display_username']) : $_POST['create_display_username']) : null;
		$create['description'] = array_key_exists('create_description', $_POST) ? (ini_get('magic_quotes_gpc') ? stripslashes($_POST['create_description']) : $_POST['create_description']) : null;
		$create['news'] = array_key_exists('news', $_POST);
		$create['username'] = trim($create['username']);
		$create['display_username'] = trim($create['display_username']);

		$create['display_username'] = htmlspecialchars($create['display_username']);
		$create['description'] = htmlspecialchars($create['description']);
		
		$captcha_response = recaptcha_check_answer(
			RECAPTCHA_PRIVATE_KEY,
			$_SERVER['REMOTE_ADDR'],
			$_POST['recaptcha_challenge_field'],
			$_POST['recaptcha_response_field']
		);
		
		if (!$captcha_response->is_valid) 
			$_errors['captcha'] = "Invalid captcha response. Please try again.";

		if (!preg_match('/^[A-Z0-9\._%+-]+@[A-Z0-9\.-]+\.[A-Z]{2,4}$/i', $create['email'])) 
			$_errors['create_email'] = "Invalid email address";

		if (!preg_match('/^[A-Z0-9\._-]+$/i', $create['username'])) 
			$_errors['create_username'] = "Illegal characters in the username (alphanumerics, period, underscore and dash only)";
		elseif (strlen($create['username']) > 32)
			$_errors['create_username'] = "Please limit your username to 32 characters or less";
		elseif (strlen($create['username']) < 6)
			$_errors['create_username'] = "Please use at least 6 characters in your username";
			
		if (strlen($password) < 6)
			$_errors['create_password'] = "Password must be at least 6 characters long";
		elseif (!preg_match('/[A-Z]/i', $password) || !preg_match('/[^A-Z]/i', $password) )
			$_errors['create_password'] = "Password should contain at least one alphabetic character and at least one non-alphabetic character";
		
		if ($password != $passwordconf)
			$_errors['create_passconf'] = "Password does not match confirmation";
		
		if (strlen($create['display_username']) > 32)
			$_errors['create_display_username'] = "Please limit your username to 32 characters or less";

		if (strlen($create['description']) > 256)
			$_errors['create_description'] = "Please limit your description to 256 characters or less";

		if ($user->user_exists($create['username']))
			$_errors['create_username'] = "Username already in use";
		
		
		if (count($_errors) == 0)
		{
			if ($user->create_user($create['username'], $password, $create['display_username'], $create['email'], $create['description'], $create['news']))
			{
				setcookie('PERSONA_USER', $user->get_cookie(), null, '/');
				if ($return_url)
					header('Location: ' . $return_url);
				else
					header('Location: /');
				exit;
			}
		}
	}

	$title = "Login"; 
	include 'templates/header.php'; 
?>
<body>
    <div id="outer-wrapper">
        <div id="inner-wrapper">
<?php include 'templates/nav.php'; ?>
            <div id="header">
                <h2>Login</h2>
            </div>
            <div id="maincontent" class="login-signup">
                <div id="breadcrumbs">
                    Personas Home : Login    
                </div>
				<?php if (array_key_exists('success_message', $_errors)) echo '<p class="logout-success">' . $_errors['success_message'] . '</p>' ?>
<?php include 'templates/login_form.php'; ?>
<?php if (!array_key_exists('admin', $_GET))
		include 'templates/signup_form.php'; 
?>
            </div>
        </div>
    </div>
<?php include "templates/footer.php" ?>
</body>
</html>

