            <div id="secondary-content">
              <ol id="demo-nav">
<?php if (preg_match('/demo_install/', $_SERVER['SCRIPT_NAME'])) { ?>
                <li class="current">
                    <div class="wrapper">
                        <span>How to Get Started</span>
                    </div>
                </li>
<?php } else { ?>
                <li> 
					<a href="/demo_install">How to Get Started</a>
				</li>
<?php } ?>
<?php if (preg_match('/demo_create/', $_SERVER['SCRIPT_NAME'])) { ?>
                <li class="current">
                    <div class="wrapper">
                        <span>How to Create Personas</span>
                    </div>
                </li>
<?php } else { ?>
                <li> 
					<a href="/demo_create">How to Create Personas</a>
				</li>
<?php } ?>
<?php if (preg_match('/faq/', $_SERVER['SCRIPT_NAME'])) { ?>
                <li class="current">
                    <div class="wrapper">
                        <span>Frequent Questions</span>
                    </div>
                </li>
<?php } else { ?>
                <li> 
					<a href="/faq">Frequent Questions</a>
				</li>
<?php } ?>
              </ol>
              <div class="info-box">
                <h3><a href="<?php echo PERSONAS_ADDON_URL ?>" id="getpersonas">Get Personas Free</a></h3>
                <div class="body">
                    <p>Easy to install and easy to change "skins" for your Firefox web browser.</p>
                </div>
              </div>
            </div>
            <script type="text/javascript" charset="utf-8">
                $("#getpersonas").personasDownload({"bundle":"bundle-url", "bundle-text":'Get Firefox and Personas'});
            </script>