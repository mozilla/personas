<?php $title = ($upload_submitted['id'] ? _("Edit Your Persona") : _("Create Your Persona")); include 'header.php'; ?>
<body>
    <div id="outer-wrapper">
        <div id="inner-wrapper">
<?php include 'nav.php'; ?>
            <div id="header">
                <h2><?= _("Create Your Persona");?></h2>
                <h3><?= ("Follow the easy steps below to start dressing up your browser!");?></h3>
            </div>
            <div id="maincontent">
                <p id="breadcrumbs"><?printf("<a href=\"%s\">" . _("Personas Home") . "</a> : %s", $locale_conf->url('/'), ($upload_submitted['id'] ? _("Edit Your Own") : _("Create Your Own")));?></p>
                
                <h4><?= $upload_submitted['id'] ? _("Edit Your Persona") : _("Create Your Persona") ?></h4>
<?php if ($override_error) { ?>
                <p class="description"><?= $override_error ?></p>
<?php } else { ?>
                <form method="POST" action="/upload" enctype='multipart/form-data'>
				<input type="hidden" name="agree" value="<?= htmlspecialchars($upload_submitted['agree']) ?>">
				<input type="hidden" name="license" value="<?= htmlspecialchars($upload_submitted['license']) ?>">
				<input type="hidden" name="id" value="<?= htmlspecialchars($upload_submitted['id']) ?>">
                <div id="create-part-1">
                    <p>
                        <label for="persona-name"><?= _("Persona Name");?></label>
                        <input type="text" name="name" value="<?= htmlspecialchars($upload_submitted['name']) ?>" id="name" <?php if (array_key_exists('name', $upload_errors)) echo 'class="error"' ?> />
                        <?php if (array_key_exists('name', $upload_errors)) echo '<span class="error-message">' . $upload_errors['name'] . '</span>' ?>
                     </p>
                    
                    <p>
                        <label for="textcolor"><?= _("Text Color");?></label>
                        <input type="text" name="textcolor" value="<?= htmlspecialchars($upload_submitted['textcolor']) ?>" id="textcolor"/ <?php if (array_key_exists('textcolor', $upload_errors)) echo 'class="error"' ?>>
                         <?php if (array_key_exists('textcolor', $upload_errors)) echo '<span class="error-message">' . $upload_errors['textcolor'] . '</span>' ?>
                    </p>
                    
                    <p>
                        <label for="header-image"><?= _("Header Image");?></label>
                        <span><input type="file" name="header-image" value="" id="header-image"  <?php if (array_key_exists('header-image', $upload_errors)) echo 'class="error"' ?> /></span>
                        <?php if (array_key_exists('header-image', $upload_errors)) echo '<span class="error-message">' . $upload_errors['header-image'] . '</span>' ?>
                    </p>
                    
                    <p>
                        <label for="description"><?= _("Description");?></label>
                        <textarea name="description" id="description" <?php if (array_key_exists('description', $upload_errors)) echo 'class="error"' ?> ><?= $upload_submitted['description'] ?></textarea>
                        <?php if (array_key_exists('description', $upload_errors)) echo '<span class="error-message">' . $upload_errors['description'] . '</span>' ?>
                     </p>
                </div>
                
                <div id="create-part-2">
                    <p>
                        <label for="category"><?= _("Category");?></label>
                        <select name="category" id="category">
<?php
		echo '<option value=""';
		if ($upload_submitted['category'] == "")
			echo " selected";
		echo "></option>\n";
		
		foreach ($categories as $category)
		{
			echo "<option value=\"$category\"";
			if ($category == $upload_submitted['category'])
				echo " selected";
			echo ">$category</option>\n";
		} 
?>
						</select>
                        <?php if (array_key_exists('category', $upload_errors)) echo '<span class="error-message">' . $upload_errors['category'] . '</span>' ?>
                    </p>
                    <p>
                        <label for="accentcolor"><?= _("Accent Color");?></label>
                        <input type="text" name="accentcolor" value="<?= htmlspecialchars($upload_submitted['accentcolor']) ?>" id="accentcolor"/ <?php if (array_key_exists('accentcolor', $upload_errors)) echo 'class="error"' ?> >
                        <?php if (array_key_exists('accentcolor', $upload_errors)) echo '<span class="error-message">' . $upload_errors['accentcolor'] . '</span>' ?>
                     </p>
                    
                    <p>
                        <label for="footer-image"><?= _("Footer Image");?></label>
                        <span><input type="file" name="footer-image" value="" id="footer-image" <?php if (array_key_exists('footer-image', $upload_errors)) echo 'class="error"' ?> /></span>
                        <?php if (array_key_exists('footer-image', $upload_errors)) echo '<span class="error-message">' . $upload_errors['footer-image'] . '</span>' ?>
                     </p>
                    
                    <p>
                        <label for="reason"><?= _("I'm creating a Persona to...");?></label>
                        <span>
                            <select name="reason" id="reason">
<?php
		$reasons = Array("" => "", "fun" => _("have some fun"), "build" => _("build a brand"), "non-profit" => _("support a non-profit cause"), "other" => _("other"));
		foreach ($reasons as $reason => $longreason)
		{
			print "<option value=\"$reason\"";
			if ($reason == $upload_submitted['reason'])
				echo _(" selected");
			print ">$longreason</option>\n";
		}
?>
                            </select>
                         <?php if (array_key_exists('reason', $upload_errors)) echo '<span class="error-message">' . $upload_errors['reason'] . '</span>' ?>
                        </span>
                    </p>
                    
                    <p id="other-info">
                        <label for="other-reason"><?= _("Reason:");?></label>
                        <input id="other-reason" name="other-reason" type="text" value="<?= htmlspecialchars($upload_submitted['other-reason']) ?>"/>
                        <?php if (array_key_exists('other-reason', $upload_errors)) echo '<span class="error-message">' . $upload_errors['other-reason'] . '</span>' ?>
                   </p>
                    
                </div>
                
                <p class="continue">
                    <button type="submit" class="button"><span><?= _("submit");?></span><span class="arrow">&nbsp;</span></button></p>
                <p><?printf(_("By clicking submit I affirm that I am the rightful owner of this content (see <a href=\"%s\" target=\"_blank\">guidelines</a>) and understand that this design will be publicly available in the Gallery upon approval."), $locale_conf->url('/faq#guidelines'));?></p>
            	</form>
 <?php } ?>
           </div>
			<div id="secondary-content">
              <ol id="upload-steps">
                   <li class="completed">                         
                          	<h3><?= _("Step 1:");?></h3>
                          	<h4><?= _("Terms of Service");?></h4>
                      </li>
                <li class="current">
                    <div class="wrapper">
                        <h3><?= _("Step 2:");?></h3>
                        <h4><?= $title ?></h4>
                    </div>
                </li>
               
                <li>
                    <h3><?= _("Step 3:");?></h3>
                    <h4><?= _("Finish!");?></h4>
                </li>
              </ol>
            </div>
        </div>
    </div>
<?php include 'footer.php'; ?>
   <script type="text/javascript" charset="utf-8">
    $('#textcolor').ColorPicker({
    	onSubmit: function(hsb, hex, rgb) {
    		$('#textcolor').val(hex);
    	},
    	onBeforeShow: function () {
    		$(this).ColorPickerSetColor(this.value);
    	},
    	onChange: function (hsb, hex, rgb) {
        		$('#textcolor').val(hex);
        	}
        
    })
    .bind('keyup', function(){
    	$(this).ColorPickerSetColor(this.value);
    });
    
    
    $('#accentcolor').ColorPicker({
    	onSubmit: function(hsb, hex, rgb) {
    		$('#accentcolor').val(hex);
    	},
    	onBeforeShow: function () {
    		$(this).ColorPickerSetColor(this.value);
    	},
    	onChange: function (hsb, hex, rgb) {
        		$('#accentcolor').val(hex);
        	}
        
    })
    .bind('keyup', function(){
    	$(this).ColorPickerSetColor(this.value);
    });
    
     $("#reason").change(function() {
        if($(this)[0].selectedIndex == 4) {
            $("#other-info").show();
        } else {
            $("#other-info").hide();
        }
    });
  </script>
</body>
</html>
