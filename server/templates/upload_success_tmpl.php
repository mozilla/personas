<?php $title = _("Success!"); include 'header.php'; ?>
<body>
    <div id="outer-wrapper">
        <div id="inner-wrapper">
<?php include 'nav.php'; ?>
            <div id="header">
                <h2><?= _("Create Your Own");?></h2>
                <h3><?= _("It's easy to create your own Persona just follow the easy steps below!");?></h3>
            </div>
            <div id="maincontent" class="success">
                <div id="breadcrumbs">
                    <?printf("<a href=\"%s\">" . _("Personas Home") . "</a> : " . _("Create Your Own"), $locale_conf->url('/'));?>
                </div>
                <h2><?= _("Success!");?></h2>
                <h3><?= $action_sentence ?></h3>
                <ul class="success-options">
                    <li><?printf("<a href=\"%s\">" . _("View Personas Gallery") . "</a>", $locale_conf->url('/gallery/All/Popular'));?></li>
                </ul>
            </div>
            <div id="secondary-content">
              <ol id="upload-steps">
                  <li class="completed"> <!-- class="completed" needed to show green checkbox -->
                      <h3><?= _("Step 1:");?></h3>
                      <h4><?= _("Terms of Service");?></h4>
                  </li>
                <li class="completed"> 
                    <h3><?= _("Step 2:");?></h3>
                    <h4><?= _("Create Your Persona");?></h4> 
                </li>
                
                <li class="completed">
                    <h3><?= _("Step 3:");?></h3>
                    <h4><?= _("Finish!");?></h4>
                </li>
              </ol>
            </div>
        </div>
    </div>
<?php include 'footer.php'; ?>
</body>
</html>
