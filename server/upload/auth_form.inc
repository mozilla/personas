<!DOCTYPE HTML>
<html>
  <head>
    <title>Personas for Firefox</title>
    <style type="text/css" media="screen">
      @import "personas.css";
    </style>
  </head>

  <body>

<?php global $error; if ($error) { echo "<div class=\"error\">$error</div>"; } ?>

<form method=POST enctype='multipart/form-data' action="submit.php">

Username: <input type=text name="user">
<p>
Password: <input type=password name=pass>
<p>
<input type=submit>

</form>
  </body>

</html>
