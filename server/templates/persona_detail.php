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
                <h3>created by <a href="/gallery/Designer/<?= $persona['author'] ?>"><?= $persona['display_username'] ?></a></h3>
                <?php
                  if ($user->get_unauthed_username())
            		{
                        $text = ($favorite_persona) ? 'Remove from favorites' : 'Add to favorites';
            		    $action = ($favorite_persona) ? 0 : 1;
            		    $class = ($favorite_persona) ? 'favorited':"";
            		    echo '<p class="favorite"><a href="/favorite/'.$persona_id.'/' . $nonce . '?action='.$action.'" class="'.$class.'">'.$text.'</a></p>';
            		}
                ?>
                <img class="detailed-view"  alt="<?= $persona['name'] ?>" persona="<?= $persona['json'] ?>" src="<?= PERSONAS_LIVE_PREFIX . '/' . url_prefix($persona['id']) ?>/preview_large.jpg" >
                
<?php           
		if ($persona['description'])
		{
			$desc = preg_replace('/(https?:\/\/[^ ]+[A-Za-z0-9\/])/', '<a href="$1">$1</a>', $persona['description']);
?>
				<p class="description"><strong>Description:</strong> <?= $desc ?></p>
<?php
		}
?>
                <p id="buttons">
                    <a href="#" class="button" id="try-button" persona="<?= $persona['json'] ?>"><span>try it now</span><span>&nbsp;</span></a>
                </p>
                
<?php
		if ($persona['popularity'])
			print '<p class="numb-users">' . number_format($persona['popularity']) . ' active daily users</p>';

?>
	<p><?php include('includes/sharethis.php'); ?></p>
<?php
	} else {
?>            
                <p class="description">We are unable to find this persona. Please return to the gallery and try again.</p>
<?php
	}
?>
