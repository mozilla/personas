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
# The Original Code is Personas Server
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
	require_once 'lib/personas_functions.php';	
	require_once 'lib/storage.php';
	require_once 'lib/user.php';
	
	$db = new PersonaStorage();
	$user = new PersonaUser();
	$user->authenticate();
	$showWearThis = false;
	$showDescription = true;
	
	$description_max = 50; #truncated description size
	$url_prefix = '/gallery'; #telling the templates the gallery root
	$title = "Gallery"; #page title for the header template
	$no_my = array_key_exists('no_my', $_GET) ? 1 : 0; #whether to display all the dynamic stuff
	$display_username = '';
	
	$categories = $db->get_categories();
	array_unshift($categories, 'All');
	$tabs = array('Popular', 'Recent', 'All', 'My', 'Favorites');
	
	$path = array_key_exists('PATH_INFO', $_SERVER) ? $_SERVER['PATH_INFO'] : '/';
	$path = substr($path, 1); #chop the lead slash
	list($category, $tab, $page) = explode('/', $path.'//');

	$category = urldecode(ucfirst($category));
		
	$page_header = "View Personas";

	$list = array(); #grab the appropriate personas for display
	
	#Designer reverses the semantics of the tab path. Everything else has the base directive 
	#down at the lowest level, but Designer is a level up to have the designer name make sense
	if ($category == 'Designer')
	{
		$display_username = $user->get_display_username($tab);
		if ($tab) #tab is actually the author here
		{
			$list = $db->get_persona_by_author($tab); 
			$title = $page_header = "Personas by " . $display_username;
			$showWearThis = true;
			$showDescription = false;
		}
	}
	else
	{
		if (!in_array($category, $categories))
			$category = 'All';

		switch(strtolower($tab))
		{
			case 'all':
				$page = is_numeric($page) && $page > 0 ? $page : 1;
				$list = $db->get_all_personas($category == 'All' ? null : $category, $page - 1);
				break;
			case 'recent':
				$list = $db->get_recent_personas($category == 'All' ? null : $category);
				break;
			case 'my':
				$user->force_signin();
				$title = $page_header = "My Personas";
				if ($user->get_username())
					$list = $db->get_persona_by_author($user->get_username(), $category == 'All' ? null : $category);	
				break;
			case 'favorites':
				$user->force_signin();
				$title = $page_header = "My Favorite Personas";
				if ($user->get_username())
					$list = $db->get_user_favorites($user->get_username(), $category);
				break;
			case 'search':
				$title = $page_header = 'Search Personas';
				if (array_key_exists('p', $_GET) && $_GET['p'])
				{
					$title = $page_header = "Personas Search Results for " . htmlspecialchars($_GET['p']);
					$list = $db->search_personas($_GET['p'], $category, PERSONA_GALLERY_PAGE_SIZE);
				}
				break;
			default: #should default to popular
				$tab = 'Popular';
				$list = $db->get_popular_personas($category == 'All' ? null : $category);
				break;
		}
	}

	if (array_key_exists('rss', $_GET))
	{
		header("Content-Type: application/xml"); 
		$types = array('png' => 'application/png', 'jpg' => 'application/jpg');
		foreach ($list as &$persona)
		{
			$persona['header_url'] = 'http://www.getpersonas.com' . PERSONAS_LIVE_PREFIX . '/' . url_prefix($persona['id']) . '/' . $persona['header'];
			$persona['preview_url'] ='http://www.getpersonas.com' .  PERSONAS_LIVE_PREFIX . '/' . url_prefix($persona['id']) . '/' . "preview.jpg";
			$persona['media_type'] = $types[substr($persona['header'], -3)];
		}
		include 'templates/gallery_rss.php';
	}
	elseif (array_key_exists('json', $_GET))
	{
		header("Content-Type: application/json"); 
		echo json_encode(array_map("extract_record_data", $list));
	}
	else
	{
		foreach ($list as &$persona)
		{
			$persona['preview_url'] = PERSONAS_LIVE_PREFIX . '/' . url_prefix($persona['id']) . '/' . "preview.jpg";
			$persona['json'] = htmlentities(json_encode(extract_record_data($persona)));
			$persona['date'] = date("n/j/Y", strtotime($persona['approve']));
			$persona['short_description'] = $persona['description'];
			if (strlen($persona['short_description']) > $description_max)
			{
				$persona['short_description'] = substr($persona['short_description'], 0, $description_max);
				$persona['short_description'] = preg_replace('/ [^ ]+$/', '', $persona['short_description']) . '...';
			}
		}
		include 'templates/gallery.php';
	}

?>
