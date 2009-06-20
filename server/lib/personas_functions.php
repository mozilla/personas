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
	require_once 'storage.php';
	

#####
# All persona data is stored in directories corresponding to the last two digits of their 
# id. This gives us a well-distributed file tree without too many items in the same folder
# (which can cause problems if we ever need to do command-line manipulation). url_prefix just
# gives us a fast way to generate the end of that path.


	function url_prefix($id)
	{
		$second_folder = $id%10;
		$first_folder = ($id%100 - $second_folder)/10;
		return  $first_folder . '/' . $second_folder .  '/'. $id ;
	}


#####
# Takes the data from a persona db record and just extracts the parts that are needed for the 
# json object that the client needs.
# $url_root allows hosting of the personas elsewhere, but isn't really used much.


	function extract_record_data($item, $url_root = null)
	{
		$padded_id = $item['id'] < 10 ? '0' . $item['id'] : $item['id'];
		$extracted = array('id' => $item['id'], 
						'name' => $item['name'],
						'accentcolor' => $item['accentcolor'] ? '#' . $item['accentcolor'] : null,
						'textcolor' => $item['textcolor'] ? '#' . $item['textcolor'] : null,
						'header' => $url_root . url_prefix($item['id']) . '/' . $item['header'], 
						'footer' => $url_root . url_prefix($item['id']) . '/' . $item['footer']);
		return $extracted;	
	}

#####
# Given a root directory and a persona id, generates the full path to storage location. Creates any 
# missing directories along the way (though not the ones contained in the $base)
#
# This is usually not called directly, but rather through make_persona_storage_path and 
# make_persona_pending_path


	function make_persona_path($base, $persona_id)
	{
		$second_folder = $persona_id%10;
		$first_folder = ($persona_id%100 - $second_folder)/10;

		$base = preg_replace('/\/$/', '', $base);
		$persona_path = $base . '/' . $first_folder;
		if (!is_dir($persona_path)) { mkdir($persona_path); }
		$persona_path .= "/" . $second_folder;
		if (!is_dir($persona_path)) { mkdir($persona_path); }
		$persona_path .= "/" . $persona_id;
		if (!is_dir($persona_path)) { mkdir($persona_path); }
		return $persona_path;		
	}


#####
# Storage path for live content

	function make_persona_storage_path($persona_id)
	{
		return make_persona_path(PERSONAS_STORAGE_PREFIX, $persona_id);
	}
	

#####
# Storage path for pending content

	function make_persona_pending_path($persona_id)
	{
		return make_persona_path(PERSONAS_PENDING_PREFIX, $persona_id);
	}


#####
# On inital upload, whenever there's an edit, or if a rebuild is requested, we generate 4 graphics
# and a json file for use in various parts of the site

	function build_persona_files($persona_path, $persona)
	{
		
		#standard preview, for use in the gallery
		$imgcommand = "convert " . $persona_path . "/" . $persona['header'] . " -gravity NorthEast -crop 600x200+0+0  -scale 200x100 " . $persona_path . "/preview.jpg";
		exec($imgcommand);
		
		#large preview for use on the persona detail page
		$imgcommand2 = "convert " . $persona_path . "/" . $persona['header'] . " -gravity NorthEast -crop 1360x200+0+0 -scale 680x100 " . $persona_path . "/preview_large.jpg";
		exec($imgcommand2);
		
		#tiny image for use on the front page popular module
		$imgcommand3 = "convert " . $persona_path . "/" . $persona['header'] . " -gravity NorthEast -crop 320x220+0+0  -scale 64x44 " . $persona_path . "/preview_popular.jpg";
		exec($imgcommand3);
		
		#slightly bigger-than-preview image for featured designer and persona. TODO: Pull these
		#last two out and generate them on demand. Most personas never need them.
		$imgcommand4 = "convert " . $persona_path . "/" . $persona['header'] . " -gravity NorthEast -crop 592x200+0+0  -scale 296x106 " . $persona_path . "/preview_featured.jpg";
		exec($imgcommand4);

		file_put_contents($persona_path . '/index_1.json', json_encode(extract_record_data($persona)));
	}

?>