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
		$message = "Thanks for requesting an edit to your Persona '$name'. Unfortunately, we cannot process the edit because of the following reason: $reason.\n\n"; 
		$message .= "We apologize for the disappointment, and we hope you will give it another shot.\n\n";
		$message .= "If you have any questions or want more information, please stop by the Persona message boards and tell us what's on your mind.\n\n";
		$message .= "Best Wishes,\n";
		$message .= "The Personas Team\n";
		
		$header = "From: personas-devel@mozilla.com\r\n";
		return mail($address, 'A problem with your Persona edit', $message, $header);
	}
		
	function send_accept_email($address, $name)
	{
		$message = "Congrats ' You have successfully updated your Persona '$name'!\n\n";
		$message .= "If you have any questions or want more information, please stop by the Persona message boards and tell us what's on your mind.\n\n";
		$message .= "Best Wishes,\n";
		$message .= "The Personas Team\n";
		
		$header = "From: personas-devel@mozilla.com\r\n";
		return mail($address, 'Your Persona has been successfully edited', $message, $header);
	}
	 

	try 
	{
		$user = new PersonaUser();
		$user->authenticate();
		if (!$user->has_admin_privs())
		{
			$this->_errors['login_user'] = "This account does not have privileges for this operation. Please log in with an account that does.";
			$user->auth_form();
			exit;
		}
	}
	catch(Exception $e)
	{
		error_log($e->getMessage());
		print("Database problem. Please try again later.");
		exit;
	}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
            "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>Personas for Firefox | Gallery</title>
	<link href="/store/css/style.css" rel="stylesheet" type="text/css" media="all" />
</head>
<body>
    <div id="outer-wrapper">
        <div id="inner-wrapper">
            <p id="account"><a href="https://personas.services.mozilla.com/upload">Designer Tools</a></p>
            <div id="nav">
                <h1><a href="http://www.getpersonas.com/"><img src="/store/img/logo.png" alt="Mozilla Labs Personas"></a></h1>
                <ul>
                    <li class="gallery"><a href="http://www.getpersonas.com/store/gallery/All/Popular">Gallery</a></li>
                    <li class="create"><a href="https://personas.services.mozilla.com/upload">Create <br/>Your Own</a></li>
                    <li class="demo"><a href="https://www.getpersonas.com/store/demo_install.html">Demo</a></li>
                    <li class="faq"><a href="https://www.getpersonas.com/store/faq.html">Frequent <br/>Questions</a></li>
                </ul>
            </div>
            <div id="header">
                <h2>View Personas</h2>
                <h3>Your browser, your style! Dress it up with easy-to-change "skins" for your
                Firefox.</h3>
            </div>
            <div id="maincontent">
                <p id="breadcrumbs">Personas Home : Admin </p>
                <div id="gallery">
