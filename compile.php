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
	
	require_once 'server/lib/personas_constants.php';	
	require_once 'server/lib/personas_functions.php';	
	require_once 'server/lib/storage.php';

	$page_size = 42;
	
	$db = new PersonaStorage();
	$categories = $db->get_categories();

	
	function get_directory_html($path, $file)
	{
		if (!is_dir(PERSONAS_STORAGE_PREFIX . "/gallery/$path"))
		{
			mkdir(PERSONAS_STORAGE_PREFIX . "/gallery/$path");
		}

		$ch = curl_init();
		$fp = fopen(PERSONAS_STORAGE_PREFIX . "/gallery/$path/$file", "w");	
		curl_setopt($ch, CURLOPT_URL, "http://localhost/store/dynamic/gallery/$path/$file?no_my=1");
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_exec($ch);
		fclose($fp);	
	}

	function make_directory_path($file)
	{
		$file = substr($file, 1); #remove leading slash
		$components = explode("/", $file);
		array_pop($components); #remove filename
		$path = "";
		foreach ($components as $dir)
		{
			$path .= "/" . $dir;
			if (!is_dir($path))
				mkdir($path);
		}
	}
	
	function store_page($url, $filename)
	{
		make_directory_path($filename);
		$fp = fopen($filename, "w");

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_exec($ch);
		fclose($fp);		
	}

	#Top level popular page
	$popular_list = $db->get_popular_personas(null, 21);
	foreach ($popular_list as $item)
	{
		$data = extract_record_data($item);
		$data['recent'] = (time() - strtotime($item['approve']) < 604800) ? true : false;
		$popular_json[] = $data;
	}
	$master['popular'] = $popular_json;

	#Top level recent page
	$recent_list = $db->get_recent_personas(null, 21);
	foreach ($recent_list as $item)
	{
		$data = extract_record_data($item);
		$data['recent'] = (time() - strtotime($item['approve']) < 604800) ? true : false;
		$recent_json[] = $data;
	}
	$master['recent'] = $recent_json;


	foreach ($categories as $category)
	{
		#get category counts for pagination
		$category_total = $db->get_active_persona_count($category);
		$pages = floor($category_total/$page_size) + 1;
		
		$popular_list = $db->get_popular_personas($category, 10);
	
		$count = 0;
		$short_cat_list = array();
		foreach ($popular_list as $item)
		{
			$data = extract_record_data($item);
			$data['recent'] = (time() - strtotime($item['approve']) < 604800) ? true : false;
			$short_cat_list[] = $data;
		}
		$category_array[] = array('name' => $category, 'personas' => $short_cat_list);

		#get the html
		#the popular page
		store_page("http://localhost/gallery/$category/Popular?no_my", PERSONAS_STORAGE_PREFIX . "/gallery/$category/Popular");
	
		#the recent page
		store_page("http://localhost/gallery/$category/Recent?no_my", PERSONAS_STORAGE_PREFIX . "/gallery/$category/Recent");

		$i = 1;
		while ($i <= $pages)
		{
			store_page("http://localhost/gallery/$category/All/$i?no_my", PERSONAS_STORAGE_PREFIX . "/gallery/$category/All/$i");
			$i++;
		}		
	}
	$master['categories'] = $category_array;

	file_put_contents(PERSONAS_STORAGE_PREFIX . '/index_1.json', json_encode($master));

	#now write out the individual pages
	$master_list = $db->get_active_persona_ids();
	foreach ($master_list as $id)
	{
		$path = get_persona_path(PERSONAS_STORAGE_PREFIX . "/gallery/persona", $id);
		store_page("http://localhost/persona/$id", $path . "/" . ($id < 10 ? '0' : '') .  $id);
	}
		
	#and the index
	store_page("http://localhost/index.html?no_my=1", PERSONAS_STORAGE_PREFIX . "/index.html");

	#the all page
	store_page("http://localhost/gallery/All/All?no_my", PERSONAS_STORAGE_PREFIX . "/gallery/All/All");

	#the popular page
	store_page("http://localhost/gallery/All/Popular?no_my", PERSONAS_STORAGE_PREFIX . "/gallery/All/Popular");

	#the recent page
	store_page("http://localhost/gallery/All/Recent?no_my", PERSONAS_STORAGE_PREFIX . "/gallery/All/Recent");

	#featured designers
	$featured_persona = $db->get_persona_by_id(FEATURE_DESIGNER_PERSONA_ID);
	store_page("http://localhost/gallery/Designer/" . $featured_persona['author'] . "?no_my=1", PERSONAS_STORAGE_PREFIX . "/featured");
	
	#update page
	store_page("http://localhost/updated?no_my=1", PERSONAS_STORAGE_PREFIX . "/updated.html");

	#firstrun page
	store_page("http://localhost/firstrun?no_my=1", PERSONAS_STORAGE_PREFIX . "/firstrun.html");

	#recent page hack to provide a backwards compatible recent.html
	store_page("http://localhost/gallery/All/Recent?no_my=1", PERSONAS_STORAGE_PREFIX . "/recent.html");

	#faq
	store_page("http://localhost/faq?no_my=1", PERSONAS_STORAGE_PREFIX . "/faq.html");

	#demo_install
	store_page("http://localhost/demo_install?no_my=1", PERSONAS_STORAGE_PREFIX . "/demo_install.html");

	#demo_create
	store_page("http://localhost/demo_create?no_my=1", PERSONAS_STORAGE_PREFIX . "/demo_create.html");

	#privacy
	store_page("http://localhost/privacy?no_my=1", PERSONAS_STORAGE_PREFIX . "/privacy.html");
	
?>