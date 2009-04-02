<?php
	require_once 'lib/personas_constants.php';	
	require_once 'lib/personas_functions.php';	
	require_once 'lib/storage.php';
	require_once 'lib/user.php';

	$db = new PersonaStorage();
	$user = new PersonaUser();

	$title = "Thanks for installing Personas"; 
	include 'templates/header.php'; 
?>
<body class="firstrun">
    <div id="outer-wrapper">
        <div id="inner-wrapper">
<?php include 'templates/nav.php'; ?>
            <div id="header">
                <h2>Thanks for Installing Personas for Firefox!</h2>
                <h2>The Easiest Way to Dress Up Your Browser.</h2>
            </div>
            
<?php include 'templates/featured_designer.php'; ?>
            
            <div class="feature ">
                <h3>Get Started with Personas</h3>
                <ol class="get-started">
                    <li class="one">Click on the fox mask in the lower left corner of your Firefox browser, or go to the Personas page directly from <a href="http://www.getpersonas.com">here</a>.</li>
                    <li class="two">Next, select a Persona from the list, or check out the <a a href="/gallery/All/Popular">Personas gallery</a>.</li>
                    <li class="three">You can change your persona as much as you like! Choose a new one from the list or <a href="https://personas.services.mozilla.com/upload">create your own</a>.</li>
                </ol>
                
                <p>Have a Personas question or comment? Check out our <a href="/faq">FAQ</a> section or <a href="https://labs.mozilla.com/forum/?CategoryID=18">discussion
                forum</a>.
                </p>
            </div>
            
            <div class="feature last more">
                <h3>Find out more about Firefox</h3>
                <p>Wondering what to do now? Our <a href="http://en-us.www.mozilla.com/en-US/firefox/central/">Getting Started</a> page has plenty of helpful information.</p>
                <p>Questions? Our <a href="https://labs.mozilla.com/forum/?CategoryID=18">Support page</a> has answers.</p>
                <p>Ready to customize? Now that you’ve got Firefox and Personas, find out more about all the ways you can <a href="https://addons.mozilla.org/en-US/firefox">personalize Firefox</a>!</p>
             
            </div>
            
        </div>
    </div>
<?php include 'templates/footer.php'; ?>
    <p id="get-more-personas">
       Click on the fox mask to get started!
    </p>
</body>
</html>