<?php 
	require_once 'lib/personas_constants.php';	
	require_once 'lib/personas_functions.php';	
	require_once 'lib/storage.php';
	

	$db = new PersonaStorage();
	$categories = $db->get_categories();
	array_unshift($categories, 'All');
	$category = null;
	
	$path = array_key_exists('PATH_INFO', $_SERVER) ? $_SERVER['PATH_INFO'] : '/';
	$path = substr($path, 1); #chop the lead slash
	list($persona_id) = explode('/', $path.'');

	if (!is_numeric($persona_id))
		$persona_id = null;
	else
	{
		$persona_id = intval($persona_id);
		$persona_data = $db->get_persona_by_id($persona_id);
		$category = $persona_data['category'];
		$persona_json = htmlentities(json_encode(extract_record_data($persona_data)));
	}
	
	
?>


<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
            "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>Personas for Firefox | Persona Detail</title>
	<link href="/store/css/style.css" rel="stylesheet" type="text/css" media="all" />

</head>
<body>
    <div id="outer-wrapper">
        <div id="inner-wrapper">
            <p id="account"><a href="https://personas.services.mozilla.com/upload">Designer Tools</a></p>
            <div id="nav">
                <h1><a href="https://www.getpersonas.com/"><img src="/store/img/logo.png" alt="Mozilla Labs Personas"></a></h1>
                <ul>
                    <li class="gallery"><a href="https://www.getpersonas.com/store/gallery/All/Popular">Gallery</a></li>
                    <li class="create"><a href="https://personas.services.mozilla.com/upload">Create <br/>Your Own</a></li>
                    <li class="demo"><a href="https://www.getpersonas.com/store/demo_install.html">Demo</a></li>
                    <li class="faq"><a href="https://www.getpersonas.com/store/faq.html">Frequent <br/>Questions</a></li>
                </ul>
            </div>
            <div id="header">
                <h2>View Personas</h2>
                <h3>Your browser, your style! Dress it up with easy-to-change "skins" for your
                Firefox.</h3>
            </div>
            <div id="maincontent">
                <p id="breadcrumbs">Personas Home : View Personas</p>
<?php
	if ($persona_data['id'])
	{
?>
				<h2><?= $persona_data['name'] ?></h2>
                <h3>created by <?= $persona_data['author'] ?></h3>
                <img class="detailed-view"  alt="<?= $item['name'] ?>" persona="<?= $persona_json ?>" src="<?= PERSONAS_LIVE_PREFIX . '/' . url_prefix($persona_id) ?>/preview_large.jpg" >
                
                <p class="description"><strong>Description:</strong> <?= $persona_data['description'] ?></p>
                
                <p id="buttons">
                    <a href="#" class="button"><span>try it now</span><span>&nbsp;</span></a>
                    
                </p>
                <p class="numb-users"><?= $persona_data['popularity'] ?> users</p>
<?php
	} else {
?>            
                <p class="description">We are unable to find this persona. Please return to the gallery and try again.</p>
<?php
	}
?>
            </div>
	<div id="secondary-content">
                <ul id="subnav">
<?php
			foreach ($categories as $list_category)
			{
				$category_url = "/store/gallery/$list_category";
				if ($list_category == $category)
				{
					echo "		<li class=\"active\"><a href=\"$category_url/Popular\">$list_category</a></li>\n";
				}
				else
				{
					echo "		<li><a href=\"$category_url/Popular\">$list_category</a></li>\n";
				}
			}
?>
                </ul>
            </div>
            
        </div>
    </div>
    
    <div id="footer">
        <p>Copyright © <?= date("Y") ?> Mozilla. Personas for Firefox is a Mozilla Labs Beta Project | <a href="http://labs.mozilla.com/about-labs/">About Mozilla Labs</a>    |  <a href="https://www.getpersonas.com/store/privacy.html">Privacy</a></p>
    </div>
    <script src="/store/js/jquery.js"></script>
    <script src="/store/js/script.js"></script>
    <script type="text/javascript" charset="utf-8">
        $(document).ready(function () {
            $("#header").ie6Warning({"message":'<div id="ie6">Upgrade your browser to get the most out of this website. <a href="%LINK%">Download Firefox for free</a>.</div>'});
        });
    </script>
</body>
</html>
