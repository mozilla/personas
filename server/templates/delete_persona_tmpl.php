<?php 
	error_log($persona['author']);
	error_log($auth_user);
	$delete_require = $persona['author'] != $auth_user ? ("onsubmit=\"if ($('#formreason').val() == '') {alert('". _("Please provide a reason for deletion") . "'); return false;}\"" : "";
	$title = _("Delete your Persona"); 
    
    include 'header.php'; 
?>
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
                
<?php include 'persona_detail.php' ?>
<?php if(!$override_error && $persona) { ?>
                <form method="POST" action="<?= $locale_conf->url('/delete/=' . $persona['id']);?>" enctype='multipart/form-data' <?= $delete_require ?>>
					<input type="hidden" name="id" value="<?= htmlspecialchars($persona['id']) ?>">
					<input type="hidden" name="confirm" value="1">
					<p class="continue">
<?php 	if($persona['author'] != $auth_user) { ?>
						<?= _("Delete Reason:");?> <input type="text" name="dreason" id=formreason> 
<?php 	} ?>
						<button type="submit" class="button"><span><?= _("confirm deletion");?></span><span class="arrow">&nbsp;</span></button>
					</p>
            	</form>
<?php } ?>
            </div>
            <div id="secondary-content">
              <ol id="upload-steps">
                <li class="current">
                    <div class="wrapper">
                        <h3><?= _("Step 1:");?></h3>
                        <h4><?= $title ?></h4>
                    </div>
                </li>
                <li>
                    <h3><?= _("Step 2:");?></h3>
                    <h4><?= _("Finish");?></h4>
                </li>
              </ol>
            </div>
        </div>
    </div>
<?php include 'footer.php'; ?>
    <script type="text/javascript" charset="utf-8">
        $(document).ready(function () {
            $("#header").ie6Warning({"message":'<div id="ie6"><?= _('Upgrade your browser to get the most out of this website. <a href="%LINK%">Download Firefox for free</a>.');?></div>'});
            $("#try-button").personasButton({
                                        'hasPersonas':'<span><?= _("wear this");?></span><span>&nbsp;</span>',
                                        'hasFirefox':'<span><?= _("get personas now!");?></span><span>&nbsp;</span>',
                                        'noFirefox':'<span><?= _("get personas with firefox");?></span><span>&nbsp;</span>'
                                        });
        });
    </script>
</body>
</html>
