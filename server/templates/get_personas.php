            <div id="secondary-content">
              <ol id="demo-nav">
<?php if (preg_match('/demo_create/', $_SERVER['SCRIPT_URL'])) { ?>
              <li class="current">
                  <div class="wrapper">
                      <span><?= _("How to Create Personas");?></span>
                  </div>
              </li>
<?php } else { ?>
              <li> 
					<?printf("<a href=\"%s\">" .  _("How to Create Personas") . "</a>", $locale_conf->url('/demo_create'));?>
				</li>
<?php } ?>
<?php if (preg_match('/demo_install/', $_SERVER['SCRIPT_URL'])) { ?>
                <li class="current">
                    <div class="wrapper">
                        <span><?= _("How to Get Started");?></span>
                    </div>
                </li>
<?php } else { ?>
                <li> 
					<?printf("<a href=\"/demo_install\">" . _("How to Get Started") . "</a>", $locale_conf->url('/demo_install'));?>
				</li>
<?php } ?>

<?php if (preg_match('/faq/', $_SERVER['SCRIPT_URL'])) { ?>
                <li class="current">
                    <div class="wrapper">
                        <span><?= _("Frequent Questions");?></span>
                    </div>
                </li>
<?php } else { ?>
                <li> 
					<?printf("<a href=\"%s\">" . _("Frequent Questions") . "</a>", $locale_conf->url('faq'));?>
				</li>
<?php } ?>
              </ol>
              <div class="info-box">
                <h3><a href="<?php echo PERSONAS_ADDON_URL ?>" id="getpersonas"><?= _("Get Personas Free");?></a></h3>
                <div class="body">
                    <p><?= _("Easy to install and easy to change \"skins\" for your Firefox web browser.");?></p>
                </div>
              </div>
            </div>
            <script type="text/javascript" charset="utf-8">
                $("#getpersonas").personasDownload({"bundle":"bundle-url", "bundle-text":<?= _("Get Firefox and Personas");?>});
            </script>
