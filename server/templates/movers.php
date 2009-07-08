            <div class="feature last">
                <h3>Movers and Shakers</h3>
                <ol class="popular">
<?php
	$list = $db->get_movers(null);
	$count = 1;
	
	$featured_designer_list = explode(":", FEATURED_DESIGNERS);
	$featured_persona_list = explode(":", FEATURED_PERSONAS);
	
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
                             <a href="/persona/<?= $persona['id'] ?>"><img class="persona" alt="<?= $persona['name'] ?>" persona="<?= $persona_json ?>" src="<?= PERSONAS_LIVE_PREFIX . '/' . url_prefix($persona['id']) ?>/preview_popular.jpg"></a>
                            <p class="author">By: <a href="/gallery/Designer/<?= $persona['author'] ?>"><?= $persona['display_username'] ?></a></p>
                            <p class="downloads"><?= number_format($persona['popularity']) ?> active daily users</p>
                    </li>
<?php
		if (++$count > 3)
			break;
	}
?>
                </ol>
            </div>
