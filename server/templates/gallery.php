<?php
	include 'header.php'; 
?>
<body>
    <div id="outer-wrapper">
        <div id="inner-wrapper">
<?php include 'nav.php'; ?>
<?php
	if ($category == 'Designer' && file_exists("designer/$tab.php"))
	{
		include "designer/$tab.php";
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
			
			
			
			include 'pagination.php';
?>
					<ul>
<?php
			foreach ($list as &$persona)
			{
?>
                        <li class="gallery-item">
                            <div>
                                <h3><?= $persona['name'] ?></h3>
                                <div class="preview">
                                    <a href="/persona/<?= ($persona['id'] < 10 ? "0" : "") . $persona['id'] ?>"><img src="<?= $persona['preview_url'] ?>" alt="<?= $persona['name'] ?>" persona="<?= $persona['json'] ?>"/></a>
                                </div>
                                <p class="designer"><strong>Designer:</strong> <a href="/gallery/Designer/<?= $persona['author'] ?>"><?= $persona['display_username'] ?></a></p>
                                <p class="added"><strong>Added:</strong> <?= $persona['date'] ?></p>
                                <?php if($showDescription) { ?>
                                    <p><?= $persona['short_description'] ?></p>
                                <?php } ?>
                                <p><?= number_format($persona['popularity']) ?> active daily users</p>
                                <p><a href="<?= "/persona/" . ($persona['id'] < 10 ? "0" : "") . $persona['id'] ?>" class="view">view details »</a></p>
                                
                                <?php if($showWearThis) { ?>
                                    <p id="buttons">
                                        <a href="#" class="button try-button" persona="<?= $persona['json'] ?>"><span>try it now</span><span>&nbsp;</span></a>
                                    </p>
                                <?php } ?>
                                
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
                    <?php if($category == 'Designer')  echo '<p><script type="text/javascript" src="http://w.sharethis.com/button/sharethis.js#publisher=df86b16e-195c-4917-ae28-61a1382ba281&amp;type=website&amp;send_services=&amp;post_services=facebook%2Cdigg%2Cdelicious%2Cybuzz%2Ctwitter%2Cstumbleupon%2Creddit%2Ctechnorati%2Cmixx%2Cblogger%2Ctypepad%2Cwordpress%2Cgoogle_bmarks%2Cwindows_live%2Cmyspace%2Cfark%2Cbus_exchange%2Cpropeller%2Cnewsvine%2Clinkedin"></script></p>'; ?>                    
                </div>
<?php include 'pagination.php'; ?>
            </div>
<?php include 'category_nav.php'; ?>
        </div>
    </div>
<?php include 'footer.php'; ?>
    <script type="text/javascript" charset="utf-8">
        $(document).ready(function() {
           $("#gallery .preview img").previewPersona();
        });
        
        <?php if($showWearThis) { ?>
            $(".try-button").personasButton({
                'hasPersonas':'<span>wear this</span><span>&nbsp;</span>',
                'hasFirefox':'<span>get personas now!</span><span>&nbsp;</span>',
                'noFirefox':'<span>get personas with firefox</span><span>&nbsp;</span>'
            });
        <?php } ?>
    </script>
</body>
</html>
