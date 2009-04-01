<?php $title = "Forgot Your Password"; include 'header.php'; ?>
<body class="forgot-password">
    <div id="outer-wrapper">
        <div id="inner-wrapper">
<?php include 'nav.php'; ?>
            <div id="header">
                <h2>Forgot Your Password?</h2>
            </div>
            <div id="maincontent">
                <p id="breadcrumbs"><a href="http://www.getpersonas.com">Personas Home</a> : <a href="/signin">Sign In</a> : Forgot Your Password?</p>
<?php 
	if ($error)
		echo "<h4 class=\"error\">$error</h4>";
?>
                <h4>Please enter your Personas username below</h4>
                <form action="forgot_password" method="post">
                    <p>
                        <label for="username">Username:</label>
                        <input type="text" name="userreq" value="" id="username"/>
                    </p>
                    
                    <button type="submit" class="button"><span>continue</span><span class="arrow">&nbsp;</span></button>
                </form>
            </div>
            
        </div>
    </div>
    
   
<?php include 'footer.php'; ?>
</body>
</html>
