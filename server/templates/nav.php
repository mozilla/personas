            <p id="account">
<?php 
	if(!array_key_exists('no_my', $_GET) && $user->get_unauthed_username())
	{
		echo 'Welcome, ' . $user->get_unauthed_username() . ' | <a href="/profile' . $_SERVER['SCRIPT_URL'] . '">Edit Your Profile</a> | <a href="/signin?action=signout&return=' . $_SERVER['SCRIPT_URL'] . '">Sign Out</a>';
	}
	elseif(!array_key_exists('no_my', $_GET))
	{
		if (array_key_exists('signout_success', $_GET))
			echo "You have been signed out | ";
		echo '<a href="/signin?return=' . $_SERVER['SCRIPT_URL'] . '">Sign In</a>';
	}
?>
			</p>
            <div id="nav">
                <h1><a href="/"><img src="/static/img/logo.png" alt="Mozilla Labs Personas"></a></h1>
                
                <?php if(isset($showCheckItOut) && $showCheckItOut) { ?>
                    <div id="check-it-out">
                        <div class="hd">
                            &nbsp;
                        </div>
                        <p class="bd">
                            Check it out! Your browser's all dressed up.                        
                        </p>

                        <div class="ft">
                            &nbsp;
                        </div>
                    </div>
                <?php } ?>
                <?php if(!(isset($hidenav) && $hideNav)) { ?>
                    <ul>
                        <li class="gallery"><a href="/gallery/All/Popular">Gallery</a></li>
                        <li class="create"><a href="/upload">Create <br/>Your Own</a></li>
                        <li class="demo"><a href="/demo_create">How To</a></li>
                        <li class="faq"><a href="/faq">Frequent <br/>Questions</a></li>
                    </ul>
                <?php } ?>
            </div>
