            <div class="feature">
                 <h3>Featured Designer</h3>
<?php
	$personas = explode(":", FEATURED_DESIGNERS); 
	$persona = $personas[0];
	$persona_json = htmlentities(json_encode(extract_record_data($persona)));
	$detail_url = "/gallery/persona/" . url_prefix($persona['id']);
?>
					<img class="preview persona" src="<?= PERSONAS_LIVE_PREFIX . '/' . url_prefix($persona['id']) ?>/preview_featured.jpg" persona="<?= $persona_json ?>">
                    <h4><?= $persona['author'] ?></h4>
                    <p class="try"><a href="/featured">view more »</a></p>
            </div>

<?php

if (0) { #new version not launching this rev

?>



<?php
	$featured = explode(":", FEATURED_DESIGNERS); 
?>
<div class="feature slideshow">
                <h3>Featured Designers</h3>
                <ul id="slideshow-nav">
<?php
				for ($i = 1; $i <= count($featured); $i++)
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
	$description_max = 50;
	foreach ($featured as $id)
	{
		$persona = $db->get_persona_by_id($id); 

		$persona_json = htmlentities(json_encode(extract_record_data($persona)));
		$detail_url = "/gallery/persona/" . url_prefix($persona['id']);
?>
                        <li>
                            <img class="preview persona" src="<?= PERSONAS_LIVE_PREFIX . '/' . url_prefix($persona['id']) ?>/preview_featured.jpg" persona="<?= $persona_json ?>">
                            <h4><a href="/gallery/Designer/<?= $persona['author'] ?>"><?= $persona['author'] ?></a></h4>
                            <p class="try"><a href="<?= $detail_url ?>">view designer »</a></p>
                            <hr />
                        </li>
<?php
	}
?>
                    </ul>
                    
                </div>
            </div>

<?php } ?>