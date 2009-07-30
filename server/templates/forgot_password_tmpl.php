<?php $title = _("Forgot Your Password"); include 'header.php'; ?>
<body class="forgot-password">
    <div id="outer-wrapper">
        <div id="inner-wrapper">
<?php include 'nav.php'; ?>
            <div id="header">
                <h2><?= _("Forgot Your Password?")?></h2>
            </div>
            <div id="maincontent">
                <p id="breadcrumbs"><?printf("<a href=\"%s\">" . _("Personas Home") . "</a> : <a href=\"%s\">" . _("Sign In") . "</a> : " . _("Forgot Your Password?"), $locale_conf->url('/'), $locale_conf->url('/signin'));?></p>
<?php 
	if ($error)
		echo "<h4 class=\"error\">" . $error . "</h4>";
?>
                <h4><?= _("Please enter your Personas username below");?></h4>
                <form action="forgot_password" method="post">
                    <p>
                        <label for="username"><?= _("Username:")?></label>
                        <input type="text" name="userreq" value="" id="username"/>
                    </p>
                    
                    <button type="submit" class="button"><span><?= _("continue")?></span><span class="arrow">&nbsp;</span></button>
                </form>
            </div>
            
        </div>
    </div>
    
   
<?php include 'footer.php'; ?>
</body>
</html>
