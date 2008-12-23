<?php

# ***** BEGIN LICENSE BLOCK *****
# Version: MPL 1.1/GPL 2.0/LGPL 2.1
#
# The contents of this file are subject to the Mozilla Public License Version
# 1.1 (the "License"); you may not use this file except in compliance with
# the License. You may obtain a copy of the License at
# http://www.mozilla.org/MPL/
#
# Software distributed under the License is distributed on an "AS IS" basis,
# WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
# for the specific language governing rights and limitations under the
# License.
#
# The Original Code is Weave Basic Object Server
#
# The Initial Developer of the Original Code is
# Mozilla Labs.
# Portions created by the Initial Developer are Copyright (C) 2008
# the Initial Developer. All Rights Reserved.
#
# Contributor(s):
#	Toby Elliott (telliott@mozilla.com)
#
# Alternatively, the contents of this file may be used under the terms of
# either the GNU General Public License Version 2 or later (the "GPL"), or
# the GNU Lesser General Public License Version 2.1 or later (the "LGPL"),
# in which case the provisions of the GPL or the LGPL are applicable instead
# of those above. If you wish to allow use of your version of this file only
# under the terms of either the GPL or the LGPL, and not to allow others to
# use your version of this file under the terms of the MPL, indicate your
# decision by deleting the provisions above and replace them with the notice
# and other provisions required by the GPL or the LGPL. If you do not delete
# the provisions above, a recipient may use your version of this file under
# the terms of any one of the MPL, the GPL or the LGPL.
#
# ***** END LICENSE BLOCK *****
	
	require_once 'lib/personas_constants.php';	
	require_once 'lib/storage.php';

	$db = new PersonaStorage();
	$categories = $db->get_categories();

	function url_prefix($id)
	{
		$second_folder = $id%10;
		$first_folder = ($id%100 - $second_folder)/10;
		return  $first_folder . '/' . $second_folder .  '/'. $id . '/';
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

	file_put_contents(PERSONAS_STORAGE_PREFIX . '/index_1.json', json_encode($master));



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
		$preview_url = PERSONAS_LIVE_PREFIX . '/' . url_prefix($item{'id'}) . "preview.jpg";
		$persona = htmlentities(json_encode(extract_record_data($item)));
		$text = <<< End_of_block
		<div class="persona">
			<div class="name">${item['name']}</div>
			<div class="preview">
				<img class="preview-image"
					 persona="$persona"
					 src="$preview_url">
			</div>
		</div>			
End_of_block;
#this goes back in between those two divs once we have real creator info
#			<div class="creator">Creator: ${item['author']}</div>
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
				<link rel="stylesheet" type="text/css" href="/personas/store/css/personas.css" />
				<script language="JavaScript">
					function dispatchPersonaEvent(aType, aNode) 
					{
						if (!aNode.hasAttribute("persona"))
							return;
						var event = document.createEvent("Events");
						event.initEvent(aType, true, false);
						aNode.dispatchEvent(event);
					}
				</script>
			</head>
			<body>
			<div id="header">
				<div><img src="/personas/images/personas-logo.png" width=175></div>
				<h1>$uctype Personas $forcat</h1>
			</div>
			<div id="menu">
				$sidelist
			</div>
End_of_block;
			$text .= <<< End_of_block
			<div id="contents" onclick="dispatchPersonaEvent('SelectPersona', event.originalTarget)"
              onmouseover="dispatchPersonaEvent('PreviewPersona', event.originalTarget)"
              onmouseout="dispatchPersonaEvent('ResetPersona', event.originalTarget)">
End_of_block;

			foreach ($boxes as $contents)
			{
				$text .= html_box($contents);
			}
			$text .= '</div></body></html>';
			return $text;
	}

?>