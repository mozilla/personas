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
	
	require_once '../lib/personas_constants.php';
	require_once '../lib/personas_functions.php';
	require_once '../lib/storage.php';
	require_once '../lib/user.php';


	#get the http auth user data
	
	$auth_user = array_key_exists('PHP_AUTH_USER', $_SERVER) ? $_SERVER['PHP_AUTH_USER'] : null;
	$auth_pw = array_key_exists('PHP_AUTH_PW', $_SERVER) ? $_SERVER['PHP_AUTH_PW'] : null;

	header("Content-type: application/json");
	if ($auth_user != 'personasuser' and $authpass != READLOG_PASS)
	{
		header('HTTP/1.1 401 Unauthorized',true,401);
		header('WWW-Authenticate: Basic realm="Personas"');
		
		exit(json_encode("Need auth"));
	}
	$date = array_key_exists('date', $_GET) ? $_GET['date'] : date("Y-m-d",time() - 86400);
	
	$db = new PersonaStorage();
	$results = $db->get_log_by_date($date);
	$output = array();
	foreach ($results as $entry)
	{
		list($action) = explode(' ', $entry['action']);
		switch ($action)
		{
			case 'Flagged': #not relevant entries
			case 'EditRejected':
			case 'Rejected':
			case 'Added':
			case 'Import': #don't want to chain import logging
				break;
			case 'Pulled':
				$output[] = $entry;
				break;
			case 'EditApproved':
			case 'Edit': #some older ones had this format
			case 'Approved';
				$persona = $db->get_persona_by_id($entry['id']);
				$entry['data'] = array('name' => $persona['name'],
									   'header' => $persona['header'],
									   'footer' => $persona['footer'],
									   'category' => $persona['category'],
									   'author' => $persona['author'],
									   'accentcolor' => $persona['accentcolor'],
									   'textcolor' => $persona['textcolor'],
									   'description' => $persona['description'],
									   'license' => $persona['license']
								);
				$output[] = $entry;
				break;
		}
	}
	echo json_encode($output);