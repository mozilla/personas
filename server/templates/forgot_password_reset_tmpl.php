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
                <h4>Enter your new password:</h4>
                
                <form action="forgot_password" method="post">
                <input type=hidden name="user" value="<?= $username ?>">
                <input type=hidden name="code" value="<?= $code ?>">
                 <p>
                        <label for="password">New password</label>
                        <input type="password" name="password" value="" id="password" />
                    </p>
                    
                    <p>
                        <label for="password-verify">Re-type your new password</label>
                        <input type="password" name="password-verify" value="" id="" />
                    </p>
                    
                    <button type="submit" class="button"><span>reset password</span><span class="arrow">&nbsp;</span></button>
                    
                </form>
            </div>
        </div>
    </div>
<?php include 'footer.php'; ?>
</body>
</html>
