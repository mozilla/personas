            <div class="feature last">
                <h3><?= _("Most Popular Personas");?></h3>
                <ol class="popular">
<?php
	$list = array_slice($db->get_popular_personas(null), 0, 3);
	
	foreach ($list as $persona)
	{
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
	}
?>
                </ol>
            </div>
