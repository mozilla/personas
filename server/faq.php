<?php
	require_once 'lib/personas_constants.php';
	require_once 'lib/user.php';	


	$user = new PersonaUser();

	$title = "Frequent Questions"; 
	include 'templates/header.php'; 
?>
<body>
    <div id="outer-wrapper">
        <div id="inner-wrapper">
<?php include 'templates/nav.php'; ?>
            <div id="header">
                <h2>Frequent Questions</h2>
                <h3>Personas are lightweight, easy to install and easy to change “skins” for your Firefox web browser.</h3>
            </div>
            <div id="maincontent" class="demo">
                <div id="breadcrumbs">
                    <a href="http://www.getpersonas.com">Personas Home</a> :  Frequent Questions
                </div>
               
                <dl>
                    <dt>What are Personas?</dt>
                    <dd>
                        <p>Personas are lightweight “skins” that change the look of your Firefox web browser. You can easily switch between Personas with just a few clicks. There are hundreds of Personas to choose from, with more added every day. You can even create your own and share it with others.</p>
                    </dd>
                    <dt>How do I add Personas to my Firefox?</dt>
                    <dd><p>In less than 60 seconds, you can install a Persona and transform the look of your Firefox web browser. Visit <a href="http://getpersonas.com">GetPersonas.com</a> and click the download button. After installation, you will be asked to restart Firefox.</p>

                    <p>If you want to see how it works, you can watch a quick video demonstration <a href="/demo_install">here</a>.</p>

                    <p>Once Personas are installed, you’ll be able to choose and change your selected Persona any time simply by clicking on the little fox mask in the lower left-hand corner of your browser window.</p></dd>
                        <dt>How do I create or edit my designer profile?</dt>
                           <dd>
                               <p>If you are a new Personas designer, you will be asked to create a profile when
                                  you first sign up <a href="https://personas.services.mozilla.com/signin?return=/upload">here</a>. You will have the option to include a "display
                                  name" and "designer description", both of which will be displayed in the public
                                  gallery.</p>

                                  <p>If you are an existing Personas designer, you can edit and add to your profile
                                  <a href="https://personas.services.mozilla.com/profile">here</a>.</p>

                           </dd>
                
                <dt>How can I add or change my current Persona?</dt>
                <dd><p>There are two easy ways to change your Persona:</p>
                    <ol>
                        <li>Visit the Personas website at <a href="http://getpersonas.com">GetPersonas.com</a>, check out the gallery, choose your favorite Persona, and click on your choice to instantly change the look of your browser.<br> OR</li>
                       <li>Click on the little fox mask in the lower left hand corner of your browser, then select a Persona that suits your style.</li>
                    </ol>
                
               <dt>I can't seem to find Personas for Firefox after I downloaded it and installed it.  Where is it?</dt>
               <dd><p>Look for the little fox mask in the lower left-hand corner of your Firefox browser window.</p></dd>
               
               
               <dt>Can I create my own Persona?</dt>

               <dd><p>Absolutely! All you need to do is create two graphics files in your favorite graphics editing program (<em>e.g.</em>, Photoshop). To get started read more about how to <a href="/demo_create">create a Persona</a>.</p></dd>
               
			   <dt><a name="guidelines"></a>Are there any guidelines for content allowed in the Personas gallery?</dt>
    			<dd><p>Yes. We are big fans of creativity, but want to ensure that the art displayed in the Personas gallery meets a basic set of guidelines. Specifically:</p>
               
                   <ol>
                      <li>The logo, image, or art within your design is either your original work, or you are authorized to license and distribute it</li>
                      <li>Your design does not contain provocative, lewd, or sexual content (ie, it is PG-rated)</li>
                      <li>Your design does not include any identifiable non-celebrity person(s)</li>
                      <li>Your design does not contain violence or violent acts, nor does it exhibit discriminatory behavior or signs</li>
                      <li>Your design does not violate any applicable law, regulation or ordinance</li>
                      </ol></dd>
               
               <dt>After creating a Persona, can I edit the design?</dt>
               <dd><p>Yes. If at any point you need to edit your Persona design 
                after it has been submitted to the gallery, simply visit <a href="http://personas.services.mozilla.com/gallery/All/My">this link</a>, log in to
               your account, and go to the "Personas Home : Gallery : All : My" section. 
               From there, you can make changes to the settings you entered upon creation.</p></dd>
               
               <dt>What's the maximum file size allowable for my persona?</dt>

               <dd><p>The persona that you upload may not exceed 300KB for the header or the footer
               image.</p></dd>
               
               

               <dt>Do I still retain ownership over Persona artwork that I upload to the Personas website?</dt>

               <dd><p>Yes.</p></dd> 


               <dt>What’s the difference between a Firefox theme and Personas for Firefox?</dt>

               <dd><p>Personas allow you to “skin” the top and bottom areas of Firefox only (the header and footer of the browser chrome) without any change to the look of the navigation buttons or menus. With Personas, you can easily switch between many different lightweight skins with no further installation required.</p>

               <p>Like Personas, a Firefox theme is a type of Firefox add-on that extends the functionality of your browser and allows you to “skin” it in a variety of ways. However, unlike Personas, a theme changes the appearance of navigation buttons, toolbars and menus.  </p></dd>


               <dt>If I have an existing Firefox theme installed, will Personas still work?</dt>

               <dd><p>Yes, Personas will work. However, it’s strongly recommended that you uninstall a theme when using Personas. To disable a current Firefox theme, go to the “Tools” menu and select “Add-ons” to display the add-ons manager. Then click on the “Themes” button at the top of the add-ons manager window, click on the “Default” theme for Firefox, and click on “Use theme”. After a quick restart, you’ll be ready to dress up your browser with Personas.</p></dd>


               <dt>Do I have to add my Persona to the public gallery?</dt>

               <dd><p>No. When you upload your Persona at GetPersonas.com, you can choose to add it to the public gallery or keep it private. If you want to upload a custom Persona without visiting GetPersonas.com, you can do so anytime by following these steps:</p>

            <ol>            
               <li>Enable custom Personas in your version of Personas:
                   <ul>
                       <li>Click on the little fox mask icon in the lower left-hand corner of your browser</li>
                          <li>Select "Preferences..."</li>
                          <li>Under “Advanced”, select "Show Custom Persona Menu"</li>
                          <li>Close the preferences window</li>
                   </ul>
               </li>
               
               <li>Create your custom Persona:
               <ul>
                 <li>Click on the little fox mask icon in the lower left-hand corner of your browser</li>
                 <li>Select "Custom Persona", then select "Edit" from its sub-menu</li>
                 <li>Specify a header, footer, text color and accent color for a Persona that’s locally stored on your computer</li>
                 <li>Your new Persona will be automatically selected, or you can manually choose it from the little fox mask menu </li>
                 </ul>
