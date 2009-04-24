<?php $title = "Delete your Persona"; include 'header.php'; ?>
<body>
    <div id="outer-wrapper">
        <div id="inner-wrapper">
<?php include 'nav.php'; ?>
            <div id="header">
                <h2>Delete Your Persona</h2>
                <h3>Thanks for sharing your persona with us.</h3>
            </div>
            <div id="maincontent">
                <p id="breadcrumbs"><a href="http://www.getpersonas.com">Personas Home</a> : Delete Your Persona</p>
                
                <form method="POST" action="/delete/<?= $persona['id'] ?>" enctype='multipart/form-data'>
					<input type="hidden" name="id" value="<?= htmlspecialchars($persona['id']) ?>">
					<input type="hidden" name="confirm" value="1">
<?php include 'persona_detail.php' ?>
                	<p class="continue"><button type="submit" class="button"><span>confirm deletion</span><span class="arrow">&nbsp;</span></button></p>
            	</form>
            </div>
            <div id="secondary-content">
              <ol id="upload-steps">
                <li class="current">
                    <div class="wrapper">
                        <h3>Step 1:</h3>
                        <h4><?= $title ?></h4>
                    </div>
                </li>
                <li>
                    <h3>Step 2:</h3>
                    <h4>Finish</h4>
                </li>
              </ol>
            </div>
        </div>
    </div>
<?php include 'footer.php'; ?>
    <script type="text/javascript" charset="utf-8">
        $(document).ready(function () {
            $("#header").ie6Warning({"message":'<div id="ie6">Upgrade your browser to get the most out of this website. <a href="%LINK%">Download Firefox for free</a>.</div>'});
            $("#try-button").personasButton({
                                        'hasPersonas':'<span>wear this</span><span>&nbsp;</span>',
                                        'hasFirefox':'<span>get personas now!</span><span>&nbsp;</span>',
                                        'noFirefox':'<span>get personas with firefox</span><span>&nbsp;</span>'
                                        });
        });
    </script>
</body>
</html>