<?php

	$db = new PersonaStorage();
	$categories = $db->get_categories();
	
	$id = array_key_exists('id', $_GET) ? $_GET['id'] : null;
	$category = array_key_exists('category', $_GET) && $_GET['category'] != 'All' ? $_GET['category'] : null;
	
	if (array_key_exists('verdict', $_GET) && $id)
	{
		$persona = $db->get_persona_by_id($id);
		$edits = $db->get_edits_by_id($id);

		switch ($_GET['verdict'])
		{
			case 'accept':
				if ($edits['header'])
				{
					unlink(make_persona_pending_path($id) . '/' . $persona['header']);
					rename (make_persona_pending_path($id) . '/' . $edits['header'], make_persona_storage_path($id) . '/' . $edits['header']);
					rename (make_persona_pending_path($id) . '/preview.jpg', make_persona_storage_path($id) . '/preview.jpg');
					rename (make_persona_pending_path($id) . '/preview_large.jpg', make_persona_storage_path($id) . '/preview_large.jpg');
					rename (make_persona_pending_path($id) . '/preview_popular.jpg', make_persona_storage_path($id) . '/preview_popular.jpg');
				}
				
				if ($edits['footer'])
				{
					unlink(make_persona_pending_path($id) . '/' . $persona['footer']);
					rename (make_persona_pending_path($id) . '/' . $edits['footer'], make_persona_storage_path($id) . '/' . $edits['footer']);
				}
				
				$db->approve_persona_edit($persona{'id'});
				if ($persona['author'] == $edits['author'])
					send_accept_email($user->get_email($persona['author']), $edits['name']);
				$id = null;
				break;
			case 'reject':
				$db->reject_persona_edit($persona['id']);
				if ($persona['author'] == $edits['author'])
					send_problem_email($user->get_email($persona['author']), $_GET['reason'], $persona['name']);
				$id = null;
				break;
			default:
				print "Could not understand the verdict";	
				exit;
		}
	}
	
	if ($id) #working with a specific persona.
	{
		$result = $db->get_edits_by_id($id);
		$original_data = $db->get_persona_by_id($id);

		$category = $result['category'];
		$path = PERSONAS_URL_PREFIX . '/' . url_prefix($id);
		$old_path = PERSONAS_LIVE_PREFIX . '/' . url_prefix($id);
?>
		<form action="/admin/editing.php" method="GET">
		<input type=hidden name=id value=<?= $result{'id'} ?>>

<?php
		if ($original_data['name'] != $result['name'])
		{
			print "<div style=\"outline: red solid thin\">Name: " . $result['name'] . " (original: " . $original_data['name'] . ")</div>\n";
		}
		else 
		{ 
			print "Name: " . $result['name']; 
		}
		print "<p>";
		
		print "User: " . $original_data['author'] . " (edit submitted by: " . $result['author'] . ")\n";
		print "<p>";

		if ($original_data['category'] != $result['category'])
		{
			print "<div style=\"outline: red solid thin\">Category: " . $result['category'] . " (original: " . $original_data['category'] . ")</div>\n";
		}
		else 
		{ 
			print "Category: " . $result['category']; 
		}
		print "<p>";

		if ($original_data['description'] != $result['description'])
		{
			print "<div style=\"outline: red solid thin\">Description: " . $result['description'] . " (original: " . $original_data['description'] . ")</div>\n";
		}
		else 
		{ 
			print "Description: " . $result['description']; 
		}
		print "<p>";

		if ($original_data['textcolor'] != $result['textcolor'])
		{
			print "<div style=\"outline: red solid thin\">Text Color: " . $result['textcolor'] . " (original: " . $original_data['textcolor'] . ")</div>\n";
		}
		else 
		{ 
			print "Text Color: " . $result['textcolor']; 
		}
		print "<p>";

		if ($original_data['accentcolor'] != $result['accentcolor'])
		{
			print "<div style=\"outline: red solid thin\">Accent color: " . $result['accentcolor'] . " (original: " . $original_data['accentcolor'] . ")</div>\n";
		}
		else 
		{ 
			print "Accent Color: " . $result['accentcolor']; 
		}
		print "<p>";

		if ($result['header'])
		{
			$preview_url =  $path . "/preview.jpg";
			$preview_large =  $path . "/preview_large.jpg";
			$preview_popular =  $path . "/preview_popular.jpg";
			$header_url =  $path . "/" . $result['header'];

			$old_preview_url =  $old_path . "/preview.jpg";
			$old_preview_large =  $old_path . "/preview_large.jpg";
			$old_preview_popular =  $old_path . "/preview_popular.jpg";
			$old_header_url =  $old_path . "/" . $original_data['header'];
			
			print "<div style=\"outline: red solid thin\">Preview: <img src=\"$preview_url\"> Original: <img src=\"$old_preview_url\">\n";
			print "<P>Detail Preview: <br><img src=\"$preview_large\"> <br>Original: <br><img src=\"$old_preview_large\">\n";
			print "<P>Popular Preview: <br><img src=\"$preview_popular\"> <br>Original: <br><img src=\"$old_preview_popular\">\n";
			print "<P>Header: <br><img src=\"$header_url\"> <br>Original: <br><img src=\"$old_header_url\">\n";
			print "</div>";
		}
		
		if ($result['footer'])
		{
			$footer_url =  $path . "/" . $result['footer'];
			$old_footer_url =  $old_path . "/" . $original_data['footer'];
			print "<div style=\"outline: red solid thin\">Footer: <br><img src=\"$footer_url\"> <br>Original: <br><img src=\"$old_footer_url\"></div>\n";
		}
	
		print "<p>";
		if ($original_data['reason'] != $result['reason'])
		{
			print "<div style=\"outline: red solid thin\">Reason: " . $result['reason'];
			if ($result['reason_other'])
				print " (" . $original_data['reason_other'] . ")";
			print " (original: " . $original_data['reason'];
			if ($result['reason_other'])
				print " (" . $result['reason_other'] . ")";
			print ")</div>\n";
		}
		else 
		{ 
			print "Reason: " . $result['reason'];
			if ($result['reason_other'])
				print " (" . $result['reason_other'] . ")";
		}
?>
<p>
		If reject, reason to tell the user: <input type=text name=reason>
		<p>
		<input type="submit" name="verdict" value="accept">
		<input type="submit" name="verdict" value="reject">
		</form>
<?php
		
	}
	else
	{
		$results = $db->get_pending_edits($category);
		if (!$count = count($results))
		{
			print "There are no more pending edits";
		}
		else
		{
            print "<ul>\n";
			foreach ($results as $item)
			{
				$path = PERSONAS_URL_PREFIX . '/' . url_prefix($item['id']);
				$preview_url =  $path . "/preview.jpg";
				$persona_json = htmlentities(json_encode(extract_record_data($item)));
?>
                        <li class="gallery-item">
                            <div>
                                <h3><a href="/admin/editing.php?id=<?= $item['id'] ?>"><?= $item['name'] ?></a></h3>
                                <div class="preview">
                                    <img src="<?= $preview_url ?>" alt="<?= $item['name'] ?>" persona="<?= $persona_json ?>"/>
                                </div>
                                <p class="designer"><strong>Designer:</strong> <?= $item['author'] ?></p>
                                <p class="designer"><strong>Category:</strong> <?= $item['category'] ?></p>
                                <p class="added"><strong>Submitted:</strong> <?= $item['submit'] ?></p>
                                <p><?= $item['description'] ?></p>
                                <p><a href="/admin/editing.php?id=<?= $item['id'] ?>" class="view">Administer »</a></p>
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
			foreach ($categories as $list_category)
			{
				$active = ($category == $list_category) ? 'class="active"' : null;
				print "		<li" . ($category == $list_category ? ' class="active"' : "") . "><a href=\"/admin/editing.php?category=$list_category\">$list_category</a></li>\n";
			}
?>
            </div>
        </div>
    </div>
    <script src="/store/js/jquery.js"></script>
    <script src="/store/js/script.js"></script>
    <script type="text/javascript" charset="utf-8">
        $(document).ready(function() {
           $("#gallery .preview img").previewPersona();
        });
    </script>
    <div id="footer">
        <p>Copyright ' <?= date("Y") ?> Mozilla. Personas for Firefox is a Mozilla Labs Beta Project | <a href="http://labs.mozilla.com/about-labs/">About Mozilla Labs</a>   |  Terms of Use  |  Privacy</p>
    </div>
</body>
</html>
