<?php
	require_once 'constants.inc';
	require_once 'storage.inc';

	$db = new PersonaStorage();
	$categories = $db->get_categories();
	
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

	if (array_key_exists('verdict', $_POST) && array_key_exists('id', $_POST))
	{
		$persona = $db->get_persona_by_id($_POST['id']);

		switch ($_POST['verdict'])
		{
			case 'accept':
				$db->approve_persona($persona{'id'});
				
				#rebuild category summary
				if (!is_dir(getenv('PERSONAS_STORAGE_PREFIX') . '/' . $persona{'category'})) { mkdir(getenv('PERSONAS_STORAGE_PREFIX') . '/' . $persona{'category'}); }

				$category_list = $db->get_personas_by_category($persona{'category'});
				$json = array();
				foreach ($category_list as $item)
				{
					$padded_id = $item{'id'} < 10 ? '0' . $item{'id'} : $item{'id'};
					$json[] = array('id' => $item{'id'}, 'header' => getenv('PERSONAS_URL_PREFIX') . '/' . $padded_id[0] . '/' . $padded_id[1] .  '/'. $item{'id'} . '/' . $item{'header'}, 'footer' => getenv('PERSONAS_URL_PREFIX') . '/' . $padded_id[0] . '/' . $padded_id[1] .  '/'. $item{'id'} . '/' . $item{'footer'});
				}
				file_put_contents(getenv('PERSONAS_STORAGE_PREFIX') . '/' . $persona{'category'} . '/all.json', json_encode($json));
				
				#rebuild recent for category
				$recent_list = $db->get_recent_personas($persona{'category'});
				$json = array();
				foreach ($recent_list as $item)
				{
					$padded_id = $item{'id'} < 10 ? '0' . $item{'id'} : $item{'id'};
					$json[] = array('id' => $item{'id'}, 'header' => getenv('PERSONAS_URL_PREFIX') . '/' . $padded_id[0] . '/' . $padded_id[1] .  '/'. $item{'id'} . '/' . $item{'header'}, 'footer' => getenv('PERSONAS_URL_PREFIX') . '/' . $padded_id[0] . '/' . $padded_id[1] .  '/'. $item{'id'} . '/' . $item{'footer'});
				}
				file_put_contents(getenv('PERSONAS_STORAGE_PREFIX') . '/' . $persona{'category'} . '/recent.json', json_encode($json));

				#rebuild recent
				$recent_list = $db->get_recent_personas();
				$json = array();
				foreach ($recent_list as $item)
				{
					$padded_id = $item{'id'} < 10 ? '0' . $item{'id'} : $item{'id'};
					$json[] = array('id' => $item{'id'}, 'header' => getenv('PERSONAS_URL_PREFIX') . '/' . $padded_id[0] . '/' . $padded_id[1] .  '/'. $item{'id'} . '/' . $item{'header'}, 'footer' => getenv('PERSONAS_URL_PREFIX') . '/' . $padded_id[0] . '/' . $padded_id[1] .  '/'. $item{'id'} . '/' . $item{'footer'});
				}
				file_put_contents(getenv('PERSONAS_STORAGE_PREFIX') . '/recent.json', json_encode($json));
				
				break;
			case 'change':
				$category = ini_get('magic_quotes_gpc') ? stripslashes($_POST['category']) : $_POST['category'];
				$db->change_persona_category($persona{'id'}, $category);
				break;			
			case 'reject':
				#unlink($header_path);
				#unlink($footer_path);
				$db->reject_persona($persona{'id'});
				break;
			default:
				print "<html><body>Could not understand the verdict</body></html>";	
				exit;
		}
	}
	
	
	$results = $db->get_pending_personas();
	
	if (!$count = count($results))
	{
		print "<html><body>There are no more pending personas</body></html>";
		exit;
	}
	
	$result = $results[0];
	$second_folder = $result['id']%10;
	$first_folder = ($result['id']%100 - $second_folder)/10;
	$path = getenv('PERSONAS_URL_PREFIX') . '/' .  $first_folder . '/' . $second_folder . '/' . $result{'id'};
	$preview_url =  $path . "/preview.jpg";
	$header_url =  $path . "/" . $result['header'];
	$footer_url =  $path . "/" . $result['footer'];
?>
<html>
<body>
<form action="pending.php" method="POST">
<input type=hidden name=id value=<?= $result{'id'} ?>>
Internal ID: <?= $result{'id'} ?>
<br>
Name: <?= $result{'name'} ?>
<br>
User: <?= $result{'user'} ?>
<br>
Category: <select name="category">
<?php
	foreach ($categories as $category)
	{
		print "<option" . ($result{'category'} == $category ? ' selected="selected"' : "") . ">$category</option>";
	}
?>
</select><input type="submit" name="verdict" value="change">
<br>
<p>
Preview:
<br>
<img src="<?= $preview_url ?>"><br>
<img src="<?= $header_url ?>"><br>
<img src="<?= $footer_url ?>"><br>
<p>
<input type="submit" name="verdict" value="accept">
<input type="submit" name="verdict" value="reject">
</form>
</body>
</html>
