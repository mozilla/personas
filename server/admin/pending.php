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

	function send_problem_email($address, $reason, $name)
	{
		$message = "Thanks for submitting your Persona '$name'. Unfortunately, we cannot add your Persona because of the following reason: $reason.\n\n"; 
		$message .= "We appreciate your involvement in the Personas community and encourage you to create another design that fits our Terms of Service (http://personas.services.mozilla.com/upload).\n\n";
		$message .= "Also, you are able to use apply any design you like on your own computer. Here is how:\n\n";
		$message .= "1. If you have Personas installed, click on the little fox on the bottom left of your computer screen.\n\n";
		$message .= "2. Click on \"Preferences\" and ensure the box \"Show Custom Persona in Menu\" is checked and close the box.\n\n";
		$message .= "3. Click on the little fox again. Mouse over \"Custom\" in the menu and to the right find and click \"Edit\".\n\n";
		$message .= "This will take you to a custom persona interface that will let you design any persona you like for your own computer. Thank you again for being a part of our community.\n\n";
		$message .= "If you have any questions or want more information, please stop by the Persona message boards and tell us what's on your mind.\n\n";
		$message .= "Best Wishes,\n";
		$message .= "The Personas Team\n";
		
		$header = "From: personas@mozilla.com\r\n";
		return mail($address, 'A problem with your Persona submission', $message, $header);
	}
	
	function send_accept_email($address, $name, $id)
	{
		$message = "Thanks for submitting your Persona '$name'! We're big fans of creativity, and it's fun to see how people are dressing up their browsers.\n\n";
		$message .= "Once it gets automatically copied up to the live server you'll be able to view it at http://www.getpersonas.com/persona/$id .\n\n";
		$message .= "You can also check it out shortly in the Gallery at http://www.getpersonas.com/gallery/All/Recent .\n\n";
		$message .= "If you have any questions or want more information, please stop by the Persona message boards at https://labs.mozilla.com/forum/?CategoryID=18 and tell us what's on your mind.\n\n";
		$message .= "Best Wishes,\n";
		$message .= "The Personas Team\n";
		
		$header = "From: personas@mozilla.com\r\n";
		return mail($address, 'Thanks for submitting your Firefox persona', $message, $header);
	}
	
	function send_edit_email($address, $name)
	{
		$message = "Congrats ' You have successfully updated your Persona '$name'!\n\n";
		$message .= "If you have any questions or want more information, please stop by the Persona message boards and tell us what's on your mind.\n\n";
		$message .= "Best Wishes,\n";
		$message .= "The Personas Team\n";
		
		$header = "From: personas@mozilla.com\r\n";
		return mail($address, 'Your Persona has been successfully edited', $message, $header);
	}
	 

	$title = "Admin | Uploads Pending Approval";
	$json = array_key_exists('json', $_GET);
	
	try 
	{
		$user = new PersonaUser();
		$user->authenticate();
		$user->force_signin(1);
		if (!$user->has_approval_privs())
		{
			$_errors['error'] = 'This account does not have privileges for this operation. Please <a href="/signin?action=logout">log in</a> with an account that does.';
			include '../templates/user_error.php';
			exit;
		}
	}
	catch(Exception $e)
	{
		error_log($e->getMessage());
		print("Database problem. Please try again later.");
		exit;
	}


	$db = new PersonaStorage();
	$categories = $db->get_categories();
	
	$id = array_key_exists('id', $_GET) ? $_GET['id'] : null;
	$page_category = array_key_exists('category', $_GET) && $_GET['category'] != 'All' ? $_GET['category'] : null;
	
	if (array_key_exists('verdict', $_GET) && $id)
	{
		$persona = $db->get_persona_by_id($id);

		switch ($_GET['verdict'])
		{
			case 'accept':
				rename (make_persona_pending_path($id), make_persona_storage_path($id));
				$db->approve_persona($persona{'id'});
				send_accept_email($user->get_email($persona['author']), $persona['name'], $persona['id']);
				$db->log_action($user->get_username(), $persona['id'], "Approved");
				$id = null;
				break;
			case 'change':
				$change_category = ini_get('magic_quotes_gpc') ? stripslashes($_GET['changecategory']) : $_GET['changecategory'];
				$db->change_persona_category($persona['id'], $change_category);
				break;			
			case 'rebuild':
				build_persona_files(make_persona_pending_path($id), $persona);
				break;			
			case 'rebuild_live':
				build_persona_files(make_persona_storage_path($id), $persona);
				break;			
			case 'pull':
				rename (make_persona_storage_path($id), make_persona_pending_path($id));
				$db->reject_persona($persona['id']);
				print "Persona " . $persona['id'] . " pulled";
				$db->log_action($user->get_username(), $persona['id'], "Pulled");
				break;
			case 'copyrightreject':
				$reason = "We are unable to confirm that you have legal rights to the design contained in the persona. \n\nTo be the rightful owner of a persona design, you must own the actual logo or art inside the design. For example, if you use a sports team's logo to create a persona design, you must have permission from that particular team. To confirm that you have these rights, please send a note to personas@mozilla.com with subject line \"I have the rights to publish [insert persona design name]\" and we will review your submission as soon as possible";
				$db->reject_persona($persona['id']);
				send_problem_email($user->get_email($persona['author']), $reason, $persona['name']);
				$db->log_action($user->get_username(), $persona['id'], "Rejected - Copyright concern");
				$id = null;
				break;				
			case 'duplicatereject':
				$reason = "The design appears to be identical to a previous version you submitted";
				$db->reject_persona($persona['id']);
				send_problem_email($user->get_email($persona['author']), $reason, $persona['name']);
				$db->log_action($user->get_username(), $persona['id'], "Rejected - Duplicate");
				$id = null;
				break;				
			case 'flagforlegal':
				$db->flag_persona_for_legal($persona['id']);
				$db->log_action($user->get_username(), $persona['id'], "Flagged for Legal");
				$id = null;
				break;				
			case 'reject':
				$db->reject_persona($persona['id']);
				send_problem_email($user->get_email($persona['author']), $_GET['reason'], $persona['name']);
				$db->log_action($user->get_username(), $persona['id'], "Rejected - " . $_GET['reason']);
				$id = null;
				break;
			default:
				print "Could not understand the verdict";	
				exit;
		}
		
		if (array_key_exists('json', $_GET))
		{
			echo "<option selected>Rejected</option>";
			exit;
		}
		
	}
	
	include '../templates/header.php';
