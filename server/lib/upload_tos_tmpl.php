<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
            "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>Create Your Persona</title>
	<link href="../store/css/style.css" rel="stylesheet" type="text/css" media="all" />

</head>
<body>
    <div id="outer-wrapper">
        <div id="inner-wrapper">
            <p id="account"><a href="#">Designer Tools</a></p>
            <div id="nav">
                <h1><a href="#"><img src="../store/img/logo.png" alt="Mozilla Labs Personas"></a></h1>
                <ul>
                    <li class="gallery"><a href="#">Gallery</a></li>
                    <li class="create"><a href="#">Create <br/>Your Own</a></li>
                    <li class="demo"><a href="#">Demo</a></li>
                    <li class="faq"><a href="#">Frequent <br/>Questions</a></li>
                </ul>
            </div>
            <div id="header">
                <h2>Create Your Persona</h2>
                <h3>Follow the easy steps below to start dressing up your browser!</h3>
            </div>
            <div id="maincontent">
                <p id="breadcrumbs">Personas Home : Create Your Own</p>
                
                <h4>Terms of Service</h4>
               <form action="upload" method="post">
               <input type="hidden" name="firstterms" value="1">
                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam pharetra pellentesque tellus. Quisque facilisis. Nullam accumsan tellus ut nisi. Etiam ut diam a pede iaculis lobortis. Ut eget orci. Integer viverra orci in purus. Sed semper enim in urna. Etiam imperdiet, augue eu ultricies imperdiet, turpis sapien pretium velit, non facilisis odio ipsum ut arcu. Aliquam eget odio at diam rutrum accumsan. Sed vitae tellus. Nam quis nisl ac quam dictum auctor. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam pharetra pellentesque tellus. Quisque facilisis. Nullam accumsan tellus ut nisi. Etiam ut diam a pede iaculis lobortis. Ut eget orci. Integer viverra orci in purus. Sed semper enim in urna. Etiam imperdiet, augue eu ultricies imperdiet, turpis sapien pretium velit, non facilisis odio ipsum ut arcu. Aliquam eget odio at diam rutrum accumsan. Sed vitae tellus. Nam quis nisl ac quam dictum auctor. </p>

                           <label class="agree" for="agree"><input type="checkbox" name="agree" value="1" id="agree" /> I agree to the user agreement</label>
<?php if (array_key_exists('agree', $upload_errors)) echo '<span class="error-message">' . $upload_errors['agree'] . '</span>' ?>
              
                   
                   
                   <h4>How Would You Like to Share Your Personas Design?</h4>
                   <p>We encourage you to make your design publicly available, though you may choose not to submit your design under an open source license.  Please select the option below that you prefer. </p>
                   
                   <div id="license-options">
                       <p><label for="license-cc"><input type="radio" name="license" value="cc" id="license-cc" />Yes, I want to make my design available to everyone under a Creative Commons license.</label></p>

                          <ul>
                           <li>   People may share and modify my Persona as long as they give me credit and don’t charge for it.  <a href="http://creativecommons.org/licenses/by-nc-sa/3.0/us/">Learn more.</a></li>
                              <li>My design will be quickly added to the directory and hosted for free.</li>
                          </ul>

                          <p><label for="license-restricted"><input type="radio" name="license" value="restricted" id="license-restricted" />I’d rather restrict any modifications and sharing of my Personas design.</label></p>

                          <ul>
                           <li>   People may not share, modify, or distribute my design outside of the Personas application.
                           </li>
                           <li>I would like a free 30-day trial, after which point I will be charged a fee to help Mozilla sustain the program at no cost to users.  Full details will be sent via email as the trial period is expiring.</li>
                          </ul>
<?php if (array_key_exists('license', $upload_errors)) echo '<span class="error-message">' . $upload_errors['license'] . '</span>' ?>
                    
                   </div>
                   
                   <button type="submit" class="button"><span>continue</span><span class="arrow">&nbsp;</span></button>
                   
                   
                   
               </form> 
               
               
            </div>
            <div id="secondary-content">
              <ol id="upload-steps">
                <li class="current"> <!-- Active step requires 'current' classname and the extra wrapper div -->
                    <div class="wrapper">
                        <h3>Step 1:</h3>
                        <h4>Terms of Service</h4>
                    </div> 
                </li>
                <li> <!-- class="completed" needed to show green checkbox -->
                    <h3>Step 2:</h3>
                    <h4>Create Your Persona</h4>
                </li>
                <li>
                    <h3>Step 3:</h3>
                    <h4>Finish!</h4>
                </li>
              </ol>
            </div>
        </div>
    </div>
    
   
    <div id="footer">
        <p>Copyright © 2009 Mozilla. Personas is a Mozilla Labs Project.  |  Terms of Use  |  Privacy</p>
    </div>
</body>
</html>
