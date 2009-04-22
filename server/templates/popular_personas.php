            <div class="feature last">
                <h3>Most Popular Personas</h3>
                <ol class="popular">
<?php
	$list = $db->get_popular_personas(null,3);
	foreach ($list as $persona)
	{
		$persona_json = htmlentities(json_encode(extract_record_data($persona)));
?>
					<li>
                            <h4><?= $persona['name'] ?></h4>
                            <hr />
                            <img class="persona" alt="<?= $persona['name'] ?>" persona="<?= $persona_json ?>" src="<?= PERSONAS_LIVE_PREFIX . '/' . url_prefix($persona['id']) ?>/preview_popular.jpg">
                            <p class="downloads"><?= number_format($persona['popularity']) ?> active daily users</p>
                    </li>
<?php
	}
?>
                </ol>
            </div>
