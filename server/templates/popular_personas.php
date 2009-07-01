            <div class="feature last">
                <h3>Most Popular Personas</h3>
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
                             <a href="/persona/<?= $persona['id'] ?>"><img class="persona" alt="<?= $persona['name'] ?>" persona="<?= $persona_json ?>" src="<?= PERSONAS_LIVE_PREFIX . '/' . url_prefix($persona['id']) ?>/preview_popular.jpg"></a>
                            <p class="author">By: <a href="/gallery/Designer/<?= $persona['author'] ?>"><?= $persona['display_username'] ?></a></p>
                            <p class="downloads"><?= number_format($persona['popularity']) ?> active daily users</p>
                    </li>
<?php
	}
?>
                </ol>
            </div>
