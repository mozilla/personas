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

	function make_persona_pending_path($persona_id)
	{
		$second_folder = $persona_id%10;
		$first_folder = ($persona_id%100 - $second_folder)/10;

		$persona_path = PERSONAS_PENDING_PREFIX . "/" . $first_folder;
		if (!is_dir($persona_path)) { mkdir($persona_path); }
		$persona_path .= "/" . $second_folder;
		if (!is_dir($persona_path)) { mkdir($persona_path); }
		$persona_path .= "/" . $persona_id;
		if (!is_dir($persona_path)) { mkdir($persona_path); }
		return $persona_path;
	}

	function make_persona_storage_path($persona_id)
	{
		$second_folder = $persona_id%10;
		$first_folder = ($persona_id%100 - $second_folder)/10;

		$persona_path = PERSONAS_STORAGE_PREFIX . "/gallery/persona/" . $first_folder;
		if (!is_dir($persona_path)) { mkdir($persona_path); }
		$persona_path .= "/" . $second_folder;
		if (!is_dir($persona_path)) { mkdir($persona_path); }
		return $persona_path;
	}
	

?>