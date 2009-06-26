			<div id="secondary-content">
                <ul id="subnav">
<?php
			foreach ($categories as $list_category)
			{
				$category_url = "$url_prefix/$list_category";
				if ($list_category == $category)
				{
					if ($tabs)
					{
						echo "		<li class=\"active\">$list_category\n";
						echo "            <ul>\n";
						foreach ($tabs as $list_tab)
						{
							$tab_url = "$url_prefix/$list_category/$list_tab";
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
						echo "		<li class=\"active\"><a href=\"$category_url/Popular\">$list_category</a></li>\n";
					}
				}
				else
				{
					echo "		<li><a href=\"$category_url/Popular\">$list_category</a></li>";
				}
			}
?>
                </ul>
            </div>
