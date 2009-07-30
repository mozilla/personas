    <div id="footer">
        <form class="languages go" method="get" action="">
        <div>
            <label for="language"><?= _("Other languages:");?></label>
            <select id="language" name="lang" dir="ltr">
                <option value="en-US" selected="selected">English (US)</option>
            </select>
            <button>Go</button>
        </div>
        </form>

        <p><?printf(_("Copyright &copy; %s Mozilla.") . _(" <a href=\"http://labs.mozilla.com/projects/firefox-personas/\">Personas</a> is a <a href=\"http://labs.mozilla.com\">Mozilla Labs</a> experiment. | <a href=\"http://labs.mozilla.com/about-labs/\">") . _("About Mozilla Labs") . "</a>    |  <a href=\"%s\">" . _("Privacy") . "</a>", date("Y"), $locale_conf->url('/privacy'));?></p>
    </div>

    
	<script src="/static/js/urchin.js"></script>
    <script type="text/javascript">
          urchinTracker();
    </script>
