<?php $title = _("Delete your Persona"); include 'header.php'; ?>
<body>
    <div id="outer-wrapper">
        <div id="inner-wrapper">
<?php include 'nav.php'; ?>
            <div id="header">
                <h2><?= _("Delete Your Persona");?></h2>
                <h3><?= _("Thanks for sharing your persona with us.");?></h3>
            </div>
            <div id="maincontent">
                <p id="breadcrumbs"><?printf("<a href=\"%s\">" . _("Personas Home") . "</a> : " . _("Delete Your Persona"), $locale_conf->url('/'));?></p>
				<?= _("Thank you for letting us host your persona. We'll look forward to seeing your future efforts!")?>
            </div>
            <div id="secondary-content">
              <ol id="upload-steps">
                <li>
                    <h3><?= _("Step 1:")?></h3>
                    <h4><?= $title ?></h4>
                </li>
                <li class="current">
                    <div class="wrapper">
                   		<h3><?= _("Step 2:")?></h3>
                    	<h4><?= _("Finish")?></h4>
                    </div>
                </li>
              </ol>
            </div>
        </div>
    </div>
<?php include 'footer.php'; ?>
    <script type="text/javascript" charset="utf-8">
        $(document).ready(function () {
            $("#header").ie6Warning({"message":'<div id="ie6"><?= _("Upgrade your browser to get the most out of this website. <a href="%LINK%">Download Firefox for free</a>.");?></div>'});
            $("#try-button").personasButton({
                                        'hasPersonas':'<span><?= _("wear this");?></span><span>&nbsp;</span>',
                                        'hasFirefox':'<span><?= _("get personas now!");?></span><span>&nbsp;</span>',
                                        'noFirefox':'<span><?= _("get personas with firefox");?></span><span>&nbsp;</span>'
                                        });
        });
    </script>
</body>
</html>