?>
<body>
    <div id="outer-wrapper">
        <div id="inner-wrapper">
<?php include '../templates/nav.php'; ?>
            <div id="header">
                <h2>View Personas</h2>
                <h3>Your browser, your style! Dress it up with easy-to-change "skins" for your
                Firefox.</h3>
            </div>
            <div id="maincontent">
                <p id="breadcrumbs"><a href="http://www.getpersonas.com">Personas Home</a> : Admin </p>
                <div id="gallery">
<?php

	if (array_key_exists('verdict', $_GET) && $_GET['verdict'] == 'pull')
	{
		#Do nothing here
	}
	elseif ($id) #working with a specific persona.
	{
		$result = $db->get_persona_by_id($id);
		$category = $result['category'];
		$path = PERSONAS_URL_PREFIX . '/' . url_prefix($id);
		$preview_url =  $path . "/preview.jpg";
		$preview_large =  $path . "/preview_large.jpg";
		$preview_popular =  $path . "/preview_popular.jpg";
		$header_url =  $path . "/" . $result['header'];
		$footer_url =  $path . "/" . $result['footer'];
?>
		<form action="/admin/pending.php" method="GET">
		<input type=hidden name=id value=<?= $result{'id'} ?>>
		<input type=hidden name=category value="<?= $page_category ?>">
		Internal ID: <?= $result{'id'} ?>
		<br>
		Name: <?= $result['name'] ?>
		<br>
		User: <a href="/gallery/designer/<?= $result['author'] ?>" target="_blank"><?= $result['author'] ?></a>
		<br>
		Category: <select name="changecategory">
		<?php
			foreach ($categories as $pcategory)
			{
				print "<option" . ($result['category'] == $pcategory ? ' selected="selected"' : "") . ">$pcategory</option>";
			}
		?>
		</select><input type="submit" name="verdict" value="change">
		<br>
		License: <?= $result['license'] ?>
		<br>
		Reason: <?= $result['reason'] ?>
		<br>
		Description: <?= $result['description'] ?>
		<p>
		Preview:
		<br>
		<img src="<?= $preview_url ?>"><p>
		Preview Large:
		<br>
		<img src="<?= $preview_large ?>"><p>
		Preview Popular:
		<br>
		<img src="<?= $preview_popular ?>"><p>
		Header:<br>
		<img src="<?= $header_url ?>"><p>
		Footer:<br>
		<img src="<?= $footer_url ?>"><p>
		<p>
		If reject, reason to tell the user: <input type=text id=formreason name=reason>
		<p>
		<input type="submit" name="verdict" value="accept">
		<input type="submit" name="verdict" value="reject" onclick="if ($('#formreason').val() == '') {alert('Please provide a reason for rejection'); return false;}">
		<input type="submit" name="verdict" value="copyrightreject">
		<input type="submit" name="verdict" value="flagforlegal">
		<input type="submit" name="verdict" value="rebuild">
		</form>
<?php
		
	}
	else
	{
		if ($page_category == 'Legal')
			$results = $db->get_legal_flagged_personas();			
		else	
			$results = $db->get_pending_personas($page_category);
			
		if (!count($results))
		{
			print "There are no more pending personas";
		}
		else
		{
            print count($results) . " pending personas";
            print "<ul>\n";
			foreach ($results as $persona)
			{
				$path = PERSONAS_URL_PREFIX . '/' . url_prefix($persona['id']);
				$preview_url =  $path . "/preview.jpg";
				$persona_json = htmlentities(json_encode(extract_record_data($persona, 'http://' . $_SERVER['SERVER_NAME'] . '/pending/')));
?>
                        <li class="gallery-item">
                            <div>
                                <h3><a href="/admin/pending.php?id=<?= $persona['id'] ?>"><?= $persona['name'] ?></a></h3>
                                <div class="preview">
                                    <img src="<?= $preview_url ?>" alt="<?= $persona['name'] ?>" persona="<?= $persona_json ?>"/>
                                </div>
                                <p class="designer"><strong>Designer:</strong> <?= $persona['author'] ?></p>
                                <p class="designer"><strong>Category:</strong> <?= $persona['category'] ?></p>
                                <p class="added"><strong>Submitted:</strong> <?= $persona['submit'] ?></p>
                                <p><?= $persona['description'] ?></p>
                                <p><a href="/admin/pending.php?id=<?= $persona['id'] ?>&category=<?= $page_category ?>" class="view">Administer »</a></p>
                                <p align=right>
									<select onChange="rejectselect(<?= $persona['id'] ?>, this)">
										<option value="" selected>quickverdict >></option>
										<option value="copyrightreject">Copyright</option>
										<option value="duplicatereject">Duplicate</option>
									</select>
                                </p>
                            </div>
                        </li>
 <?php
 			}
			print "</ul>\n";
		}
	}

	
?>
            </div>
        </div>
	<div id="secondary-content">
                <ul id="subnav">
<?php
			array_unshift($categories, 'All');
			array_push($categories, 'Legal');
			if (!$page_category)
				$page_category = 'All';
			foreach ($categories as $list_category)
			{
				print "		<li" . ($page_category == $list_category ? ' class="active"' : "") . "><a href=\"/admin/pending.php?category=$list_category\">$list_category</a></li>\n";
			}
?>
            </div>
        </div>
    </div>
<?php include '../templates/footer.php'; ?>
    <script type="text/javascript" charset="utf-8">
        $(document).ready(function() {
           $("#gallery .preview img").previewPersona();
        });
		
		function rejectselect(id, pointer)
		{
			$(pointer).load("/admin/pending.php?json=1&verdict=" + $(pointer).val() + "&id=" + id);
			return false;
		}
    </script>
</body>
</html>
