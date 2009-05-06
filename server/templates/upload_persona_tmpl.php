<?php $title = ($upload_submitted['id'] ? "Edit" : "Create") . " your Persona"; include 'header.php'; ?>
<body>
    <div id="outer-wrapper">
        <div id="inner-wrapper">
<?php include 'nav.php'; ?>
            <div id="header">
                <h2>Create Your Persona</h2>
                <h3>Follow the easy steps below to start dressing up your browser!</h3>
            </div>
            <div id="maincontent">
                <p id="breadcrumbs"><a href="http://www.getpersonas.com">Personas Home</a> : <?= $upload_submitted['id'] ? "Edit" : "Create" ?> Your Own</p>
                
                <h4><?= $upload_submitted['id'] ? "Edit" : "Create" ?> Your Persona</h4>
                <form method="POST" action="/upload" enctype='multipart/form-data'>
				<input type="hidden" name="agree" value="<?= htmlspecialchars($upload_submitted['agree']) ?>">
				<input type="hidden" name="license" value="<?= htmlspecialchars($upload_submitted['license']) ?>">
				<input type="hidden" name="id" value="<?= htmlspecialchars($upload_submitted['id']) ?>">
                <div id="create-part-1">
                    <p>
                        <label for="persona-name">Persona Name</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($upload_submitted['name']) ?>" id="name" <?php if (array_key_exists('name', $upload_errors)) echo 'class="error"' ?> />
                        <?php if (array_key_exists('name', $upload_errors)) echo '<span class="error-message">' . $upload_errors['name'] . '</span>' ?>
                     </p>
                    
                    <p>
                        <label for="textcolor">Text Color</label>
                        <input type="text" name="textcolor" value="<?= htmlspecialchars($upload_submitted['textcolor']) ?>" id="textcolor"/ <?php if (array_key_exists('textcolor', $upload_errors)) echo 'class="error"' ?>>
                         <?php if (array_key_exists('textcolor', $upload_errors)) echo '<span class="error-message">' . $upload_errors['textcolor'] . '</span>' ?>
                    </p>
                    
                    <p>
                        <label for="header-image">Header Image</label>
                        <span><input type="file" name="header-image" value="" id="header-image"  <?php if (array_key_exists('header-image', $upload_errors)) echo 'class="error"' ?> /></span>
                        <?php if (array_key_exists('header-image', $upload_errors)) echo '<span class="error-message">' . $upload_errors['header-image'] . '</span>' ?>
                    </p>
                    
                    <p>
                        <label for="description">Description</label>
                        <textarea name="description" id="description" <?php if (array_key_exists('desription', $upload_errors)) echo 'class="error"' ?> ><?= $upload_submitted['description'] ?></textarea>
                        <?php if (array_key_exists('description', $upload_errors)) echo '<span class="error-message">' . $upload_errors['description'] . '</span>' ?>
                     </p>
                </div>
                
                <div id="create-part-2">
                    <p>
                        <label for="category">Category</label>
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
                        <label for="accentcolor">Accent Color</label>
                        <input type="text" name="accentcolor" value="<?= htmlspecialchars($upload_submitted['accentcolor']) ?>" id="accentcolor"/ <?php if (array_key_exists('accentcolor', $upload_errors)) echo 'class="error"' ?> >
                        <?php if (array_key_exists('accentcolor', $upload_errors)) echo '<span class="error-message">' . $upload_errors['accentcolor'] . '</span>' ?>
                     </p>
                    
                    <p>
                        <label for="footer-image">Footer Image</label>
                        <span><input type="file" name="footer-image" value="" id="footer-image" <?php if (array_key_exists('footer-image', $upload_errors)) echo 'class="error"' ?> /></span>
                        <?php if (array_key_exists('footer-image', $upload_errors)) echo '<span class="error-message">' . $upload_errors['footer-image'] . '</span>' ?>
                     </p>
                    
                    <p>
                        <label for="reason">I'm creating a Persona to...</label>
                        <span>
                            <select name="reason" id="reason">
<?php
		$reasons = Array("" => "", "fun" => "have some fun", "build" => "build a brand", "non-profit" => "support a non-profit cause", "other" => "other");
		foreach ($reasons as $reason => $longreason)
		{
			print "<option value=\"$reason\"";
			if ($reason == $upload_submitted['reason'])
				echo " selected";
			print ">$longreason</option>\n";
		}
?>
                            </select>
                         <?php if (array_key_exists('reason', $upload_errors)) echo '<span class="error-message">' . $upload_errors['reason'] . '</span>' ?>
                        </span>
                    </p>
                    
                    <p id="other-info">
                        <label for="other-reason">Reason:</label>
                        <input id="other-reason" name="other-reason" type="text" value="<?= htmlspecialchars($upload_submitted['other-reason']) ?>"/>
                        <?php if (array_key_exists('other-reason', $upload_errors)) echo '<span class="error-message">' . $upload_errors['other-reason'] . '</span>' ?>
                   </p>
                    
                </div>
                
                <p class="continue">
                    <button type="submit" class="button"><span>submit</span><span class="arrow">&nbsp;</span></button></p>
            	</form>
            </div>
            <div id="secondary-content">
              <ol id="upload-steps">
                   <li class="completed">                         
                          	<h3>Step 1:</h3>
                          	<h4>Terms of Service</h4>
                      </li>
                <li class="current">
                    <div class="wrapper">
                        <h3>Step 2:</h3>
                        <h4><?= $title ?></h4>
                    </div>
                </li>
               
                <li>
                    <h3>Step 3:</h3>
                    <h4>Finish!</h4>
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
