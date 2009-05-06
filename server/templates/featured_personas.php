<?php
	$featured_description_max = 140;
	
	foreach (explode(":", FEATURED_PERSONAS) as $id)
	{
		$persona = $db->get_persona_by_id($id); 
		if (!$persona)
			continue;

		$persona['json'] = htmlentities(json_encode(extract_record_data($persona)));
		$persona['detail_url'] = "/gallery/persona/" . url_prefix($persona['id']);
		$persona['preview_image'] = PERSONAS_LIVE_PREFIX . '/' . url_prefix($persona['id']);
		$persona['short_description'] = $persona['description'];
		if (strlen($persona['short_description']) > $description_max)
		{
			$persona['short_description'] = substr($persona['short_description'], 0, $featured_description_max);
			$persona['short_description'] = preg_replace('/ [^ ]+$/', '', $persona['short_description']) . '...';
		}

		$personas[] = $persona; 
	}	
?>
			<div class="feature slideshow">
                <h3>Featured Personas</h3>
                <ul id="slideshow-nav">
<?php
				for ($i = 1; $i <= count($personas); $i++)
				{
					echo '<li><a href="#"' . ($i == 1 ? 'class="active"' : '') . ">$i</a></li>";
				}
?>
				</ul>
                <a href="#" id="slideshow-previous"><img src="/static/img/nav-prev.png" alt="Previous"/></a>
                <a href="#" id="slideshow-next"><img src="/static/img/nav-next.png" alt="Next"/></a>
                <div id="slideshow">
                    <ul id="slides">
<?php
				foreach ($personas as $persona)
				{
?>
                        <li>
                            <a href="/persona/<?= $persona['id'] ?>"><img class="preview persona" src="<?= $persona['preview_image'] ?>/preview_featured.jpg" persona="<?= $persona['json'] ?>"></a>
                            <h4><a href="/persona/<?= $persona['id'] ?>"><?= $persona['name'] ?></a></h4>
                            <p class="try"><a href="/persona/<?= $persona['id'] ?>">view details »</a></p>
                            <hr />
                            <p class="designer">By: <a href="/gallery/Designer/<?= $persona['author'] ?>"><?= $persona['author'] ?></a></p>
                            <p class="daily-users"><?= number_format($persona['popularity']) ?> active daily users</p>
                            <p><?= $persona['short_description'] ?></p>
                            <hr />

                        </li>
<?php
				}
?>
                    </ul>
                    
                </div>
            </div>
