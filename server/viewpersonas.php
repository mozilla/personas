<?php 
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

	$categories = $db->get_categories();
	array_unshift($categories, 'All');
	$tabs = array('Popular', 'Recent', 'All', 'Search', 'My');
	
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
		$page_header = "Personas by $tab";
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
		$page = is_integer($page) ? $page : 1;
		$start = ($page - 1) * $page_size;
		$list = $db->get_recent_personas($category == 'All' ? null : $category, $page_size, $start);
	}

	include 'templates/header.php'; 

?>
<body>
    <div id="outer-wrapper">
        <div id="inner-wrapper">
<?php include 'templates/nav.php'; ?>
            <div id="header">
                <h2><?= $page_header ?></h2>
                <h3>Your browser, your style! Dress it up with easy-to-change "skins" for your Firefox.</h3>
            </div>
            <div id="maincontent">
                <p id="breadcrumbs"><a href="http://www.getpersonas.com">Personas Home</a> : <a href="http://www.getpersonas.com/gallery/All/Popular">Gallery</a> : <?= $category ?><?php if ($tab != "All") { echo " : $tab"; } ?></p>
                <div id="gallery">
                    <ul>
<?php
			
			if ($tab == 'Search')
			{
?>
				<form action="" method=GET>
				<input type=text name=p value='<?= array_key_exists('p', $_GET) ? $_GET['p'] : '' ?>'><input type=submit value="Search"><p>
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
			foreach ($list as $persona)
			{
				$preview_url = PERSONAS_LIVE_PREFIX . '/' . url_prefix($persona['id']) . '/' . "preview.jpg";
				$persona_json = htmlentities(json_encode(extract_record_data($persona)));
				$persona_date = date("n/j/Y", strtotime($persona['approve']));
				$persona_description = $persona['description'];
				if (strlen($persona_description) > $description_max)
				{
					$persona_description = substr($persona_description, 0, $description_max);
					$persona_description = preg_replace('/ [^ ]+$/', '', $persona_description) . '...';
				}
?>
                        <li class="gallery-item">
                            <div>
                                <h3><?= $persona['name'] ?></h3>
                                <div class="preview">
                                    <img src="<?= $preview_url ?>" alt="<?= $persona['name'] ?>" persona="<?= $persona_json ?>"/>
                                </div>
                                <p class="designer"><strong>Designer:</strong> <a href="/gallery/Designer/<?= $persona['author'] ?>"><?= $persona['author'] ?></a></p>
                                <p class="added"><strong>Added:</strong> <?= $persona_date ?></p>
                                <p><?= $persona_description ?></p>
                                <p><?= number_format($persona['popularity']) ?> active daily users</p>
                                <p><a href="<?= "/persona/" . ($persona['id'] < 10 ? "0" : "") . $persona['id'] ?>" class="view">view details »</a></p>
<?php
				if ($tab == 'My' || $user->has_admin_privs())
				{
					echo '<p><a href="/upload?id=' . $persona['id'] . '" target="_blank">Edit</a>';
					if ($user->has_admin_privs())
						echo ' | <a href="/admin/pending.php?verdict=pull&id=' . $persona['id'] . '" target="_blank" onClick="return confirm(\'Confirm Deletion\');">Pull</a>';
					echo "</p>";
				}
?>
                            </div>
                        </li>
 <?php
 			}
 ?>
                    </ul>
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
