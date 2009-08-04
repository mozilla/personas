<?php
	require_once 'lib/personas_constants.php';
	require_once 'lib/user.php';	
    require_once 'lib/language.php';

	$user = new PersonaUser();
	$title = _("How to Get Started"); 
	include 'templates/header.php'; 
?>
<body>
    <div id="outer-wrapper">
        <div id="inner-wrapper">
<?php include 'templates/nav.php'; ?>
            <div id="header">
                <h2><?= _("Watch Our Demo");?></h2>
                <h3><?= _("Personas are lightweight, easy to install and easy to change \"skins\" for your Firefox web browser.");?></h3>
            </div>
            <div id="maincontent" class="demo">
                <div id="breadcrumbs">
                    <?printf("<a href=\"%s\">" . _("Personas Home") . "</a> : " . _("Watch Our Demo"), $locale_conf->url('/'));?>
                </div>
                <h3><?= _("How to Get Started");?></h3>
                    <? // This really should be converted to open <video> :-P ?>
					<object width="400" height="300"><param name="allowfullscreen" value="true"/>
					<param name="allowscriptaccess" value="always" />
					<param name="movie" value="http://vimeo.com/moogaloop.swf?clip_id=3841582&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;color=&amp;fullscreen=1" />
					<embed src="http://vimeo.com/moogaloop.swf?clip_id=3841582&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;color=&amp;fullscreen=1" type="application/x-shockwave-flash" 
					allowfullscreen="true" allowscriptaccess="always" width="400" height="300"></embed>
					</object>
                
            </div>
<?php include 'templates/get_personas.php'; ?>
            
        </div>
    </div>
<?php include 'templates/footer.php'; ?>
</body>
</html>
