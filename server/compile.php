<?php
	require_once 'personas_libs/personas_constants.php';	
	require_once 'personas_libs/storage.php';

	$db = new PersonaStorage();
	$categories = $db->get_categories();

	function url_prefix($id)
	{
		$second_folder = $id%10;
		$first_folder = ($id%100 - $second_folder)/10;
		return PERSONAS_URL_PREFIX . '/' . $first_folder . '/' . $second_folder .  '/'. $id . '/';
	}

	function extract_record_data($item)
	{
		$padded_id = $item{'id'} < 10 ? '0' . $item{'id'} : $item{'id'};
		$extracted = array('id' => $item{'id'}, 
						'name' => $item{'name'},
						'accentcolor' => $item{'accentcolor'} ? $item{'accentcolor'} : null,
						'textcolor' => $item{'textcolor'} ? $item{'textcolor'} : null,
						'header' => url_prefix($item{'id'}) . $item{'header'}, 
						'footer' => url_prefix($item{'id'}) . $item{'footer'});
		return $extracted;	
	}
	
	
	$popular_list = $db->get_popular_personas(null, 20);
	foreach ($popular_list as $item)
	{
		$popular_json[] = extract_record_data($item);
	}
	$master['popular'] = $popular_json;
	file_put_contents(PERSONAS_STORAGE_PREFIX . '/popular.html', html_page('popular', '', $popular_list));


	$recent_list = $db->get_recent_personas(null, 20);
	foreach ($recent_list as $item)
	{
		$recent_json[] = extract_record_data($item);
	}
	$master['recent'] = $recent_json;
	file_put_contents(PERSONAS_STORAGE_PREFIX . '/recent.html', html_page('recent', '', $recent_list));
	

	foreach ($categories as $category)
	{
		$storage_path = PERSONAS_STORAGE_PREFIX . '/' . preg_replace('/ /', '_', $category);
		if (!is_dir($storage_path))
		{
			mkdir($storage_path);
		}

		$popular_list = $db->get_popular_personas($category);
	
		$count = 0;
		$short_cat_list = array();
		$med_cat_list = array();
		$long_cat_list = array();
		foreach ($popular_list as $item)
		{
			$count++;
			if ($count <= 10)
			{
				$data = extract_record_data($item);
				$data['recent'] = (time() - strtotime($item['approve']) < 604800) ? true : false;
				$short_cat_list[] = $data;
			}
			if ($count <= 20)
			{
				$med_cat_list[] = $item;
			}
			$long_cat_list[] = $item;
		}
		$category_array[] = array('name' => $category, 'personas' => $short_cat_list);
		file_put_contents($storage_path . '/popular.html', html_page('popular', $category, $med_cat_list));
		file_put_contents($storage_path . '/all.html', html_page('all', $category, $long_cat_list));
		
		
		
		$recent_list = $db->get_recent_personas($category, 20);
		file_put_contents($storage_path . '/recent.html', html_page('recent', $category, $recent_list));
	}
	$master['categories'] = $category_array;

	file_put_contents(PERSONAS_STORAGE_PREFIX . '/index.json', json_encode($master));



	function html_menu($type, $category)
	{
		global $categories;
		$relative = "";
		$string = "<ul>";
		if ($category == "")
		{
			$string .= "<li><h2>All Personas >></h2><ul>";
		}
		else
		{
			if ($type == 'all')
			{
				$string .= "<li><a href=\"../recent.html\">All Personas</a><ul>";
			}
			else
			{
				$string .= "<li><a href=\"../$type.html\">All Personas</a><ul>";
			}
			$relative = "../";
		}
		foreach (array("recent", "popular") as $entry)
		{
			$string .= "<li><a href=\"$relative$entry.html\">$entry</a></li>";
		}
		$string .= "</ul></li>\n";
		
		foreach ($categories as $item)
		{
			if ($category == $item)
			{
				$string .= "<li><h2>$item >></h2><ul>";
			}
			else
			{
				$string .= "<li><a href=\"$relative" . preg_replace('/ /', '_', $item) . "/$type.html\">$item</a><ul>";
			}

			foreach (array("recent", "popular", "all") as $entry)
			{
				$string .= "<li><a href=\"$relative" . preg_replace('/ /', '_', $item) . "/$entry.html\">$entry</a></li>";
			}
			$string .= "</ul></li>\n";
		}
		$string .= "</ul>\n";
		return $string;
	}
	
	function html_box($item)
	{
		$preview_url = url_prefix($item{'id'}) . "preview.jpg";
		
		$text = <<< End_of_block
		<div class="persona">
			<div class="name">${item['name']}</div>
			<div class="preview">
				<img closs="preview-image"
					 onclick="dispatchPersonaEvent('SelectPersona', this)" 
					 onmouseover="dispatchPersonaEvent('PreviewPersona', this)" 
					 onmouseout="dispatchPersonaEvent('ResetPersona', this)"
					 persona="${item['id']}"
					 header="${item['header']}"
					 footer="${item['footer']}"
					 textcolor="${item['textcolor']}"
					 accentcolor="${item['accentcolor']}"
					 src="$preview_url">
			</div>
			<div class="creator">Creator: ${item['author']}</div>
		</div>			
End_of_block;
		return $text;
	}

	function html_page($type, $category, $boxes)
	{
		$uctype = ucfirst($type);
		$forcat = $category ? "for $category" : "";
		$sidelist = html_menu($type, $category);
		
		$text = <<< End_of_block
		<html lang="en">
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
				<title>Personas</title>
				<link rel="stylesheet" type="text/css" href="/personas/store/personas.css" />
				<script language="JavaScript">
					function dispatchPersonaEvent(aType, aNode) 
					{
						var event = document.createEvent("Events");
						event.initEvent(aType, true, false);
						aNode.dispatchEvent(event);
					}
				</script>
			</head>
			<body>
			<div id="header">
				<div><img src="http://labs.mozilla.com/projects/personas/images/personas-logo-full.png" width=175></div>
				<h1>$uctype Personas $forcat</h1>
			</div>
			<div id="menu">
				$sidelist
			</div>
End_of_block;
			$text .= '<div id="contents">';
			foreach ($boxes as $contents)
			{
				$text .= html_box($contents);
			}
			$text .= '</div></body>';
			return $text;
	}

?>