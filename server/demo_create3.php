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
                <h2>How To</h2>
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
                				<td class="tut_left"><a href="/demo_create">Step 1: Creating a Persona Header Image</a> &raquo;</td>
                				<td class="tut_leftOFF">Step 3: Testing your Persona Images</td>
                			</tr>
                			<tr>
                				<td class="tut_left"><a href="/demo_create_2">Step 2: Creating a Persona Footer Image</a> &raquo;</td>
                				<td class="tut_left"><a href="/demo_create_4">Step 4: Submit your Persona!</a> &raquo;</td>
                			</tr>
                		</table>

                	<!-- STEP 3 -->

                		<a name="test"></a>

                		<div class="tut_Box">

                			<div class="tut_step"><span class="tut_Title">Step 3: Testing your Persona Images</span></div>

                			<div class="tut_Copy">

                			<p>In general, designs that feature rich content areas in the top-right corner of the browser work
                			best. Though that may be true, you should always check to see where the UI elements sit on top of
                			your designs within the different platform versions of Firefox. <p>This may be a critical step in
                			finalizing your image, depending on the importance of the visual information you are including in
                			your designs.

                			<br><br><div class="tut_SubTitle">Option 1: Test on Your Own Computer</div> 

                			<p>Within the Personas menu in the bottom left of the browser's status bar, you can enable an "offline" Persona on your own personal computer by enabling a setting within Preferences.
                			In doing this, you can test your Personas before submitting them to the online catalog.  

                			<p>Follow these four steps to get the Custom Personas option up and running in your browser:

                			</div>

                			<div class="tut_info"><img src="/static/img/information-64.png" class="tut_icon">

                					<ol>

                					<li>If you have Personas installed, click on the little fox on the bottom left
                	of your computer screen and click on "Preferences"</li>

                					<li>Ensure the box "Show Custom Persona in Menu" is
                	checked and close the box.</li>

                					<li>Click on the little fox again. Mouse over "Custom" in the menu and to the
                	right find and click "Edit"</li>

                					<li>Build your Persona using the upload fields and additional settings.</li>

                					</ol>


                			</div>


                			<div class="tut_Image"><img src="/static/img/tut_custom_1.jpg" border="0"><br>
                			<div class="tut_Image"><img src="/static/img/tut_custom_2.jpg" border="0"><br>

                			<div class="tut_warning"><img src="/static/img/warning-64.png" class="tut_icon">

                					<ul>

                					<li>Once your images are playing nice with the UI for all the OS flavors of Firefox, save final copies (PNG or JPG) -
                			but be sure to check to ensure they don't exceed 300k in filesize!. (Note: This will only test your Persona on the platform you are currently using)</li>

                					</ul>

                			</div>		

                			<br><br><div class="tut_SubTitle">Option 2: Cross-Platform Photoshop PSD Header Template</div> 

                			<div class="tut_Copy">

                			We've created a positioning template that can be used to help figure out placement of your
                			artwork. The template is structured to allow testing of your Persona header within OSX, Windows XP and Windows Vista
                			flavors of the browser. </div>

                			<div class="link">Download the Personas Header Template:  <a
                			href="/static/img/Persona_Header_TEMPLATE.psd"><img class="button" src="/static/img/tut_btn_download.gif"
                			border="0"></a></div>

                			<div class="tut_Copy">

                			The key to using this PSD template is to simply layer your Persona header image underneath one of
                			the three OS layers.  Be sure to turn off any of the OS layers you aren't using, as they will
                			overlap each other due to their transparency.</div>

                			<div class="tut_Image center"><img src="/static/img/tut_PSpalette.jpg" border="0"><br><span
                			class="caption">Photoshop overlay layerset</span></div>

                			<div class="tut_warning"><img src="/static/img/warning-64.png" class="tut_icon">

                					<ul>

                					<li>Once you turn on an OS layer, you will be able to see where the UI elements will sit on top of
                			your designs and you can flag any conflicts that may arise.</li>

                					<li>Once your images are playing nice with the UI for all the OS flavors of Firefox, save final copies (PNG or JPG) -
                			but be sure to check to ensure they don't exceed 300k in filesize!</li>

                					</ul>

                			</div>


                		</div>

                	</div>

                		<table class="tut_nav">
                				<tr>
                					<td>
                						<div class="tut_left"><b>&laquo; <a href="/demo_create_2">Back to Step 2</a></b></div>
                					</td>
                					<td>
                						<div class="tut_right"><b><a href="/demo_create_4">Continue to Step 4</a> &raquo;</b></div>
                					</td>
                				</tr>
                		</table>

                	</div>

                		<br><br>

                <!-- RESOURCES -->

                		<div class="tut_didyouknow"><img src="/static/img/question-64.png" class="tut_icon"><p>Did you know you can test a Persona before you submit it?  <b><a href="/demo_create_3#test">Find out how!</a> &raquo;</b></div>


                		<div class="tut_info"><img src="/static/img/information-64.png" class="tut_icon">

                		<p><span class="tut_TitleInfo">Online Image Editor Resources</span><br><br>

                		<ul>

                		<li><a href="http://www.sumopaint.com">SUMOPaint</a> -  SUMO Paint offers professional and easy to use tools for creating and editing images within a browser. SUMO Paint is a free image editing software that gives you the opportunity to create, edit and comment images online with powerful tools and layer support. </li>
                		<li><a href="http://www.photoshop.com">Photoshop.com</a> - Tweak, rotate and touch up photoswith Photoshop&reg; Express, your free online photo editor.</li>
                		<li><a href="http://aviary.com/home">Aviary Phoenix</a> - All the Photoshop features you actually need, at a fraction of the price. Did we mention built-in collaboration? Create and edit with the world!</li>

                		</ul>

                		</div>		

                </div>
         
         
            </div>
<?php include 'templates/get_personas.php'; ?>
        </div>
    </div>
<?php include 'templates/footer.php'; ?>
</body>
</html>
