 <?php
	if ($override_error)
	{
?>            
                <p class="description"><?= $override_error ?></p>
<?php
	}
	else if ($persona['id'])
	{
?>
				<h2><?= $persona['name'] ?></h2>
                <h3><?printf("created by <a href=\"%s\">%s</a>", $locale_conf->url('/gallery/Designer/' . $persona['author']), $persona['display_username']);?></h3>
                <?php
                  if ($user->get_unauthed_username())
            		{
                        $text = ($favorite_persona) ? _("Remove from favorites") : _("Add to favorites");
            		    $action = ($favorite_persona) ? 0 : 1;
            		    $class = ($favorite_persona) ? _("favorited"):"";
            		    echo '<p class="favorite">' . sprintf("<a href=\"%s\" class=\"%s\">%s</a>", $locale_conf->url('/favorite' . $persona_id . '/' . $nonce . '?action=' . $action), $class, $text) . '</p>';
            		}
                ?>
                <img class="detailed-view"  alt="<?= $persona['name'] ?>" persona="<?= $persona['json'] ?>" src="<?= PERSONAS_LIVE_PREFIX . '/' . url_prefix($persona['id']) ?>/preview_large.jpg" >
                
<?php           
		if ($persona['description'])
		{
			$desc = preg_replace('/(https?:\/\/[^ ]+[A-Za-z0-9\/])/', '<a href="$1">$1</a>', $persona['description']);
?>
				<p class="description"><?= _("<strong>Description:</strong>");?> <?= $desc ?></p>
<?php
		}
?>
                <p id="buttons">
                    <a href="#" class="button" id="try-button" persona="<?= $persona['json'] ?>"><span><?= _("try it now");?></span><span>&nbsp;</span></a>
                </p>
                
<?php
		if ($persona['popularity'])
			print '<p class="numb-users">' . sprintf(_("%d active daily users"), number_format($persona['popularity'])) . '</p>';

?>
	<p><?php include('includes/sharethis.php'); ?></p>
<?php
	} else {
?>            
                <p class="description"><?= _("We are unable to find this persona. Please return to the gallery and try again.");?></p>
<?php
	}
?>
