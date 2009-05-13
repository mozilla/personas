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
	require_once 'server/lib/user.php';

	$username = "personasuser";
	$password = "ppass";
	$url_host = "http://personas.services.mozilla.com";

	$date = $argc > 1 ? $argv[1] : date("Y-m-d",time() - 86400);
	$url = "$url_host/admin/readlog.php?date=$date";

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch,CURLOPT_USERPWD,"$username:$password");
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
 	$json = curl_exec($ch);

	if (!substr($json, 0, 1) == '[') #valid json response
	{
		echo $json;
		exit;
	}

	$db = new PersonaStorage();

	$entries = json_decode($json, true);
	foreach ($entries as $persona)
	{
		list($action) = explode(' ', $persona['action']);
		switch ($action)
		{
			case 'Pulled':
				$db->reject_persona($persona['id']);
				$db->log_action('importer', $persona['id'], "Import Pull");
				break;
			case 'EditApproved':
			case 'Edit': #some older ones had this format
			case 'Approved';
				$db->direct_persona_input($persona['id'], 
										  $persona['data']['name'],
										  $persona['data']['category'],
										  $persona['data']['header'],
										  $persona['data']['footer'],
										  $persona['data']['author'],
										  $persona['data']['accentcolor'],
										  $persona['data']['textcolor'],
										  $persona['data']['description'],
										  $persona['data']['license'],
										  '','');
				#now grab the images
				$path = make_persona_storage_path($persona['id']);
				$persona['data']['id'] = $persona['id']; #need to pass this in

				#header
				$ch = curl_init();
				$fp = fopen($path . '/' . $persona['data']['header'], "w");	
				curl_setopt($ch, CURLOPT_URL, $url_host . '/static/' . url_prefix($persona['id']) . '/' . $persona['data']['header']);
				curl_setopt($ch, CURLOPT_FILE, $fp);
				curl_exec($ch);
				fclose($fp);	
								
				#footer
				$ch = curl_init();
				$fp = fopen($path . '/' . $persona['data']['footer'], "w");	
				curl_setopt($ch, CURLOPT_URL, $url_host . '/static/' . url_prefix($persona['id']) . '/' . $persona['data']['footer']);
				curl_setopt($ch, CURLOPT_FILE, $fp);
				curl_exec($ch);
				fclose($fp);	
				
				build_persona_files(make_persona_storage_path($persona['id']), $persona['data']);
				$db->log_action('importer', $persona['id'], "Import " . $action);				
				break;
		}			
	}


?>