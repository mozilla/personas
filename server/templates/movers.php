            <div class="feature last">
                <h3><?= _("Movers and Shakers");?></h3>
                <ol class="popular">
<?php
	$list = $db->get_movers(null);
	$count = 1;
	
	$featured_designer_list = $db->get_featured_designers();
	$featured_persona_list = $db->get_featured_personas();
	
	foreach ($list as $persona)
	{
		if (in_array($persona['id'], $featured_designer_list) 
			|| in_array($persona['id'], $featured_persona_list))
			continue;
		
		$persona_json = htmlentities(json_encode(extract_record_data($persona)));
?>
					<li>
                            <h4><?= $persona['name'] ?></h4>
                            <hr />
                             <?printf("<a href=\"%s\">", $locale_conf->url('/persona/' . $persona['id']));?><img class="persona" alt="<?= $persona['name'] ?>" persona="<?= $persona_json ?>" src="<?= PERSONAS_LIVE_PREFIX . '/' . url_prefix($persona['id']) ?>/preview_popular.jpg"></a>
                            <p class="author"><?printf("By: <a href=\"%s\">%s</a>", $locale_conf->url('/gallery/Designer/' . $persona['author']), $persona['display_username']);?></p>
                            <p class="downloads"><?printf(_("%d active daily users"), number_format($persona['popularity']));?></p>
                    </li>
<?php
		if (++$count > 3)
			break;
	}
?>
                </ol>
            </div>