</ol>

               <dt>What kind of computer and operating system does Personas work with?</dt>

               <dd><p>Personas work with any type of computer that has Firefox installed. This includes Apple Mac, Linux and Windows platforms. You must have administrative rights to add Personas to your Firefox browser.</p></dd>


               <dt>How do I uninstall Personas for Firefox?</dt>

               <dd><p>If you’ve decided Personas doesn’t work for you, you can uninstall with a few easy steps:</p>
                   <ol>
                      <li>Open the Add-ons dialog box by going to “Tools->Add-ons”</li>
                      <li>Click on the “Extensions” button on the top</li>
                      <li>Select “Personas” </li>
                      <li>Click "Uninstall"</li>
                      <li>Restart Firefox</li>
                      </ol></dd>


               <dt>How do I provide feedback? </dt>

               <dd><p>Personas is currently in beta, so we’re always looking for ways to improve the product. We’d love to hear what you think. Visit the <a href="https://labs.mozilla.com/forum/?CategoryID=18">Personas</a> forums to send us your feedback.</p>



               <dt>Is Personas for Firefox open source?</dt>

               <dd><p>Yes. The source code for Personas is available under the MPL/GPL/LGPL tri-license. You can view the source <a href="http://hg.mozilla.org/labs/personas/personas">here</a>.</p>
                   
                   
               
                   
                 
         </dd>       
      </dl>
                
            </div>
<?php include 'templates/get_personas.php'; ?>
        </div>
    </div>
<?php include 'templates/footer.php'; ?>
</body>
</html>
