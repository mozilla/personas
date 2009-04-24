<?php
	$personas = explode(":", FEATURED_DESIGNERS); 
	$persona = $db->get_persona_by_id($personas[0]); 
	$persona_json = htmlentities(json_encode(extract_record_data($persona)));
?>
            <div class="feature">
                 <h3>Featured Designer</h3>
				<a href="/gallery/Designer/<?= $persona['author'] ?>"><img class="preview persona" src="<?= PERSONAS_LIVE_PREFIX . '/' . url_prefix($persona['id']) ?>/preview_featured.jpg" persona="<?= $persona_json ?>"></a>
				<h4><a href="/gallery/Designer/<?= $persona['author'] ?>"><?= $persona['author'] ?></a></h4>
				<p class="try"><a href="/gallery/Designer/<?= $persona['author'] ?>">view designer »</a></p>
            </div>

<?php

if (0) { #new version not launching this rev

?>



<?php
	foreach (explode(":", FEATURED_PERSONAS) as $id)
	{
		$persona = $db->get_persona_by_id($id); 
		if (!$persona)
			continue;
		$persona['json'] = htmlentities(json_encode(extract_record_data($persona)));

		$personas[] = $persona; 
	}
?>
			<div class="feature slideshow">
                <h3>Featured Designers</h3>
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
                            <img class="preview persona" src="<?= PERSONAS_LIVE_PREFIX . '/' . url_prefix($persona['id']) ?>/preview_featured.jpg" persona="<?= $persona['json'] ?>">
                            <h4><a href="/gallery/Designer/<?= $persona['author'] ?>"><?= $persona['author'] ?></a></h4>
                            <p class="try"><a href="/gallery/Designer/<?= $persona['author'] ?>">view designer »</a></p>
                            <hr />
                        </li>
<?php
					}
?>
                    </ul>
                </div>
            </div>

<?php } ?>