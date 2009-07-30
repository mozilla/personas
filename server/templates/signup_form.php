                <div id="signup">
                    <h4><?= _("New Personas Designer?");?></h3>
                    <form action="signin" method="post">
<?php
					if ($return_url)
						echo "<input type=hidden name=return value=\"$return_url\">";
?>
                    <p><label for="email"><?= _("Email");?></label>
                    <input type="text" name="create_email" value="<?= $create['email'] ?>" id="" <?php if (array_key_exists('create_email', $_errors)) echo 'class="error"' ?>/>
					<?php if (array_key_exists('create_email', $_errors)) echo '<span class="error-message">' . $_errors['create_email'] . '</span>' ?>
                    </p>
                    
                    <p><label for="username"><?= _("Login Name");?></label>
                    <input type="text" name="create_username" value="<?= $create['username'] ?>" id="" <?php if (array_key_exists('create_username', $_errors)) echo 'class="error"' ?>/>
					<?php if (array_key_exists('create_username', $_errors)) echo '<span class="error-message">' . $_errors['create_username'] . '</span>' ?>
                    </p>
                    
                    <p><label for="username"><?= _("Display Username*");?></label>
                    <input type="text" name="create_display_username" value="<?= $create['display_username'] ?>" id="" <?php if (array_key_exists('create_display_username', $_errors)) echo 'class="error"' ?>/>
					<?php if (array_key_exists('create_display_username', $_errors)) echo '<span class="error-message">' . $_errors['create_display_username'] . '</span>' ?>
                    </p>
                    
                    <p><label for="password"><?= _("Password");?></label>
                    <input type="password" name="create_password" value="" id="" <?php if (array_key_exists('create_password', $_errors)) echo 'class="error"' ?>/>
					<?php if (array_key_exists('create_password', $_errors)) echo '<span class="error-message">' . $_errors['create_password'] . '</span>' ?>
                    </p>
                    
                    <p><label for="password_confirm"><?= _("Confirm Password");?></label>
                    <input type="password" name="create_passconf" value="" id="" <?php if (array_key_exists('create_passconf', $_errors)) echo 'class="error"' ?>/>
					<?php if (array_key_exists('create_passconf', $_errors)) echo '<span class="error-message">' . $_errors['create_passconf'] . '</span>' ?>
                    </p>
                    
                     <p>
                        <label for="description"><?= _("Designer Description*");?></label>
                        <textarea name="create_description" id="create_description" <?php if (array_key_exists('create_description', $_errors)) echo 'class="error"' ?> ><?= $create['description'] ?></textarea>
                        <?php if (array_key_exists('create_description', $_errors)) echo '<span class="error-message">' . $_errors['create_description'] . '</span>' ?>
                     </p>

                   <p class="news"><label for="news"><input type="checkbox" name="news" id="news" value="" <?= $create['news'] ? "checked" : "" ?>/><?= _("I'd like to receive news and information about Personas");?></label></p>
                    
            
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
						<?php if (array_key_exists('captcha', $_errors)) echo '<span class="error-message">' . $_errors['captcha'] . '</span>' ?>
                    </div>
                    <p><?= _("* <i>denotes an optional field. These entries will be displayed in the personas gallery.</i>");?></p>
                    <button type="submit" class="button"><span><?= _("sign me up");?></span><span class="arrow"></span></button>
                    </form>
                </div>
			<p class="disclaimer"><?= _("Mozilla values your privacy. We will not sell or rent your email address.");?></p>
