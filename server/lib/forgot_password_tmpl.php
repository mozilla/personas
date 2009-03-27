<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
            "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>Personas for Firefox | Forgot Your Password</title>
	<link href="/store/css/style.css" rel="stylesheet" type="text/css" media="all" />

</head>
<body class="forgot-password">
    <div id="outer-wrapper">
        <div id="inner-wrapper">
            <p id="account"><a href="https://personas.services.mozilla.com/upload?action=logout">Sign out</a></p>
            <div id="nav">
                <h1><a href="http://www.getpersonas.com/"><img src="/store/img/logo.png" alt="Mozilla Labs Personas"></a></h1>
                <ul>
                    <li class="gallery"><a href="http://www.getpersonas.com/store/gallery/All/Popular">Gallery</a></li>
                    <li class="create"><a href="https://personas.services.mozilla.com/upload">Create <br/>Your Own</a></li>
                    <li class="demo"><a href="http://www.getpersonas.com/store/demo_install.html">Demo</a></li>
                    <li class="faq"><a href="http://www.getpersonas.com/store/faq.html">Frequent <br/>Questions</a></li>
                </ul>
            </div>
            <div id="header">
                <h2>Forgot Your Password?</h2>
            </div>
            <div id="maincontent">
                <p id="breadcrumbs"><a href="http://www.getpersonas.com">Personas Home</a> : <a href="https://personas.services.mozilla.com/upload">Sign In</a> : Forgot Your Password?</p>
<?php 
	if ($error)
		echo "<h4 class=\"error\">$error</h4>";
?>
                <h4>Please enter your Personas username below</h4>
                <form action="forgot_password" method="post">
                    <p>
                        <label for="username">Username:</label>
                        <input type="text" name="userreq" value="" id="username"/>
                    </p>
                    
                    <button type="submit" class="button"><span>continue</span><span class="arrow">&nbsp;</span></button>
                </form>
            </div>
            
        </div>
    </div>
    
   
    <div id="footer">
        <p>Copyright © <?= date("Y") ?> Mozilla. <a href="http://labs.mozilla.com/projects/firefox-personas/">Personas</a> is a <a href="http://labs.mozilla.com">Mozilla Labs</a> experiment. | <a href="http://labs.mozilla.com/about-labs/">About Mozilla Labs</a>    |  <a href="http://www.getpersonas.com/store/privacy.html">Privacy</a></p>
    </div>
</body>
</html>
