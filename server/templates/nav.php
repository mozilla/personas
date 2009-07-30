            <p id="account">
<?php 
	if(!array_key_exists('no_my', $_GET) && $user->get_unauthed_username())
	{
		printf(_("Welcome, %s") . " | <a href=\"%s\"> " . _("Edit Account") . " </a> | <a href=\"%s\">  " . _("Sign Out") . " </a>", $user->get_unauthed_username(), $locale_conf->url('/profile'), $locale_conf->url('/signin?action=signout&return=' . $_SERVER['SCRIPT_URL']));
	}
	elseif(!array_key_exists('no_my', $_GET))
	{
		if (array_key_exists('signout_success', $_GET))
			echo _("You have been signed out | ");
		printf("<a href=\"%s\">" .  _("Sign In") . "</a>", $locale_conf->url('/signin?return=' . $_SERVER['SCRIPT_URL']));
	}
?>
			</p>
            <div id="nav">
                <h1><?printf("<a href=\"%s\"><img src=\"/static/img/logo.png\" alt=\"" . _("Mozilla Labs Personas") . "\" /></a>", $locale_conf->url('/'));?></h1>
                
                <?php if(isset($showCheckItOut) && $showCheckItOut) { ?>
                    <div id="check-it-out">
                        <div class="hd">
                            &nbsp;
                        </div>
                        <p class="bd">
                            <?echo _("Check it out! Your browser's all dressed up.")?>
                        </p>

                        <div class="ft">
                            &nbsp;
                        </div>
                    </div>
                <?php } ?>
                <?php if(!(isset($hidenav) && $hideNav)) { ?>
                    <ul>
                        <li class="gallery"><?printf("<a href=\"%s\">" . _("Gallery") . "</a>", $locale_conf->url('/gallery/All/Popular'));?></li>
                        <li class="create"><?printf("<a href=\"%s\">" . _("Create <br/>Your Own") . "</a>", $locale_conf->url('/upload'));?></li>
                        <li class="demo"><?printf("<a href=\"%s\">" . _("How To") . "</a>", $locale_conf->url('/demo_create'));?></li>
                        <li class="faq"><?printf("<a href=\"%s\">" . _("Frequent <br/>Questions") . "</a>", $locale_conf->url('/faq'));?></li>
                        <li class="search">
                            <form action=<?printf("%s", $locale_conf->url('/gallery/All/search'));?> method="GET">
                                <p><?echo _("Search personas:");?></p>
                                <input id="q" name="p" type="text" />
                                <input type="image" name="search" value="" id="submit" src="/static/img/search-button.png" />
                            </form>
                        </li>
                    </ul>
                <?php } ?>
            </div>
