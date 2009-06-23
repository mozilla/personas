<?php 
			if ($tab == 'All')
			{
				if (!isset($category_total))
					$category_total = $db->get_active_persona_count($category);
				
				if ($category_total > PERSONA_GALLERY_ALL_PAGE_SIZE)
				{
					$pages = floor($category_total/PERSONA_GALLERY_ALL_PAGE_SIZE) + 1;
					
					$floor = $page - 4;
					if ($floor < 1)
						$floor = 1;
					$ceiling = $page + 4;
					if ($ceiling > $pages)
						$ceiling = $pages;
						
					echo '<div id="pagination"><p>Page:</p>';
					echo "<ul>\n";
					if ($page > 1)
					{
						$url = "$url_prefix/$category/$tab/" . ($page - 1);
						echo "<li><a href=\"$url\">Previous</a></li>\n";
					}
					$i = $floor;
					if ($floor > 1)
					{
						echo "<li><a href=\"$url_prefix/$category/$tab/1\">1</a></li>\n";
					}
					if ($floor > 2)
					{
						echo "<li><a href=\"$url_prefix/$category/$tab/" . ($floor - 1) . "\">...</a></li>\n";
					}
					while ($i <= $ceiling)
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
					if ($ceiling + 1< $pages)
					{
						echo "<li><a href=\"$url_prefix/$category/$tab/" . ($ceiling + 1) . "\">...</a></li>\n";
					}
					if ($ceiling < $pages)
					{
						echo "<li><a href=\"$url_prefix/$category/$tab/$pages\">$pages</a></li>\n";
					}
					if ($page < $pages)
					{
						$url = "$url_prefix/$category/$tab/" . ($page + 1);
						echo "<li><a href=\"$url\">Next</a></li>\n";
					}
					echo "</ul>\n";
					echo "</div>\n";
				}
			}
?>
<p>