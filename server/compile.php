<?php
	require_once '/Users/telliott/mozilla/personas/upload/storage.inc';
	
	$PERSONAS_STORAGE_PREFIX = '/Users/telliott/mozilla/personas/store';

	$db = new PersonaStorage('/Library/Webserver/dbs/personas');
	$categories = $db->get_categories();

	function extract_data($item)
	{
		$PERSONAS_URL_PREFIX = '/personas/store';
		$padded_id = $item{'id'} < 10 ? '0' . $item{'id'} : $item{'id'};
		$url_prefix = $PERSONAS_URL_PREFIX . '/' . $padded_id[0] . '/' . $padded_id[1] .  '/'. $item{'id'} . '/';
		$extracted = array('id' => $item{'id'}, 
		                'name' => $item{'name'},
						'header' => $url_prefix . $item{'header'}, 
						'footer' => $url_prefix . $item{'footer'});
		return $extracted;	
	}
	
	function extract_html($item)
	{
		$PERSONAS_URL_PREFIX = '/personas/store';
		$second_folder = $item{'id'}%10;
		$first_folder = ($item{'id'}%100 - $second_folder)/10;
		$url_prefix = $PERSONAS_URL_PREFIX . '/' . $first_folder . '/' . $second_folder .  '/'. $item{'id'} . '/';

		$extracted = "<div class=\"persona\">
		<div class=\"name\">" . $item{'name'} . "</div>";
		$extracted .= "<div class=\"preview\"><img src=\"" . $url_prefix . "preview.jpg\" ></div>";
		if ($item{'author'})
		{
			$extracted .= "<div class=\"creator\">Creator: " . $item{'author'} . "</div>";
		}
		$extracted .= "</div>\n";

		return $extracted;	
	}
	
	$master_array = array();
	
	#recompile popular general
	$popular_list = $db->get_popular_personas(null, 20);
	$json = array();
	$popular_html_top = "";
	foreach ($popular_list as $item)
	{
		$json[] = extract_data($item);
		$popular_html_top .= extract_html($item);
	}
	$master_array["popular"] = $json;
	file_put_contents($PERSONAS_STORAGE_PREFIX . '/popular.json', json_encode($json));
	file_put_contents($PERSONAS_STORAGE_PREFIX . '/popular.html', build_file('popular', '', $popular_html_top));
	
	#recompile recent general
	$recent_list = $db->get_recent_personas(null, 20);
	$json = array();
	$recent_html_top = "";
	foreach ($recent_list as $item)
	{
		$json[] = extract_data($item);
		$recent_html_top .= extract_html($item);
	}
	$master_array["recent"] = $json;
	file_put_contents($PERSONAS_STORAGE_PREFIX . '/recent.json', json_encode($json));
	file_put_contents($PERSONAS_STORAGE_PREFIX . '/recent.html', build_file('recent', '', $recent_html_top));


	$master_array['categories'] = array();
	#new popular & recent file for each category.
	foreach ($categories as $category)
	{		
		$storage_path = $PERSONAS_STORAGE_PREFIX . '/' . preg_replace('/ /', '_', $category);
		if (!is_dir($storage_path))
		{
			mkdir($storage_path);
		}
		
		#rebuild popular for category
		$popular_list = $db->get_popular_personas($category);
		$popular_json = array();
		$popular_html_top = "";
		$popular_html_all = "";
		$count = 0;
		foreach ($popular_list as $item)
		{
			$count++;
			if ($count <= 10)
			{
				$popular_json[] = extract_data($item);
			}
			if ($count <= 20)
			{
				$popular_html_top .= extract_html($item);
			}
			$popular_html_all .= extract_html($item);
		}
		file_put_contents($storage_path . '/popular.json', json_encode($popular_json));
		file_put_contents($storage_path . '/popular.html', build_file('popular', $category, $popular_html_top));
		file_put_contents($storage_path . '/all.html', build_file('all', $category, $popular_html_all));


		#rebuild recent for category
		$recent_list = $db->get_recent_personas($category);
		$recent_html_top = "";
		$recent_html_all = "";
		$recent_json = array();
		$count = 0;
		foreach ($recent_list as $item)
		{
			$count++;
			if ($count <= 10)
			{
				$recent_json[] = extract_data($item);
			}
			if ($count <= 20)
			{
				$recent_html_top .= extract_html($item);
			}
			$recent_html_all .= extract_html($item);
		}
		file_put_contents($storage_path . '/recent.json', json_encode($recent_json));
		file_put_contents($storage_path . '/recent.html', build_file('recent', $category, $recent_html_top));
		$master_array['categories'][$category]  = array('popular' => $popular_json, 'recent' => $recent_json);
	}
	
	file_put_contents($PERSONAS_STORAGE_PREFIX . '/index.json', json_encode($master_array));
	
	
	function build_file($type, $category, $contents)
	{
		$string = "";
		$string .= css();
		$string .= "<body>";
	    $string .= "<div id=\"header\">";
		$string .= "<div><img src=\"http://labs.mozilla.com/projects/personas/images/personas-logo-full.png\" width=175></div>";
		$string .= "<h1>" . ucfirst($type) . " Personas";
		if ($category)
		{
			$string .= " for $category";
		}
		$string .= "</h1></div>";
		$string .= build_sidelist($type, $category);
		$string .= "<div id=\"contents\">$contents</div>\n";
		$string .= "</body>";
		return $string;
	}
	
	
	function build_sidelist($type, $category)
	{
		global $categories;
		$string = "<div id=\"menu\"><ul>\n";
		$relative = "";
		
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
		$string .= "</ul></div>\n";
		return $string;
	
	}
	
	function css()
	{
		$string = '<html lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Personas</title>
    <link rel="stylesheet" type="text/css" href="/personas/store/personas.css" />
    </head>';
		return $string;
	}
	
	
	
?>