<?php
	require_once 'lib/personas_constants.php';
	require_once 'lib/user.php';	


	$user = new PersonaUser();
	$title = "How to Create Personas"; 
	include 'templates/header.php'; 
?>
<body>
    <div id="outer-wrapper">
        <div id="inner-wrapper">
<?php include 'templates/nav.php'; ?>
            <div id="header">
                <h2>Demo</h2>
                <h3>Personas are lightweight, easy to install and easy to change “skins” for your Firefox web browser.</h3>
            </div>
            <div id="maincontent" class="demo">
                <div id="breadcrumbs">
                    <a href="http://www.getpersonas.com">Personas Home</a> : How to Create Personas
                </div>
                

                
                
                
                	<div id="tutorial">

                		<div class="tut_Header">How to Create Personas</div>

                		<div class="tut_Copy">

                		<p>Personas are made up of two graphic image files - a "header" image and a "footer" image - which skin
                			the default Firefox UI background.

                		</div>

                		<table class="tut_nav">
                			<tr>
                				<td class="tut_leftOFF">Step 1: Creating a Persona Header Image</td>
                				<td class="tut_left"><a href="/demo_create_3">Step 3: Testing your Persona Images</a> &raquo;</td>
                			</tr>
                			<tr>
                				<td class="tut_left"><a href="/demo_create_2">Step 2: Creating a Persona Footer Image</a> &raquo;</td>
                				<td class="tut_left"><a href="/demo_create_4">Step 4: Submit your Persona!</a> &raquo;</td>
                			</tr>
                		</table>


                	<!-- STEP 1 -->

                		<div class="tut_Box">

                			<div class="tut_step"><span class="tut_Title">Step 1: Creating a Persona Header Image</span></div>

                			<div class="tut_Copy">

                			<p>The header image is displayed as the background of the top of the browser window, nestling in behind the toolbars, address bar, search bar and the tab strip.  
                			It will be <b>anchored to the top-right corner</b> of the browser window.

                			<img class="tut_Image centerImg" src="/static/img/tut_XPheader.jpg">

                			<ul>

                			<li>View a sample Persona Header <b><a href="/static/img/Persona_Header_LABS.jpg">here</a></b>.</li>

                			<li>View the sample Persona Header as seen in <b><a href="/static/img/tut_XPheader.jpg">XP</a></b>, <b><a href="/static/img/tut_VISTAheader.jpg">Vista</a></b>, and <b><a href="/static/img/tut_OSXheader.jpg">OSX</a></b>.</li>

                			</ul>

                			<br>

                			</div>

                			<div class="tut_info"><img src="/static/img/do-64.png" class="tut_icon">

                				<ul>

                				<li>Dimensions should be <b>3000px wide x 200px high</b></li>

                				<li>PNG or JPG file format</li>

                				<li>subtle, soft contrast images and gradients work best</li>

                				</ul>

                			</div>

                			<div class="tut_warning"><img src="/static/img/warning-64.png" class="tut_icon">

                				<ul>

                				<li>highly detailed images will compete with the browser UI</li>

                				<li>Firefox may reveal more of the lower portion of the image if it or an extension adds another toolbar or other UI
                			element to the top of the window</li>

                				<li>the right-hand side of the image should have the most important information - as a user
                				increases the width of the browser window, the browser reveals more of the left-hand side of the
                				image</li>

                				</ul>

                			</div>

                			<div class="tut_alert"><img src="/static/img/donot-64.png" class="tut_icon">

                				<ul>

                				<li>images must be no larger than 300kb in filesize</li>

                				<li>images over 3000px x 200px will not be approved</li>

                				<li>never use artwork/logos/photography that you do not have the legal rights to use - you will have to prove you have the rights if the content is questioned</li>

                				</ul>

                			</div>

                			<table class="tut_nav">
                				<tr>
                					<td></td>
                					<td>
                						<div class="tut_right"><b><a href="/demo_create_2">Continue to Step 2</a> &raquo;</b></div>
                					</td>
                				</tr>
                			</table>

                		</div>

                		<br><br>

                <!-- RESOURCES -->

                		<div class="tut_didyouknow"><img src="/static/img/question-64.png" class="tut_icon"><p>Did you know you can test a Persona before you submit it?  <b><a href="/demo_create_3#test">Find out how!</a>&raquo;</b></div>

                		<div class="tut_info"><img src="/static/img/information-64.png" class="tut_icon">

                		<p><span class="tut_TitleInfo">Online Image Editor Resources</span><br><br>

                		<ul>

                		<li><a href="http://www.sumopaint.com">SUMOPaint</a> -  SUMO Paint offers professional and easy to use tools for creating and editing images within a browser. SUMO Paint is a free image editing software that gives you the opportunity to create, edit and comment images online with powerful tools and layer support. </li>
                		<li><a href="http://www.photoshop.com">Photoshop.com</a> - Tweak, rotate and touch up photoswith Photoshop&reg; Express, your free online photo editor.</li>
                		<li><a href="http://aviary.com/home">Aviary Phoenix</a> - All the Photoshop features you actually need, at a fraction of the price. Did we mention built-in collaboration? Create and edit with the world!</li>

                		</ul>

                		</div>		</div>

                
                
         
         
            </div>
<?php include 'templates/get_personas.php'; ?>
        </div>
    </div>
<?php include 'templates/footer.php'; ?>
</body>
</html>
