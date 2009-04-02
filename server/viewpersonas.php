<?php 
	require_once 'lib/personas_constants.php';	
	require_once 'lib/personas_functions.php';	
	require_once 'lib/storage.php';
	require_once 'lib/user.php';
	
	$user = new PersonaUser();
	$user->authenticate();
		
	$page_size = 42;

	$db = new PersonaStorage();
	$categories = $db->get_categories();
	array_unshift($categories, 'All');
	$tabs = array('Popular', 'Recent', 'All', 'My');
	
	$path = array_key_exists('PATH_INFO', $_SERVER) ? $_SERVER['PATH_INFO'] : '/';
	$path = substr($path, 1); #chop the lead slash
	list($category, $tab, $page) = explode('/', $path.'//');

	$no_my = array_key_exists('no_my', $_GET) ? 1 : 0;
	$url_prefix = '/gallery';
	$category = $category && ($category == 'Designer' || in_array(ucfirst($category), $categories)) ? ucfirst($category) : "All";
	$tab = $tab && ($category == 'Designer' || in_array(ucfirst($tab), $tabs)) ? ucfirst($tab) : 'Popular';
	$page = $page && is_numeric($page) ? $page : 1;

	if ($tab == 'All' and $category == 'All')
		$page_size = null;
	
	$title = "Gallery"; 
	include 'templates/header.php'; 
?>
<body>
    <div id="outer-wrapper">
        <div id="inner-wrapper">
<?php include 'templates/nav.php'; ?>
            <div id="header">
                <h2>View Personas</h2>
                <h3>Your browser, your style! Dress it up with easy-to-change "skins" for your
                Firefox.</h3>
            </div>
            <div id="maincontent">
                <p id="breadcrumbs"><a href="http://www.getpersonas.com">Personas Home</a> : <a href="http://www.getpersonas.com/store/gallery/All/Popular">Gallery</a> : <?= $category ?><?php if ($tab != "All") { echo " : $tab"; } ?></p>
                <div id="gallery">
                    <ul>
<?php
			if ($category == 'Designer')
			{
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
				$list = $db->get_persona_by_author($user->get_username(), $category == 'All' ? null : $category);			
			}
			else
			{
				$start = ($page - 1) * $page_size;
				$list = $db->get_recent_personas($category == 'All' ? null : $category, $page_size, $start);
			}
			
			$description_max = 50;
			if (count($list) == 0)
			{
				echo "There are no personas available here. Please use the navigation on the left to choose another category.";
			}
			
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
                                <p class="designer"><strong>Designer:</strong> <?= $item['author'] ?></p>
                                <p class="added"><strong>Added:</strong> <?= $persona_date ?></p>
                                <p><?= $item_description ?></p>
                                <p><a href="<?= "/persona/" . $item['id'] ?>" class="view">view details »</a></p>
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
<?php 
			if ($tab == 'All' && $category != 'All')
			{
				$category_total = $db->get_personas_by_category_count($category);
				$pages = floor($category_total/$page_size) + 1;
				echo '<div id="pagination"><p>Page:</p>';
				echo "<ul>\n";
				if ($page > 1)
				{
					$url = "$url_prefix/$category/$tab/" . ($page - 1);
					echo "<li><a href=\"$url\">Previous</a></li>\n";
				}
				$i = 1;
				while ($i <= $pages)
				{
					if ($page == $i)
					{
						echo "<li class=\"current\">$i</li>\n";
					}
					else
					{
						echo "<li><a href=\"$url_prefix/$category/$tab/$i\">$i</a></li>\n";
					}
					$i++;
				}
				if ($page < $pages)
				{
					$url = "$url_prefix/$category/$tab/" . ($page + 1);
					echo "<li><a href=\"$url\">Next</a></li>\n";
				}
				echo "</ul>\n";
				echo "</div>\n";
			}
?>
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
