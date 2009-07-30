                <div id="signup">
                    <h4><?= _("Change User Details");?></h3>
                    <form action="profile" method="post">
					<input type="hidden" name="update" value=1>
                    <p><label for="username"><?= _("Login Name:");?> <?= $user->get_username() ?></label>
                    
                    </p>
                    
                    <p><label for="email"><?= _("Email");?></label>
                    <input type="text" name="create_email" value="<?= $create['email'] ?>" id="" <?php if (array_key_exists('create_email', $_errors)) echo 'class="error"' ?>/>
					<?php if (array_key_exists('create_email', $_errors)) echo '<span class="error-message">' . $_errors['create_email'] . '</span>' ?>
                    </p>
                    
                    <p><label for="username"><?= _("Display Username*");?></label>
                    <input type="text" name="create_display_username" value="<?= $create['display_username'] ?>" id="" <?php if (array_key_exists('create_display_username', $_errors)) echo 'class="error"' ?>/>
					<?php if (array_key_exists('create_display_username', $_errors)) echo '<span class="error-message">' . $_errors['create_display_username'] . '</span>' ?>
                    </p>
                    
                     <p>
                        <label for="description"><?= _("Designer Description*");?></label>
                        <textarea name="create_description" id="create_description" <?php if (array_key_exists('create_description', $_errors)) echo 'class="error"' ?> ><?= $create['description'] ?></textarea>
                        <?php if (array_key_exists('create_description', $_errors)) echo '<span class="error-message">' . $_errors['create_description'] . '</span>' ?>
                     </p>

                   <p class="news"><label for="news"><input type="checkbox" name="news" id="news" value="" <?= $create['news'] ? "checked" : "" ?>/><?= _("I'd like to receive news and information about Personas");?></label></p>
                    <p><?= _("* <i>denotes an optional field. These entries will be displayed in the personas gallery.</i>");?></p>
                    <button type="submit" class="button"><span>change</span><span class="arrow"></span></button>
                    </form>
                </div>
			<p class="disclaimer"><?= _("Mozilla values your privacy. We will not sell or rent your email address.");?></p>
