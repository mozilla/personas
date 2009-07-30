<?php $title = _("Forgot Your Password"); include 'header.php'; ?>
<body class="forgot-password">
    <div id="outer-wrapper">
        <div id="inner-wrapper">
<?php include 'nav.php'; ?>
            <div id="header">
                <h2><?= _("Forgot Your Password?")?></h2>
                <h3><?= _("Follow the easy steps below to start dressing up your browser!")?></h3>
            </div>
            <div id="maincontent">
                <p id="breadcrumbs"><?printf("<a href=\"%s\">" . _("Personas Home") . "</a> : <a href=\"%s\">" . _("Sign In") . "</a> : " . _("Forgot Your Password?"), $locale_conf->url('/'), $locale_conf->url('/signin'));?></p>
                
                <p><?= _("Your password reset information has been e-mailed to you.")?></p>
            </div>
        </div>
    </div>
<?php include 'footer.php'; ?>
</body>
</html>
