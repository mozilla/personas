            <p id="account">
<?php 
	if(!array_key_exists('no_my', $_GET) && $user->get_unauthed_username())
	{
		echo 'Welcome, ' . $user->get_unauthed_username() . ' | <a href="/signin?action=signout&return=' . $_SERVER['SCRIPT_NAME'] . '">Sign Out</a>';
	}
	elseif(!array_key_exists('no_my', $_GET))
	{
		if (array_key_exists('signout_success', $_GET))
			echo "You have been signed_out | ";
		echo '<a href="/signin?return=' . $_SERVER['SCRIPT_NAME'] . '">Sign In</a>';
	}
?>
			</p>
            <div id="nav">
                <h1><a href="/"><img src="/store/img/logo.png" alt="Mozilla Labs Personas"></a></h1>
                <ul>
                    <li class="gallery"><a href="/gallery/All/Popular">Gallery</a></li>
                    <li class="create"><a href="/upload">Create <br/>Your Own</a></li>
                    <li class="demo"><a href="/demo_install/">Demo</a></li>
                    <li class="faq"><a href="/faq/">Frequent <br/>Questions</a></li>
                </ul>
            </div>
