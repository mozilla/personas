<?php 
	require_once 'lib/personas_constants.php';	
	require_once 'lib/personas_functions.php';	
	require_once 'lib/storage.php';
	require_once 'lib/user.php';
	
	$user = new PersonaUser();
	$user->authenticate();
		
	$page_size = 42;
	$description_max = 50;

	$db = new PersonaStorage();
	$categories = $db->get_categories();
	array_unshift($categories, 'All');
	$tabs = array('Popular', 'Recent', 'All', 'Search', 'My');
	
	$path = array_key_exists('PATH_INFO', $_SERVER) ? $_SERVER['PATH_INFO'] : '/';
	$path = substr($path, 1); #chop the lead slash
	list($category, $tab, $page) = explode('/', $path.'//');

	$no_my = array_key_exists('no_my', $_GET) ? 1 : 0;
	$url_prefix = '/gallery';
	$category = $category && ($category == 'Designer' || in_array(ucfirst($category), $categories)) ? ucfirst($category) : "All";
	if ($category != 'Designer')
		$tab = $tab && in_array(ucfirst($tab), $tabs) ? ucfirst($tab) : 'Popular';
	$page = $page && is_numeric($page) ? $page : 1;
	
	$category_total = $db->get_active_persona_count($category);

	if ($tab == 'All')
		$page_size = 501;
	
	$title = "Gallery"; 
	include 'templates/header.php'; 

	$page_header = "View Personas";

	$list = array();
	if ($category == 'Designer')
	{
		$list = $db->get_persona_by_author($tab);
		$page_header = "Personas by $tab";
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
		$list = $db->get_persona_by_author($user->get_username(), $category == 'All' ? null : $category);			
	}
	elseif ($tab == 'Search')
	{
		if (array_key_exists('p', $_GET) && $_GET['p'])
		{
			$list = $db->search_personas($_GET['p'], $category, $page_size);
		}
	}
	else
	{
		$start = ($page - 1) * $page_size;
		$list = $db->get_recent_personas($category == 'All' ? null : $category, $page_size, $start);
	}

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
				<input type=text name=p value='<?= array_key_exists('p', $_GET) ? $_GET['p'] : '' ?>'><input type=submit><p>
				</form>
<?php
				if (count($list) == 0 && array_key_exists('p', $_GET))
				{
					echo "We were unable to locate any personas that match those search terms. Please try again";
				}
			}
			elseif (count($list) == 0)
			{
				echo "There are no personas available here. Please use the navigation on the left to choose another category.";
			}
			
			include 'templates/pagination.php';
			echo '<p>';
			foreach ($list as $item)
			{
				$preview_url = PERSONAS_LIVE_PREFIX . '/' . url_prefix($item['id']) . '/' . "preview.jpg";
				$persona_json = htmlentities(json_encode(extract_record_data($item)));
				$persona_date = date("n/j/Y", strtotime($item['approve']));
				$item_description = $item['description'];
				if (strlen($item_description) > $description_max)
				{
					$item_description = substr($item_description, 0, $description_max);
					$item_description = preg_replace('/ [^ ]+$/', '', $item_description) . '...';
				}
?>
                        <li class="gallery-item">
                            <div>
                                <h3><?= $item['name'] ?></h3>
                                <div class="preview">
                                    <img src="<?= $preview_url ?>" alt="<?= $item['name'] ?>" persona="<?= $persona_json ?>"/>
                                </div>
                                <p class="designer"><strong>Designer:</strong> <a href="/gallery/Designer/<?= $item['author'] ?>"><?= $item['author'] ?></a></p>
                                <p class="added"><strong>Added:</strong> <?= $persona_date ?></p>
                                <p><?= $item_description ?></p>
                                <p><?=  number_format($item['popularity']) ?> active daily users</p>
                                <p><a href="<?= "/persona/" . ($item['id'] < 10 ? "0" : "") . $item['id'] ?>" class="view">view details »</a></p>
<?php
				if ($tab == 'My' || $user->has_admin_privs())
				{
					print "<p><a href=\"/upload?id=${item['id']}\" target=\"_blank\">Edit</a>";
					if ($user->has_admin_privs())
						print " | <a href=\"/admin/pending.php?verdict=pull&id=${item['id']}\" target=\"_blank\" onClick=\"return confirm('Confirm Deletion');\">Pull</a>";
					print "</p>";
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
