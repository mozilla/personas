<?php
	$featured_description_max = 50;
	
	foreach (explode(":", FEATURED_PERSONAS) as $id)
	{
		$persona = $db->get_persona_by_id($id); 
		if (!$persona)
			continue;

		$persona['json'] = htmlentities(json_encode(extract_record_data($persona)));
		$persona['detail_url'] = "/gallery/persona/" . url_prefix($persona['id']);
		$persona['preview_image'] = PERSONAS_LIVE_PREFIX . '/' . url_prefix($persona['id']);

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
                            <p class="daily-users"><strong><?= number_format($persona['popularity']) ?></strong> active daily users</p>
                            <hr />

                        </li>
<?php
				}
?>
                    </ul>
                    
                </div>
            </div>
