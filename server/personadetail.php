<?php
	require_once 'lib/personas_constants.php';	
	require_once 'lib/personas_functions.php';	
	require_once 'lib/storage.php';
	require_once 'lib/user.php';

	$db = new PersonaStorage();
	$user = new PersonaUser();
	

	$categories = $db->get_categories();
	array_unshift($categories, 'All');
	$category = null;
	
	$path = array_key_exists('PATH_INFO', $_SERVER) ? $_SERVER['PATH_INFO'] : '/';
	$path = substr($path, 1); #chop the lead slash
	list($persona_id) = explode('/', $path);
	$page_header = 'View Personas';
	
	if (!is_numeric($persona_id))
		$persona_id = null;
	else
	{
		$persona_id = intval($persona_id);
		$persona_data = $db->get_persona_by_id($persona_id);
		$page_header = $persona_data['name'] . ' by ' . $persona_data['author'];
		$category = $persona_data['category'];
		$persona_json = htmlentities(json_encode(extract_record_data($persona_data)));
	}

	$url_prefix = '/gallery';
	$tabs = null;
	
	$title = "Persona Detail"; 
	include 'templates/header.php'; 
?>
<body>
    <div id="outer-wrapper">
        <div id="inner-wrapper">
<?php include 'templates/nav.php'; ?>
            <div id="header">
                <h2><?= $page_header ?></h2>
                <h3>Your browser, your style! Dress it up with easy-to-change "skins" for your
                Firefox.</h3>
            </div>
            <div id="maincontent">
                <p id="breadcrumbs"><a href="http://www.getpersonas.com">Personas Home</a> : View Personas</p>
<?php
	if ($persona_data['id'])
	{
?>
				<h2><?= $persona_data['name'] ?></h2>
                <h3>created by <?= $persona_data['author'] ?></h3>
                <img class="detailed-view"  alt="<?= $persona_data['name'] ?>" persona="<?= $persona_json ?>" src="<?= PERSONAS_LIVE_PREFIX . '/' . url_prefix($persona_id) ?>/preview_large.jpg" >
                
<?php
		if ($persona_data['description'])
		{
			$desc = preg_replace('/(https?:\/\/[^ ]+[A-Za-z0-9])/', '<a href="$1">$1</a>', $persona_data['description']);
?>
				<p class="description"><strong>Description:</strong> <?= $desc ?></p>
<?php
		}
?>
                <p id="buttons">
                    <a href="#" class="button" id="try-button" persona="<?= $persona_json ?>"><span>try it now</span><span>&nbsp;</span></a>
                </p>
                
<?php
		if ($persona_data['popularity'])
			print '<p class="numb-users">' . number_format($persona_data['popularity']) . ' active daily users</p>';
	?>
	<p><script type="text/javascript" src="http://w.sharethis.com/button/sharethis.js#publisher=df86b16e-195c-4917-ae28-61a1382ba281&amp;type=website&amp;send_services=&amp;post_services=facebook%2Cdigg%2Cdelicious%2Cybuzz%2Ctwitter%2Cstumbleupon%2Creddit%2Ctechnorati%2Cmixx%2Cblogger%2Ctypepad%2Cwordpress%2Cgoogle_bmarks%2Cwindows_live%2Cmyspace%2Cfark%2Cbus_exchange%2Cpropeller%2Cnewsvine%2Clinkedin"></script></p>
	<?php
	} else {
?>            
                <p class="description">We are unable to find this persona. Please return to the gallery and try again.</p>
<?php
	}
?>
            </div>
<?php include 'templates/category_nav.php'; ?>
            
        </div>
    </div>
    
<?php include 'templates/footer.php'; ?>
    <script type="text/javascript" charset="utf-8">
        $(document).ready(function () {
            $("#header").ie6Warning({"message":'<div id="ie6">Upgrade your browser to get the most out of this website. <a href="%LINK%">Download Firefox for free</a>.</div>'});
            $("#try-button").personasButton({
                                        'hasPersonas':'<span>wear this</span><span>&nbsp;</span>',
                                        'hasFirefox':'<span>get personas now!</span><span>&nbsp;</span>',
                                        'noFirefox':'<span>get personas with firefox</span><span>&nbsp;</span>'
                                        });
        });
    </script>
</body>
</html>
