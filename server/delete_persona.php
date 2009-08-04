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

	
	function send_delete_email($address, $reason, $name)
	{
		$message = sprintf(_("We have had to remove your persona '%s' from the gallery for the following reason: %s.\n\n
		    If you have any questions about this decision, please contact us at personas@mozilla.com. We look forward to seeing your contributions to the community in the future.\n\n
		    Best Wishes,\n
		    The Personas Team\n"), $name, $reason);
		
		$header = "From: personas@mozilla.com\r\n"; //TODO
		return mail($address, _('Removing a persona'), $message, $header);
	}


	$path = array_key_exists('PATH_INFO', $_SERVER) ? $_SERVER['PATH_INFO'] : '/';
	$path = substr($path, 1); #chop the lead slash
	list($id) = explode('/', $path);
	
	$user = new PersonaUser();

	$auth_user = $user->authenticate();
	$user->force_signin();

	$db = new PersonaStorage();
	$persona = $db->get_persona_by_id($id);
	
	if(!$persona || $persona['status'] != 1)
	{
		$persona = null;
		include 'templates/delete_persona_tmpl.php';
		exit;
	}
	
	if (!($user->has_admin_privs() || $persona['author'] == $auth_user))
	{
		$override_error = _("You don't have permission to edit that");
		include 'templates/delete_persona_tmpl.php';
		exit;
	}
	
	if (array_key_exists('confirm', $_POST))
	{
		rename (make_persona_storage_path($persona['id']), make_persona_pending_path($persona['id']));
		$db->reject_persona($persona['id']);
		if ($persona['author'] != $auth_user)
			send_delete_email($user->get_email($persona['author']), $_POST['dreason'], $persona['name']);
		$db->log_action($user->get_username(), $persona['id'], "Pulled" . (array_key_exists('dreason', $_POST) ? " - " . $_POST['dreason'] : ""));  //TODO: localizing logs necessary?
		include 'templates/delete_persona_success_tmpl.php';
	}
	else
	{
		$persona['json'] = htmlentities(json_encode(extract_record_data($persona)));
		include 'templates/delete_persona_tmpl.php';
	}
	
	

?>

