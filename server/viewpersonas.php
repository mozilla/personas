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
		
	$page_size = 42; #defalt number of personas per page
	$description_max = 50; #truncated description size
	$url_prefix = '/gallery'; #telling the templates the gallery root
	$title = "Gallery"; #page title for the header template
	$no_my = array_key_exists('no_my', $_GET) ? 1 : 0; #whether to display all the dynamic stuff
	$display_username = '';
	
	$categories = $db->get_categories();
	array_unshift($categories, 'All');
	$tabs = array('Popular', 'Recent', 'All', 'My', 'Search'); # pulling 'Search'
	
	$path = array_key_exists('PATH_INFO', $_SERVER) ? $_SERVER['PATH_INFO'] : '/';
	$path = substr($path, 1); #chop the lead slash
	list($category, $tab, $page) = explode('/', $path.'//');

	$category = ucfirst($category);
	if ($category != 'Designer')
	{
		$tab = in_array(ucfirst($tab), $tabs) ? ucfirst($tab) : 'Popular';
		if (!in_array($category, $categories))
			$category = 'All';
	}
		
	$page_header = "View Personas";
	$list = array(); #grab the appropriate personas for display
	if ($category == 'Designer')
	{
		$display_username = $user->get_display_username($tab);
		$page_header = "Personas by " . $display_username;
		if ($tab) #tab is actually the author here
			$list = $db->get_persona_by_author($tab); 
	}
	elseif ($tab == 'Recent')
	{
		$list = $db->get_recent_personas($category == 'All' ? null : $category, $page_size);
	}
	elseif ($tab == 'Popular')
	{
		$list = $db->get_popular_personas($category == 'All' ? null : $category, $page_size);			
	}
	elseif ($tab == 'My')
	{
		$user->force_signin();
		$page_header = "My Personas";
		if ($user->get_username())
			$list = $db->get_persona_by_author($user->get_username(), $category == 'All' ? null : $category);			
	}
	elseif ($tab == 'Search')
	{
		if (array_key_exists('p', $_GET) && $_GET['p'])
		{
			$list = $db->search_personas($_GET['p'], $category, $page_size);
		}
	}
	else #tab = all
	{
		$page_size = 501;
		$page = is_numeric($page) ? $page : 1;
		$start = ($page - 1) * $page_size;
		$list = $db->get_recent_personas($category == 'All' ? null : $category, $page_size, $start);
	}

	include 'templates/header.php'; 

?>
<body>
    <div id="outer-wrapper">
        <div id="inner-wrapper">
<?php include 'templates/nav.php'; ?>
<?php
	if ($category == 'Designer' && file_exists("templates/designer/$tab.php"))
	{
		include "templates/designer/$tab.php";
	}
	else
	{
		if (!($category == 'Designer' && $header_text = $user->get_description($tab)))
		{
			$header_text = 'Your browser, your style! Dress it up with easy-to-change "skins" for your Firefox.';
		}
?>
			<div id="header">
                <h2><?= $page_header ?></h2>
                <h3><?= $header_text ?></h3>
            </div>
<?php } ?>
			<div id="maincontent">
                <p id="breadcrumbs"><a href="http://www.getpersonas.com">Personas Home</a> : <a href="http://www.getpersonas.com/gallery/All/Popular">Gallery</a> : <?= $category ?><?php if ($category == 'Designer') { echo " : $display_username"; } else if ($tab != "All") { echo " : $tab"; } ?></p>
                <div id="gallery">
<?php
			
			if ($tab == 'Search')
			{
?>  
				<form action="" method="GET">
				    <input type=text name=p value='<?= array_key_exists('p', $_GET) ? $_GET['p'] : '' ?>'>
				    <button class="button search" type="submit">
                    <span>search</span>
                    <span class="arrow"/>
                    </button>
				</form>
<?php
				if (count($list) == 0 && array_key_exists('p', $_GET))
				{
					echo "<p>We were unable to locate any personas that match those search terms. Please try again</p>";
				}
			}
			elseif (count($list) == 0)
			{
				echo "<p>There are no personas available here. Please use the navigation on the left to choose another category.</p>";
			}
			
			
			
			include 'templates/pagination.php';
?>
					<ul>
<?php
			foreach ($list as $persona)
			{
				$preview_url = PERSONAS_LIVE_PREFIX . '/' . url_prefix($persona['id']) . '/' . "preview.jpg";
				$persona['json'] = htmlentities(json_encode(extract_record_data($persona)));
				$persona['date'] = date("n/j/Y", strtotime($persona['approve']));
				$persona['short_description'] = $persona['description'];
				if (strlen($persona['short_description']) > $description_max)
				{
					$persona['short_description'] = substr($persona['short_description'], 0, $description_max);
					$persona['short_description'] = preg_replace('/ [^ ]+$/', '', $persona['short_description']) . '...';
				}
?>
                        <li class="gallery-item">
                            <div>
                                <h3><?= $persona['name'] ?></h3>
                                <div class="preview">
                                    <a href="/persona/<?= ($persona['id'] < 10 ? "0" : "") . $persona['id'] ?>"><img src="<?= $preview_url ?>" alt="<?= $persona['name'] ?>" persona="<?= $persona['json'] ?>"/></a>
                                </div>
                                <p class="designer"><strong>Designer:</strong> <a href="/gallery/Designer/<?= $persona['author'] ?>"><?= $persona['display_username'] ?></a></p>
                                <p class="added"><strong>Added:</strong> <?= $persona['date'] ?></p>
                                <p><?= $persona['short_description'] ?></p>
                                <p><?= number_format($persona['popularity']) ?> active daily users</p>
                                <p><a href="<?= "/persona/" . ($persona['id'] < 10 ? "0" : "") . $persona['id'] ?>" class="view">view details »</a></p>
<?php
				if ($user->has_admin_privs() || ($tab == 'My' && $persona['locale'] == PERSONAS_LOCALE))
				{
?>
								<p><a href="/upload?id=<?= $persona['id'] ?>" target="_blank">Edit</a>
								| 
								<a href="/delete/<?= $persona['id'] ?>" target="_blank">Delete</a></p>
<?php
				}
?>
                            </div>
                        </li>
 <?php
 			}
 ?>
                    </ul>
                    <?php
                    if($category == 'Designer') 
        			{
        			    echo '<p><script type="text/javascript" src="http://w.sharethis.com/button/sharethis.js#publisher=df86b16e-195c-4917-ae28-61a1382ba281&amp;type=website&amp;send_services=&amp;post_services=facebook%2Cdigg%2Cdelicious%2Cybuzz%2Ctwitter%2Cstumbleupon%2Creddit%2Ctechnorati%2Cmixx%2Cblogger%2Ctypepad%2Cwordpress%2Cgoogle_bmarks%2Cwindows_live%2Cmyspace%2Cfark%2Cbus_exchange%2Cpropeller%2Cnewsvine%2Clinkedin"></script></p>';
        			}
                    ?>
                    
                </div>
<?php include 'templates/pagination.php'; ?>
            </div>
<?php include 'templates/category_nav.php'; ?>
        </div>
    </div>
<?php include 'templates/footer.php'; ?>
    <script type="text/javascript" charset="utf-8">
        $(document).ready(function() {
           $("#gallery .preview img").previewPersona();
        });
    </script>
</body>
</html>
