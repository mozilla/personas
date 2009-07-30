<?php
	require_once 'lib/personas_constants.php';	
	require_once 'lib/personas_functions.php';	
	require_once 'lib/storage.php';
	require_once 'lib/user.php';
    require_once 'lib/language.php';
    
    $db = new PersonaStorage();
	$user = new PersonaUser();
	
    $title = _("Dress up your web browser"); 
	include 'templates/header.php'; 
?>
<body class="home">
    <div id="outer-wrapper">
        <div id="inner-wrapper">
<?php include 'templates/nav.php'; ?>
			<div id="header">
                <h2><?= _("What will your browser wear today?");?></h2>
                <h3><?= _("Personas are lightweight, easy-to-install and easy-to-change \"skins\" for your Firefox web browser.");?></h3>
                <div class="get-personas">
                    <div>
                        <p>
                            <?= _("<a href=\"https://addons.mozilla.org/firefox/downloads/latest/10900\" class=\"get-personas\" id=\"download\"><span>Get Personas for Firefox - Free</span>");?><span class="arrow"></span></a>
                            <script type="text/javascript" charset="utf-8">
                                $("#download").personasDownload({"bundle":"bundle-url", "bundle-text":'<span><?= _("Get Firefox and Personas - Free");?></span><span class="arrow"></span>'});
                            </script>
                        </p>
                        <p class="platforms-note"><?= _("Firefox Add-on for Windows, Mac or Linux");?></p>
                    </div>
                </div>
                
                <div id="more-info">
                    <div id="info">
                        <h4><?= _("Theme your browser according to your mood, hobby or season.");?> </h4>
                        <p><?= _("Click the green download button to get started!");?></p>                  
                    </div>
                </div>
            </div>
<?php include 'templates/featured_personas.php'; ?>
<?php include 'templates/featured_designer.php'; ?>
<?php include 'templates/movers.php'; ?>

        </div>
    </div>
<?php include 'templates/footer.php'; ?>
    <script type="text/javascript" charset="utf-8">
        $(document).ready(function () {
            $(".slideshow").slider();
            $("#more-info").popup();
            
            $("#header").ie6Warning({"message":'<div id="ie6"><?= _("Upgrade your browser to get the most out of this website. <a href=\"%LINK%\">Download Firefox for free</a>.");?></div>'});
            $("img.persona").previewPersona();
        });
    </script>
</body>
</html>
