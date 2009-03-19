<?php 
	require_once 'personas_constants.php';	
	require_once 'storage.php';
	
	function url_prefix($id)
	{
		$second_folder = $id%10;
		$first_folder = ($id%100 - $second_folder)/10;
		return  $first_folder . '/' . $second_folder .  '/'. $id ;
	}

	function extract_record_data($item)
	{
		$padded_id = $item['id'] < 10 ? '0' . $item['id'] : $item['id'];
		$extracted = array('id' => $item['id'], 
						'name' => $item['name'],
						'accentcolor' => $item['accentcolor'] ? '#' . $item['accentcolor'] : null,
						'textcolor' => $item['textcolor'] ? '#' . $item['textcolor'] : null,
						'header' => url_prefix($item['id']) . '/' . $item['header'], 
						'footer' => url_prefix($item['id']) . '/' . $item['footer']);
		return $extracted;	
	}


	function make_persona_storage_path($persona_id)
	{
		$persona_path = make_persona_path(PERSONAS_STORAGE_PREFIX, $persona_id);
		$persona_path .= "/" . $persona_id;
		if (!is_dir($persona_path)) { mkdir($persona_path); }
		error_log("storage - " . $persona_path);
		return $persona_path;
	}
	
	function make_persona_pending_path($persona_id)
	{
		$persona_path = make_persona_path(PERSONAS_PENDING_PREFIX, $persona_id);
		$persona_path .= "/" . $persona_id;
		if (!is_dir($persona_path)) { mkdir($persona_path); }
		error_log("pending - " . $persona_path);
		return $persona_path;
	}
	
	function make_persona_detail_path($persona_id)
	{
		$persona_path = PERSONAS_STORAGE_PREFIX . '/store/gallery';
		if (!is_dir($persona_path)) { mkdir($persona_path); }
		$persona_path .= '/persona';
		if (!is_dir($persona_path)) { mkdir($persona_path); }
		$persona_path = make_persona_path($persona_path, $persona_id);
		error_log("detail - " . $persona_path);
		return $persona_path;
	}

	function make_persona_path($base, $persona_id)
	{
		$second_folder = $persona_id%10;
		$first_folder = ($persona_id%100 - $second_folder)/10;

		$base = preg_replace('/\/$/', '', $base);
		$persona_path = $base . '/' . $first_folder;
		if (!is_dir($persona_path)) { mkdir($persona_path); }
		$persona_path .= "/" . $second_folder;
		if (!is_dir($persona_path)) { mkdir($persona_path); }
		return $persona_path;
		
	}
	
	function build_persona_files($persona_path, $persona)
	{
		$imgcommand = "convert " . $persona_path . "/" . $persona['header'] . " -gravity NorthEast -crop 600x200+0+0  -scale 200x100 " . $persona_path . "/preview.jpg";
		exec($imgcommand);
		$imgcommand2 = "convert " . $persona_path . "/" . $persona['header'] . " -gravity NorthEast -crop 1360x200+0+0 -scale 680x100 " . $persona_path . "/preview_large.jpg";
		exec($imgcommand2);
		$imgcommand3 = "convert " . $persona_path . "/" . $persona['header'] . " -gravity NorthEast -crop 320x220+0+0  -scale 64x44 " . $persona_path . "/preview_popular.jpg";
		exec($imgcommand3);
		$imgcommand4 = "convert " . $persona_path . "/" . $persona['header'] . " -gravity NorthEast -crop 592x200+0+0  -scale 296x106 " . $persona_path . "/preview_featured.jpg";
		exec($imgcommand4);

		file_put_contents($persona_path . '/index_1.json', json_encode(extract_record_data($persona)));
	}

?>