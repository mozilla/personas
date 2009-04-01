<?php $title = "Success!"; include 'header.php'; ?>
<body>
    <div id="outer-wrapper">
        <div id="inner-wrapper">
<?php include 'nav.php'; ?>
            <div id="header">
                <h2>Create Your Own</h2>
                <h3>It’s easy to create your own Persona just follow the easy steps below!</h3>
            </div>
            <div id="maincontent" class="success">
                <div id="breadcrumbs">
                    <a href="http://www.getpersonas.com">Personas Home</a> : Create Your Own    
                </div>
                <h2>Success!</h2>
                <h3>You have successfully <?= $action_verb ? $action_verb : "added" ?> your Persona. Once it's approved, you'll be able to view it in the Gallery.</h3>
                <ul class="success-options">
                    <li><a href="http://www.getpersonas.com/store/gallery/All/Popular">View Personas Gallery »</a></li>
                </ul>
            </div>
            <div id="secondary-content">
              <ol id="upload-steps">
                  <li class="completed"> <!-- class="completed" needed to show green checkbox -->
                      <h3>Step 1:</h3>
                      <h4>Persona Agreement</h4>
                  </li>
                <li class="completed"> 
                    <h3>Step 2:</h3>
                    <h4>Create Your Persona</h4> 
                </li>
                
                <li class="completed">
                    <h3>Step 3:</h3>
                    <h4>Finish!</h4>
                </li>
              </ol>
            </div>
        </div>
    </div>
<?php include 'footer.php'; ?>
</body>
</html>
