<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
            "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>Personas for Firefox | Login</title>
	<link href="/store/css/style.css" rel="stylesheet" type="text/css" media="all" />
</head>
<body>
    <div id="outer-wrapper">
        <div id="inner-wrapper">
            <p id="account"><a href="https://personas.services.mozilla.com/upload">Designer Tools</a></p>
            <div id="nav">
                <h1><a href="https://www.getpersonas.com/"><img src="/store/img/logo.png" alt="Mozilla Labs Personas"></a></h1>
                <ul>
                    <li class="gallery"><a href="https://www.getpersonas.com/store/gallery/All/Popular">Gallery</a></li>
                    <li class="create"><a href="https://personas.services.mozilla.com/upload">Create <br/>Your Own</a></li>
                    <li class="demo"><a href="https://www.getpersonas.com/store/demo_install.html">Demo</a></li>
                    <li class="faq"><a href="https://www.getpersonas.com/store/faq.html">Frequent <br/>Questions</a></li>
                </ul>
            </div>
            <div id="header">
                <h2>Login</h2>
            </div>
            <div id="maincontent" class="login-signup">
                <div id="breadcrumbs">
                    Personas Home : Login    
                </div>
                <div id="login">
                    <h4>Already a Personas Designer?</h3>
                    <form action="<?= $_SERVER['SCRIPT_NAME'] ?>" method="post">
                        <p><label for="login_user">Username</label>
                        <input type="text" name="login_user" value="" id="" <?php if (array_key_exists('login_user', $this->_errors)) echo 'class="error"' ?> />
                        <?php if (array_key_exists('login_user', $this->_errors)) echo '<span class="error-message">' . $this->_errors['login_user'] . '</span>' ?>
                        </p>
                        
                        <p><label for="login_pass">Password</label>
                        <input type="password" name="login_pass" value="" id="" />
                        <span class="extra-info">Minimum 6 characters</span>
                        </p>
                        
                        <p><label for="login_remember"><input type="checkbox" name="login_remember" id="remember" value="1" /> Remember me on this computer</label></p>
                        <button type="submit" class="button"><span>sign in</span><span class="arrow"></span></button>
                        
                        <p class="forgot"><a href="/forgot_password">Forgot your password?</a></p>
                    </form>
                </div>
<?php if (!$this->_no_signup)    
		{
?>
                <div id="signup">
                    <h4>New Personas Designer?</h3>
                    <form action="<?= $_SERVER['SCRIPT_NAME'] ?>" method="post">
                    <p><label for="email">Email</label>
                    <input type="text" name="create_email" value="" id="" <?php if (array_key_exists('create_email', $this->_errors)) echo 'class="error"' ?>/>
					<?php if (array_key_exists('create_email', $this->_errors)) echo '<span class="error-message">' . $this->_errors['create_email'] . '</span>' ?>
                    </p>
                    
                    <p><label for="username">Username</label>
                    <input type="text" name="create_username" value="" id="" <?php if (array_key_exists('create_username', $this->_errors)) echo 'class="error"' ?>/>
					<?php if (array_key_exists('create_username', $this->_errors)) echo '<span class="error-message">' . $this->_errors['create_username'] . '</span>' ?>
                    </p>
                    
                    <p><label for="password">Password</label>
                    <input type="password" name="create_password" value="" id="" <?php if (array_key_exists('create_password', $this->_errors)) echo 'class="error"' ?>/>
					<?php if (array_key_exists('create_password', $this->_errors)) echo '<span class="error-message">' . $this->_errors['create_password'] . '</span>' ?>
                    </p>
                    
                    <p><label for="password_confirm">Confirm Password</label>
                    <input type="password" name="create_passconf" value="" id="" <?php if (array_key_exists('create_passconf', $this->_errors)) echo 'class="error"' ?>/>
					<?php if (array_key_exists('create_passconf', $this->_errors)) echo '<span class="error-message">' . $this->_errors['create_passconf'] . '</span>' ?>
                    </p>
                    
                    <p class="news"><label for="news"><input type="checkbox" name="news" id="news" value="" /> I’d like to receive news and information about Personas</label></p>
                    
            
                    <div id="captcha">
                        <script type="text/javascript">
                                var RecaptchaOptions = {
                                   theme : 'clean',
                                   tabindex : 11
                                };
                                </script>

                                    <script type="text/javascript" src="https://api-secure.recaptcha.net/challenge?k=<?= RECAPTCHA_PUBLIC_KEY ?>"></script>

                        	<noscript>
                          		<iframe src="https://api-secure.recaptcha.net/noscript?k=<?= RECAPTCHA_PUBLIC_KEY ?>" height="300" width="500" frameborder="0"></iframe><br/>
                          		<textarea name="recaptcha_challenge_field" rows="3" cols="40"></textarea>
                          		<input type="hidden" name="recaptcha_response_field" value="manual_challenge"/>
                        	</noscript>        
							<?php if (array_key_exists('captcha', $this->_errors)) echo '<span class="error-message">' . $this->_errors['captcha'] . '</span>' ?>

                        
                    </div>
                    
                    <button type="submit" class="button"><span>sign me up</span><span class="arrow"></span></button>
                    </form>
                </div>
			<p class="disclaimer">Mozilla values your privacy. We will not sell or rent your email address</p>
<?php 
		} 
?>
            </div>
        </div>
    </div>
    <script src="js/jquery.js"></script>
    <script src="js/script.js"></script>
    <div id="footer">
        <p>Copyright © 2009 Mozilla. Personas for Firefox is a Mozilla Labs Beta Project | <a href="http://labs.mozilla.com/about-labs/">About Mozilla Labs</a>    |  <a href="https://www.getpersonas.com/store/privacy.html">Privacy</a></p>
    </div>
</body>
</html>
