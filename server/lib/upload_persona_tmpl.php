<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
            "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>Personas for Firefox | <?= $upload_submitted['id'] ? "Edit" : "Create" ?> Your Persona</title>
	<link href="/store/css/style.css" rel="stylesheet" type="text/css" media="all" />

</head>
<body>
    <div id="outer-wrapper">
        <div id="inner-wrapper">
            <p id="account"><a href="https://personas.services.mozilla.com/upload?action=logout">Logout</a></p>
            <div id="nav">
                <h1><a href="https://www.getpersonas.com/"><img src="/store/img/logo.png" alt="Mozilla Labs Personas"></a></h1>
                <ul>
                    <li class="gallery"><a href="https://www.getpersonas.com/store/gallery/All/Popular">Gallery</a></li>
                    <li class="create"><a href="https://personas.services.mozilla.com/upload" class="active">Create <br/>Your Own</a></li>
                    <li class="demo"><a href="https://www.getpersonas.com/store/demo_install.html">Demo</a></li>
                    <li class="faq"><a href="https://www.getpersonas.com/store/faq.html">Frequent <br/>Questions</a></li>
                </ul>
            </div>
            <div id="header">
                <h2>Create Your Persona</h2>
                <h3>Follow the easy steps below to start dressing up your browser!</h3>
            </div>
            <div id="maincontent">
                <p id="breadcrumbs"><a href="https://www.getpersonas.com">Personas Home</a> : <?= $upload_submitted['id'] ? "Edit" : "Create" ?> Your Own</p>
                
                <h4><?= $upload_submitted['id'] ? "Edit" : "Create" ?> Your Persona</h4>
                <form method="POST" action="upload" enctype='multipart/form-data'>
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
                    <button type="submit" class="button"><span>continue</span><span class="arrow">&nbsp;</span></button></p>
            	</form>
            </div>
            <div id="secondary-content">
              <ol id="upload-steps">
                <li class="completed"> <!-- class="completed" needed to show green checkbox --> 
                    <h3>Step 1:</h3>
                    <h4>Create Your Persona</h4>
                </li>
                <li class="current"> <!-- Active step requires 'current' classname and the extra wrapper div -->
                    <div class="wrapper">
                    	<h3>Step 2:</h3>
                    	<h4>Persona Agreement</h4>
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
    
    <script src="/store/js/jquery.js"></script>
    <script src="/store/js/script.js"></script>
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
    <div id="footer">
        <p>Copyright © 2009 Mozilla. Personas for Firefox is a Mozilla Labs Beta Project | <a href="http://labs.mozilla.com/about-labs/">About Mozilla Labs</a>    |  <a href="https://www.getpersonas.com/store/privacy.html">Privacy</a></p>
    </div>
</body>
</html>
