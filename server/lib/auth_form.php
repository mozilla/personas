<!DOCTYPE HTML>
<html>
  <head>
    <title>Personas for Firefox</title>
	<link rel="stylesheet" type="text/css" href="/personas/store/css/personas.css" />
  </head>

  <body>

<div class="subtitle">Log In</div>
<?php global $error; if ($error) { echo "<div class=\"error\">$error</div>"; } ?>

<form method=POST enctype='multipart/form-data' action="<?= $_SERVER['REQUEST_URI'] ?>">

Username: <input type=text name="user">
<p>
Password: <input type=password name=pass>
<p>
<input type=submit value="Log In">

</form>
<p><a href="/personas/upload/user.php">Create a personas account</a>
  </body>

</html>
