            <div class="feature slideshow">
                <h3>Featured Personas</h3>
                <ul id="slideshow-nav">
                    <li><a href="#" class="active">1</a></li>
                    <li><a href="#">2</a></li>
                    <li><a href="#">3</a></li>
                    <li><a href="#">4</a></li>
                </ul>
                <a href="#" id="slideshow-previous"><img src="/static/img/nav-prev.png" alt="Previous"/></a>
                <a href="#" id="slideshow-next"><img src="/static/img/nav-next.png" alt="Next"/></a>
                <div id="slideshow">
                    <ul id="slides">
<?php


	$featured = $db->get_featured_personas();
	$description_max = 50;
	foreach ($featured as $persona)
	{
		$item_description = $persona['description'];
		if (strlen($item_description) > $description_max)
		{
			$item_description = substr($item_description, 0, $description_max);
			$item_description = preg_replace('/ [^ ]+$/', '', $item_description) . '...';
		}
		$persona_date = date("n/j/Y", strtotime($persona['approve']));
		$persona_json = htmlentities(json_encode(extract_record_data($persona)));
		$detail_url = "/gallery/persona/" . url_prefix($persona['id']);
?>
                        <li>
                            <img class="preview persona" src="<?= PERSONAS_LIVE_PREFIX . '/' . url_prefix($persona['id']) ?>/preview_featured.jpg" persona="<?= $persona_json ?>">
                            <h4><?= $persona['name'] ?></h4>
                            <p class="try"><a href="<?= $detail_url ?>">view details »</a></p>
                            <hr />
                            <p class="designer"><strong>Designer:</strong> <?= $persona['author'] ?></p>
                            <p class="added"><strong>Added:</strong> <?= $persona_date?></p>
                            <hr />

                        </li>
<?php
	}
?>
                    </ul>
                    
                </div>
            </div>
