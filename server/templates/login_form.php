                <div id="login">
                    <h4>Already a Personas Designer?</h4>
                    <form action="signin" method="post">
<?php
					if ($return_url)
						echo "<input type=hidden name=return value=\"$return_url\">";
?>
						<p><label for="login_user">Username</label>
                        <input type="text" name="login_user" value="" id="" <?php if (array_key_exists('login_user', $_errors)) echo 'class="error"' ?> />
                        <?php if (array_key_exists('login_user', $_errors)) echo '<span class="error-message">' . $_errors['login_user'] . '</span>' ?>
                        </p>
                        
                        <p><label for="login_pass">Password</label>
                        <input type="password" name="login_pass" value="" id="" <?php if (array_key_exists('login_user', $_errors)) echo 'class="error"' ?> />
                        <span class="extra-info">Minimum 6 characters</span>
                        </p>
                        
                        <p><label for="login_remember"><input type="checkbox" name="login_remember" id="remember" value="1" /> Remember me on this computer</label></p>
                        <button type="submit" class="button"><span>sign in</span><span class="arrow"></span></button>
                        
                        <p class="forgot"><a href="/forgot_password">Forgot your password?</a></p>
                    </form>
                </div>
