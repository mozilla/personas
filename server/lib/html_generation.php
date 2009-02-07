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



require_once 'personas_constants.php';

function extract_record_data($item)
{
	$padded_id = $item{'id'} < 10 ? '0' . $item{'id'} : $item{'id'};
	$extracted = array('id' => $item{'id'}, 
					'name' => $item{'name'},
					'accentcolor' => $item{'accentcolor'} ? '#' . $item{'accentcolor'} : null,
					'textcolor' => $item{'textcolor'} ? '#' . $item{'textcolor'} : null,
					'header' => url_prefix($item{'id'}) . $item{'header'}, 
					'footer' => url_prefix($item{'id'}) . $item{'footer'});
	return $extracted;	
}
	
function url_prefix($id)
{
	$second_folder = $id%10;
	$first_folder = ($id%100 - $second_folder)/10;
	return  $first_folder . '/' . $second_folder .  '/'. $id . '/';
}

function html_menu($type, $category, $action = null)
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
			$url = $action ? $action : "../recent.html";
			$string .= "<li><a href=\"$url\">All Personas</a><ul>";
		}
		else
		{
			$url = $action ? $action : "../$type.html";
			$string .= "<li><a href=\"$url\">All Personas</a><ul>";
		}
		$relative = "../";
	}
	foreach (array("recent", "popular") as $entry)
	{
		$url = $action ? $action . "?type=$entry" : "$relative$entry.html";
		$string .= "<li><a href=\"$url\">$entry</a></li>";
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
			$item_string = preg_replace('/ /', '_', $item);
			$url = $action ? $action . "?category=$item_string&type=$type" : ($relative . $item_string . "/$type.html");
			$string .= "<li><a href=\"$url\">$item</a><ul>";
		}

		foreach (array("recent", "popular", "all") as $entry)
		{
			$item_string = preg_replace('/ /', '_', $item);
			$url = $action ? $action . "?category=$item_string&type=$entry" : ($relative . $item_string . "/$entry.html");
			$string .= "<li><a href=\"$url\">$entry</a></li>";
		}
		$string .= "</ul></li>\n";
	}
	$string .= "</ul>\n";
	return $string;
}

function html_box($item, $is_author = null, $is_admin = null)
{
	$preview_url = PERSONAS_LIVE_PREFIX . '/' . url_prefix($item['id']) . "preview.jpg";
	$persona = htmlentities(json_encode(extract_record_data($item)));
	$text = <<< End_of_block
	<div class="persona">
		<div class="name">${item['name']}</div>
		<div class="preview">
			<img class="preview-image"
				 persona="$persona"
				 src="$preview_url">
		</div>
End_of_block;
	if ($is_author || $is_admin)
	{
		$text .= "<div class=\"creator\"><a href=\"https://personas.services.mozilla.com/upload/submit.php?edit_id=${item['id']}\" target=\"_blank\">Edit</a>";
		if ($is_admin)
		{
			$text .= " | <a href=\"https://personas.services.mozilla.com/admin/pull.php?id=${item['id']}\" target=\"_blank\" onClick=\"return confirm('Confirm Deletion');\">Pull</a>";
		}
		$text .= "</div>";
	}
	else
	{
#		$text .= "<div class=\"creator\">Creator: ${item['author']}</div>";
	}
	$text .= '</div>';		
	return $text;
}

function html_page($type, $category, $boxes, $action = null, $title = null, $is_author = null, $is_admin = null)
{
	$uctype = ucfirst($type);
	$forcat = $category ? "for $category" : "";
	$sidelist = html_menu($type, $category,$action);
	if (!$title) { $title = "$uctype Personas $forcat"; }
	
	$text = <<< End_of_block
	<html lang="en">
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
			<title>Personas</title>
			<link rel="stylesheet" type="text/css" href="/store/css/personas.css" />
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
			<div><img src="/store/images/icon_firefox-personas_S.gif"></div>
			<h1>$title</h1>
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
			$text .= html_box($contents, $is_author, $is_admin);
		}
		$text .= '</div></body></html>';
		return $text;
}
?>