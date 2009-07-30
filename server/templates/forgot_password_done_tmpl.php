<?php $title = _("Forgot Your Password"); include 'header.php'; ?>
<body class="forgot-password">
    <div id="outer-wrapper">
        <div id="inner-wrapper">
<?php include 'nav.php'; ?>
            <div id="header">
                <h2><?= _("Forgot Your Password?");?></h2>
            </div>
            <div id="maincontent">
                <p id="breadcrumbs"><?printf(_("<a href=\"%s\">Personas Home</a> : <a href=\"%s\">Sign In</a> : Forgot Your Password?"), $locale_conf->url('/'), $locale_conf->url('/signin'));?></p>
                <p><?printf(_("Your password has been reset. You may <a href=\"%s\">sign in</a> now."), $locale_conf->url('/signin'));?></p>
            </div>
        </div>
    </div>
<?php include 'footer.php'; ?>
</body>
</html>
