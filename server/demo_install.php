<?php
	require_once 'lib/personas_constants.php';
	require_once 'lib/user.php';	

	$user = new PersonaUser();
	$title = "How to Get Started"; 
	include 'templates/header.php'; 
?>
<body>
    <div id="outer-wrapper">
        <div id="inner-wrapper">
<?php include 'templates/nav.php'; ?>
            <div id="header">
                <h2>Watch Our Demo</h2>
                <h3>Personas are lightweight, easy to install and easy to change “skins” for your Firefox web browser.</h3>
            </div>
            <div id="maincontent" class="demo">
                <div id="breadcrumbs">
                    <a href="/">Personas Home</a> : Watch Our Demo    
                </div>
                <h3>How to Get Started</h3>
                
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
