<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
            "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>Personas For Firefox | Firstrun</title>
	<link href="/store/css/style.css" rel="stylesheet" type="text/css" media="all" />
</head>
<body class="updated">
    <div id="outer-wrapper">
        <div id="inner-wrapper">
            <p id="account"></p>
            <div id="nav">
                <h1><a href="https://www.getpersonas.com"><img src="/store/img/logo.png" alt="Mozilla Labs Personas"></a></h1>
                
                <div id="check-it-out">
                    <div class="hd">
                        &nbsp;
                    </div>
                    <p class="bd">
                        Check it out! Your browser's all dressed up.                        
                    </p>
                    <div class="ft">
                        &nbsp;
                    </div>
                </div>
                
            </div>
            <div id="header">
                <h2>You've been updated to the latest version of Personas!</h2>

            </div>
            
            <div class="feature slideshow">
                <h3>Featured Personas</h3>
                <ul id="slideshow-nav">
                    <li><a href="#" class="active">1</a></li>
                    <li><a href="#">2</a></li>
                    <li><a href="#">3</a></li>
                    <li><a href="#">4</a></li>
                </ul>
                <a href="#" id="slideshow-previous"><img src="/store/img/nav-prev.png" alt="Previous"/></a>
                <a href="#" id="slideshow-next"><img src="/store/img/nav-next.png" alt="Next"/></a>
                <div id="slideshow">
                    <ul id="slides">
<?php
	require_once 'lib/personas_constants.php';	
	require_once 'lib/personas_functions.php';	
	require_once 'lib/storage.php';


	$db = new PersonaStorage();
	$featured = $db->get_featured_personas();
	$description_max = 50;
	foreach ($featured as $persona)
	{
		$item_description = $persona['description'];
		if (strlen($item_description) > $description_max)
		{
			$item_description = substr($item_description, 0, $description_max);
			$item_description = preg_replace('/ [^ ]+$/', '', $item_description) . '...';
		}
		$persona_date = date("n/j/Y", strtotime($persona['approve']));
		$persona_json = htmlentities(json_encode(extract_record_data($persona)));
		$detail_url = "/store/gallery/persona/" . url_prefix($persona['id']);
?>
                        <li>
                            <img class="preview persona" src="<?= PERSONAS_LIVE_PREFIX . '/' . url_prefix($persona['id']) ?>/preview_featured.jpg" persona="<?= $persona_json ?>>
                            <h4><?= $persona['name'] ?></h4>
                            <p class="try"><a href="<?= $detail_url ?>">view details »</a></p>
                            <hr />
                            <p class="designer"><strong>Designer:</strong> <?= $persona['author'] ?></p>
                            <p class="added"><strong>Added:</strong> <?= $persona_date?></p>
                            <hr />

                        </li>
<?php
	}
?>
                    </ul>
                    
                </div>
            </div>
            <div class="feature">
                <h3>Featured Designer</h3>
                <img src="/store/img/greenpeace-featured.jpg" class="preview">
                <h4>GreenPeace</h4>
                
            </div>
            <div class="feature last">
                <h3>Most Popular Personas</h3>
                <ol class="popular">
<?php
	$list = $db->get_popular_personas(null,3);
	foreach ($list as $persona)
	{
		$persona_json = htmlentities(json_encode(extract_record_data($persona)));
?>
					<li>
                            <h4><?= $persona['name'] ?></h4>
                            <hr />
                            <img class="persona" alt="<?= $persona['name'] ?>" persona="<?= $persona_json ?>" src="<?= PERSONAS_LIVE_PREFIX . '/' . url_prefix($persona['id']) ?>/preview_popular.jpg">
                            <p class="downloads"><strong>Current Users:</strong> <?= $persona['popularity'] ?></p>
                    </li>
<?php
	}
?>
                </ol>
                
                
            </div>
            
        </div>
    </div>
    <div id="footer">
        <p>Copyright © 2009 Mozilla. Personas for Firefox is a Mozilla Labs Beta Project | <a href="http://labs.mozilla.com/about-labs/">About Mozilla Labs</a>    |  <a href="../privacy.html">Privacy</a></p>
    </div>
    <script src="/store/js/jquery.js"></script>
    <script src="/store/js/script.js"></script>
    <script type="text/javascript" charset="utf-8">
        $(document).ready(function () {
            $("#slideshow").slider();
        });
    </script>
</body>
</html>
