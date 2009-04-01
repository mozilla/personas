            <div class="feature">
                 <h3>Featured Designer</h3>
<?php
	$persona = $db->get_persona_by_id(FEATURE_DESIGNER_PERSONA_ID); 
	$persona_json = htmlentities(json_encode(extract_record_data($persona)));
	$detail_url = "/store/gallery/persona/" . url_prefix($persona['id']);
?>
					<img class="preview persona" src="<?= PERSONAS_LIVE_PREFIX . '/' . url_prefix($persona['id']) ?>/preview_featured.jpg" persona="<?= $persona_json ?>">
                    <h4><?= $persona['author'] ?></h4>
                    <p class="try"><a href="/store/featured">view more »</a></p>
            </div>
