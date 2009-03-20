<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
            "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>Personas for Firefox | Dress up your web browser</title>
	<link href="/store/css/style.css" rel="stylesheet" type="text/css" media="all" />
</head>
<body class="home">
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
                <h2>What will your browser wear today?</h2>
                <h3>Personas are lightweight, easy-to-install and easy-to-change "skins" for your Firefox web browser.</h3>
                <div class="get-personas">
                    <div>
                        <p>
                            <a href="https://addons.mozilla.org/en-US/firefox/downloads/latest/10900" class="get-personas" id="download"><span>Get Personas for Firefox - Free</span><span class="arrow"></span></a>
                        </p>
                        <p class="platforms-note">Firefox Add-on for Windows, Mac or Linux</p>
                    </div>
                </div>
                
                <div id="more-info">
                    <div id="info">
                        <h4>Theme your browser according to your mood, hobby or season. </h4>
                        <p>Click the green download button to get started!</p>                  
                    </div>
                </div>
            </div>
            <div class="feature slideshow">
                <h3>Featured Personas</h3>
                <ul id="slideshow-nav">
                    <li><a href="#" class="active">1</a></li>
                    <li><a href="#">2</a></li>
                    <li><a href="#">3</a></li>
                    <li><a href="#">4</a></li>
                </ul>
                <a href="#" id="slideshow-previous"><img src="img/nav-prev.png" alt="Previous"/></a>
                <a href="#" id="slideshow-next"><img src="img/nav-next.png" alt="Next"/></a>
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
?>
                        <li>
                            <img class="preview persona" src="<?= PERSONAS_LIVE_PREFIX . '/' . url_prefix($persona['id']) ?>/preview_featured.jpg" persona="<?= $persona_json ?>>
                            <h4><?= $persona['name'] ?></h4>
                            <p class="try"><a href="#">view details »</a></p>
                            <hr />
                            <p class="designer"><strong>Designer:</strong> <?= $persona['author'] ?></p>
                            <p class="added"><strong>Added:</strong> <?= $persona_date?></p>
                            <hr />
                            <p class="description"><strong>Description:</strong> <?= $item_description ?></p>
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
                    <p class="try"><a href="#">view details »</a></p>
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
        <p>Copyright © <?= date("Y") ?> Mozilla. Personas for Firefox is a Mozilla Labs Beta Project | <a href="http://labs.mozilla.com/about-labs/">About Mozilla Labs</a>    |  <a href="https://www.getpersonas.com/store/privacy.html">Privacy</a></p>
    </div>
    <script src="/store/js/jquery.js"></script>
    <script src="/store/js/script.js"></script>
    <script type="text/javascript" charset="utf-8">
        $(document).ready(function () {
            $("#slideshow").slider();
            $("#more-info").popup();
            $("#download").personasDownload({"bundle":"bundle-url", "bundle-text":'<span>Get Firefox and Personas - Free</span><span class="arrow"></span>'});
            $("#header").ie6Warning({"message":'<div id="ie6">Upgrade your browser to get the most out of this website. <a href="%LINK%">Download Firefox for free</a>.</div>'});
            $("img.persona").previewPersona();
        });
    </script>
</body>
</html>
