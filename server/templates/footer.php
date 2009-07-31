    <div id="footer">

        <p><?printf(_("Copyright &copy; %s Mozilla.") . _(" <a href=\"http://labs.mozilla.com/projects/firefox-personas/\">Personas</a> is a <a href=\"http://labs.mozilla.com\">Mozilla Labs</a> experiment. | <a href=\"http://labs.mozilla.com/about-labs/\">") . _("About Mozilla Labs") . "</a>    |  <a href=\"%s\">" . _("Privacy") . "</a>", date("Y"), $locale_conf->url('/privacy'));?>
        <form id="language-dropdown" class="languages go" method="get" action="">
            <?= _("Other languages:");?>
            <select id="language" name="lang" dir="ltr" target="_parent._top">
                <?
                    // TODO: We need to add the localized version of each language (e.g. "Chinese" in literal Chinese characters)
                    foreach ($locale_conf->_supported_languages as $lang => $lang_code) {
                        echo "<option value=\"$lang\" " . ($lang == $locale_conf->current_language ? "selected=\"selected\" " : "") . ">$lang</option>";
                    }
                ?>
            </select>
        </form>
    </p>
    </div>
    
	<script src="/static/js/urchin.js"></script>
    <script type="text/javascript">
        urchinTracker();
        $(function () {
            $.chooseLocale("<?= $locale_conf->current_language;?>");
        });
    </script>
