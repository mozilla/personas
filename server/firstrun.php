<?php
	require_once 'lib/personas_constants.php';	
	require_once 'lib/personas_functions.php';	
	require_once 'lib/storage.php';
	require_once 'lib/user.php';

	$db = new PersonaStorage();
	$user = new PersonaUser();

	$title = _("Thanks for installing Personas"); 
	include 'templates/header.php'; 
?>
<body class="firstrun">
    <div id="outer-wrapper">
        
         
        <div id="inner-wrapper">
                       <p id="account">
           
            			</p>
                        <div id="nav">
                            <h1><a href="/"><img src="/static/img/logo.png" alt="Mozilla Labs Personas"></a></h1>
                                <div id="check-it-out">
                                    <div class="hd">
                                        &nbsp;
                                    </div>
                                    <p class="bd">
                                        <?= _("Check it out! Your browser's all dressed up.");?>                        
                                    </p>

                                    <div class="ft">
                                        &nbsp;
                                    </div>
                                </div>
                        </div>

            
            <div id="header">
                <h2><?= _("Thanks for Installing Personas for Firefox!");?></h2>
                <h2><?= _("The Easiest Way to Dress Up Your Browser.");?></h2>
                <p>
                    <a href="/gallery" class="cta">
                        <span><?= _("Browse the Gallery!");?></span>
                        <span class="arrow"></span>
                    </a>
                </p>
                
            </div>
            
<?php include 'templates/featured_designer.php'; ?>
            
            <div class="feature ">
                <h3><?= _("Get Started with Personas");?></h3>
                <ol class="get-started">
                    <li class="one"><?printf(_("Click on the fox mask in the lower left corner of your Firefox browser, or go to the Personas page directly from <a href=\"%s\">here</a>."), $locale_conf->url('/'));?></li>
                    <li class="two"><?printf(_("Next, select a Persona from the list, or check out the <a href=\"%s\">Personas gallery</a>."), $locale_conf->url('/gallery/All/Popular'));?></li>
                    <li class="three"><?printf(_("You can change your persona as much as you like! Choose a new one from the list or <a href=\"%s\">create your own</a>."), $locale_conf->url('/upload'));?></li>
                </ol>
                
                <p><?printf(_("Have a Personas question or comment? Check out our <a href=\"%s\">FAQ</a> section or <a href=\"https://labs.mozilla.com/forum/?CategoryID=18\">discussion forum</a>."), $locale_conf->url('/faq'));?>
                </p>
            </div>
            
            <div class="feature last more">
                <h3><?= _("Find out more about Firefox");?></h3>
                <p><?= _("Wondering what to do now? Our <a href=\"http://www.mozilla.com/firefox/central/\">Getting Started</a> page has plenty of helpful information.");?></p>
                <p><?= _("Questions? Our <a href=\"https://labs.mozilla.com/forum/?CategoryID=18\">Support page</a> has answers.");?></p>
                <p><?= _("Ready to customize? Now that you’ve got Firefox and Personas, find out more about all the ways you can <a href=\"https://addons.mozilla.org/firefox\">personalize Firefox</a>!";?></p>
             
            </div>
            
        </div>
    </div>
<?php include 'templates/footer.php'; ?>
    <script type="text/javascript" charset="utf-8">
        $(document).ready(function () {
            $(".slideshow").slider();
            $("img.persona").previewPersona();
        });
    </script>
    <p id="get-more-personas">
       <?= _("Click on the fox mask to get started!");?>
    </p>
</body>
</html>
