<!DOCTYPE HTML>
<html>
  <head>
    <title>Personas for Firefox</title>
	<script type="text/javascript" src="include/jquery.js"></script>
	<script type="text/javascript" src="include/farbtastic.js"></script>
	<link rel="stylesheet" href="include/farbtastic.css" type="text/css" />
	<link rel="stylesheet" type="text/css" href="/store/css/personas.css" />
</head>

  <body>
<script type="text/javascript">
$(document).ready(
	function()
	{
    	$('#colorpicker').farbtastic('#textcolor');
    	$('#colorpicker2').farbtastic('#accentcolor');
  	
  		$('#cp1').toggle(function() 
  		{
    		$('#colorpicker').slideDown("slow");
    		return false;
  		},
  		function() 
  		{
    		$('#colorpicker').slideUp("slow");
    		return false;
  		});

  		$('#cp2').toggle(function() 
  		{
    		$('#colorpicker2').slideDown("slow");
    		return false;
  		},
  		function() 
  		{
    		$('#colorpicker2').slideUp("slow");
    		return false;
  		});
  	}
);

</script>


<div class="subtitle"><?= $form_title ?></div>
<?php if ($error) { echo "<div class=\"error\">$error</div>"; } ?>
<form method=POST enctype='multipart/form-data' action="submit.php">
<b>Welcome <?php echo $auth_user ?></b> <input type=submit name="logout" value="not <?php echo $auth_user ?>?">
</form>
<p>

<form method=POST enctype='multipart/form-data' action="submit.php">
<input type=hidden name='id' value='<?= $form_id ?>'>

Persona Title: <input type=text name="name" maxlength=25 value='<?= htmlspecialchars($form_name) ?>'>
<p>
Category: <select name="category">
<?php 
	foreach ($categories as $category)
	{
		print "<option";
		if ($category == $form_category) { print " selected"; }
		print ">$category</option>";
	}
?>
</select>
<p>
Text Color (optional): <input type="text" id="textcolor" name="textcolor" value='<?= $form_text ?>'>
<input type="submit" id="cp1" value="toggle text color picker" />
<div id="colorpicker" align="left" style="display:none;"></div>
<p>
Accent Color (optional): <input type="text" id="accentcolor" name="accentcolor" value='<?= $form_accent ?>'>
<input type="submit" id="cp2" value="toggle accent color picker" />
<div id="colorpicker2" align="left" style="display:none"></div>
<p>
Header<?php if ($form_id) {?> (optional) <?php } ?>: <input type=file name=header>
<br>(Recommended: 3000px wide, 200px tall)<p>
Footer<?php if ($form_id) {?> (optional) <?php } ?>: <input type=file name=footer>
<br>(Recommended: 3000px wide, 100px tall)<p>
<input type=submit>

</form>
<p>By uploading your Persona to this site, you agree that
 the following are true:</p>
 <ul>
   <li>you have the right to distribute this Persona,
   including any rights required for material that may be
   trademarked or copyrighted by someone else; and</li>
   <li>if any information about the user or usage of this
   Persona is collected or transmitted outside of the user's
   computer, the details of this collection will be provided
   in the description of the software, and you will provide a
   link to a privacy policy detailing how the information is
   managed and protected; and</li>
   <li>your Persona may be removed from the site,
   re-categorized, have its description or other information
   changed, or otherwise have its listing changed or removed,
   at the sole discretion of Mozilla and its authorized
   agents; and</li>
   <li>the descriptions and other data you provide about the
   Persona are true to the best of your knowledge.</li>
 </ul>
  </body>

</html>