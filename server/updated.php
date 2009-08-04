<?php
	require_once 'lib/personas_constants.php';	
	require_once 'lib/personas_functions.php';	
	require_once 'lib/storage.php';
	require_once 'lib/user.php';

	$db = new PersonaStorage();
	$user = new PersonaUser();

	$title = _("Updated"); 
	include 'templates/header.php'; 
?>
<body class="updated">
    <div id="outer-wrapper">
        <div id="inner-wrapper">
<?php include 'templates/nav.php'; ?>
            <div id="header">
                <h2><?= _("You've been updated to the latest version of Personas!");?></h2>

            </div>
            
<?php include 'templates/featured_personas.php'; ?>
<?php include 'templates/featured_designer.php'; ?>
<?php include 'templates/popular_personas.php'; ?>
            
        </div>
    </div>
<?php include 'templates/footer.php'; ?>
    <script type="text/javascript" charset="utf-8">
        $(document).ready(function () {
            $(".slideshow").slider();
        });
        $("img.persona").previewPersona(true);
    </script>
</body>
</html>
