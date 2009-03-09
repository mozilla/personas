<?php 
	require_once 'lib/personas_constants.php';	
	require_once 'lib/storage.php';
	
	function url_prefix($id)
	{
		$second_folder = $id%10;
		$first_folder = ($id%100 - $second_folder)/10;
		return  $first_folder . '/' . $second_folder .  '/'. $id . '/';
	}

	function extract_record_data($item)
	{
		$padded_id = $item{'id'} < 10 ? '0' . $item{'id'} : $item{'id'};
		$extracted = array('id' => $item{'id'}, 
						'name' => $item{'name'},
						'accentcolor' => $item{'accentcolor'} ? '#' . $item{'accentcolor'} : null,
						'textcolor' => $item{'textcolor'} ? '#' . $item{'textcolor'} : null,
						'header' => url_prefix($item{'id'}) . $item{'header'}, 
						'footer' => url_prefix($item{'id'}) . $item{'footer'});
		return $extracted;	
	}

	$page_size = 21;

	$db = new PersonaStorage();
	$categories = $db->get_categories();
	array_unshift($categories, 'All');
	$tabs = array('Popular', 'Recent', 'All');
	
	$path = array_key_exists('PATH_INFO', $_SERVER) ? $_SERVER['PATH_INFO'] : '/';
	$path = substr($path, 1); #chop the lead slash
	list($category, $tab, $page) = explode('/', $path.'//');


	$category = $category && in_array(ucfirst($category), $categories) ? ucfirst($category) : "All";
	$tab = $tab && in_array(ucfirst($tab), $tabs) ? ucfirst($tab) : 'Popular';
	$page = $page && is_numeric($page) ? $page : 1;


	
?>


<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
            "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>View Personas</title>
	<link href="/store/css/style.css" rel="stylesheet" type="text/css" media="all" />
</head>
<body>
    <div id="outer-wrapper">
        <div id="inner-wrapper">
            <p id="account"><a href="#">Designer Tools</a></p>
            <div id="nav">
                <h1><a href="#"><img src="/store/img/logo.png" alt="Mozilla Labs Personas"></a></h1>
                <ul>
                    <li><a href="#" class="active">View <br/>Personas</a></li>
                    <li><a href="#">Create <br/>Your Own</a></li>
                    <li><a href="#">Watch <br/> Our Demo</a></li>
                    <li><a href="#">Frequent <br/>Questions</a></li>
                </ul>
            </div>
            <div id="header">
                <h2>View Personas</h2>
                <h3>Personas are lightweight, easy to install and easy to change "skins" for your Firefox web browser.</h3>
            </div>
            <div id="maincontent">
                <p id="breadcrumbs">Personas Home : Gallery : <?= $category ?><?php if ($tab != "All") { echo " : $tab"; } ?></p>
                <div id="gallery">
                    <ul>
<?php
			if ($tab == 'Recent')
			{
				$list = $db->get_recent_personas($category == 'All' ? null : $category, $page_size);
			}
			elseif ($tab == 'Popular')
			{
				$list = $db->get_popular_personas($category == 'All' ? null : $category, $page_size);			
			}
			else
			{
				$start = ($page - 1) * $page_size;
				$list = $db->get_popular_personas($category == 'All' ? null : $category, $page_size, $start);
			}
			
			foreach ($list as $item)
			{
				$preview_url = PERSONAS_LIVE_PREFIX . '/' . url_prefix($item['id']) . "preview.jpg";
				$persona_json = htmlentities(json_encode(extract_record_data($item)));
				$persona_date = date("n/j/Y", strtotime($item['approve']));
?>
                        <li class="gallery-item">
                            <div>
                                <h3><?= $item['name'] ?></h3>
                                <div class="preview">
                                    <img src="<?= $preview_url ?>" alt="<?= $item['name'] ?>" persona="<?= $persona_json ?>"/>
                                </div>
                                <p class="designer"><strong>Designer:</strong> <?= $item['author'] ?></p>
                                <p class="added"><strong>Added:</strong> <?= $persona_date ?></p>
                                <p>Lorem ipsum dolor sit amet, cons ectetur adipiscing elit vel nib eget magna.</p>
                                <p><a href="#" class="view">view details »</a></p>
                            </div>
                        </li>
 <?php
 			}
 ?>
                    </ul>
                </div>
<?php 
			if ($tab == 'All')
			{
				$category_total = $db->get_personas_by_category_count($category);
				$pages = floor($category_total/$page_size) + 1;
				echo '<div id="pagination"><p>Page:</p>';
				echo "<ul>\n";
				if ($page > 1)
				{
					$url = "/store/gallery/$category/$tab/" . ($page - 1);
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
						echo "<li><a href=\"/store/gallery/$category/$tab/$i\">$i</a></li>\n";
					}
					$i++;
				}
				if ($page < $pages)
				{
					$url = "/store/gallery/$category/$tab/" . ($page + 1);
					echo "<li><a href=\"$url\">Next</a></li>\n";
				}
				echo "</ul>\n";
				echo "</div>\n";
			}
?>
            </div>
	<div id="secondary-content">
                <ul id="subnav">
<?php
			foreach ($categories as $list_category)
			{
				$category_url = "/store/gallery/$list_category";
				if ($list_category == $category)
				{
					echo "		<li class=\"active\">$list_category\n";
					echo "            <ul>\n";
					foreach ($tabs as $list_tab)
					{
						if ($list_tab == 'All' && $list_category == 'All')
							continue;
						$tab_url = "/store/gallery/$list_category/$list_tab";
						echo "		<li";
						if ($list_tab == $tab)
							echo ' class="active"';
						if ($list_tab == 'All')
							$tab_url .= "/1";
						echo "><a href=\"$tab_url\">$list_tab</a></li>\n";						
					}
					echo "                        </ul></li>\n";
				}
				else
				{
					echo "		<li><a href=\"$category_url/Popular\">$list_category</a></li>";
				}
			}
?>
                </ul>
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
        <p>Copyright © <?= date("Y") ?> Mozilla. Personas is a Mozilla Labs Project.  |  Terms of Use  |  Privacy</p>
    </div>
</body>
</html>
