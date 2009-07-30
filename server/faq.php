<?php
	require_once 'lib/personas_constants.php';
	require_once 'lib/user.php';	
	require_once "lib/language.php";

	$user = new PersonaUser();

	$title = _("Frequent Questions"); 
	include 'templates/header.php'; 
?>
<body>
    <div id="outer-wrapper">
        <div id="inner-wrapper">
<?php include 'templates/nav.php'; ?>
            <div id="header">
                <h2><?= _("Frequent Questions");?></h2>
                <h3><?= _("Personas are lightweight, easy to install and easy to change \"skins\" for your Firefox web browser.");?></h3>
            </div>
            <div id="maincontent" class="demo">
                <div id="breadcrumbs">
                    <?printf("<a href=\"%s\">" . _("Personas Home") . "</a> :  " . _("Frequent Questions"), $locale_conf->url('/'));?>
                </div>
               
                <dl>
                    <dt><?= _("What are Personas?");?></dt>
                    <dd>
                        <p><?= _("Personas are lightweight \"skins\" that change the look of your Firefox web browser. You can easily switch between Personas with just a few clicks. There are hundreds of Personas to choose from, with more added every day. You can even create your own and share it with others.");?></p>
                    </dd>
                    <dt><?= _("How do I add Personas to my Firefox?");?></dt>
                    <dd><p><?printf(_("In less than 60 seconds, you can install a Persona and transform the look of your Firefox web browser. Visit <a href=\"%s\">getpersonas.com</a> and click the download button. After installation, you will be asked to restart Firefox."), $locale_conf->url('/'));?></p>

                    <p><?printf(_("If you want to see how it works, you can watch a quick video demonstration <a href=\"%s\">here</a>."), $locale_conf->url('/demo_install'));?></p>

                    <p><?= _("Once Personas are installed, you'll be able to choose and change your selected Persona any time simply by clicking on the little fox mask in the lower left-hand corner of your browser window.");?></p></dd>
                        <dt><?= _("How do I create or edit my designer profile?");?></dt>
                           <dd>
                               <p><?printf(_("If you are a new Personas designer, you will be asked to create a profile when
                                  you first sign up <a href=\"%s\">here</a>. You will have the option to include a \"display
                                  name\" and \"designer description\", both of which will be displayed in the public
                                  gallery."), $locale_conf->url('/signin?return=/upload'));?></p>

                                  <p><?printf(_("If you are an existing Personas designer, you can edit and add to your profile <a href=\"%s\">here</a>."), $locale_conf->url('/profile'));?></p>

                           </dd>
                
                <dt><?= _("How can I add or change my current Persona?");?></dt>
                <dd><p><?= _("There are two easy ways to change your Persona:");?></p>
                    <ol>
                        <li><?printf(_("Visit the Personas website at %s, check out the gallery, choose your favorite Persona, and click on your choice to instantly change the look of your browser.<br> OR"), sprintf("<a href=\"%s\">GetPersonas.com</a>", $locale_conf->url('/')));?></li>
                       <li><?= _("Click on the little fox mask in the lower left hand corner of your browser, then select a Persona that suits your style.");?></li>
                    </ol>
                
               <dt><?= _("I can't seem to find Personas for Firefox after I downloaded it and installed it.  Where is it?");?></dt>
               <dd><p><?= _("Look for the little fox mask in the lower left-hand corner of your Firefox browser window.");?></p></dd>
               
               
               <dt><?= _("Can I create my own Persona?");?></dt>

               <dd><p><?printf(_("Absolutely! All you need to do is create two graphics files in your favorite graphics editing program (<em>e.g.</em>, Photoshop). To get started read more about how to <a href=\"%s\">create a Persona</a>."), $locale_conf->url('/demo_create'));?></p></dd>
               
			   <dt><a name="guidelines"></a><?= _("Are there any guidelines for content allowed in the Personas gallery?");?></dt>
    			<dd><p><?= _("Yes. We are big fans of creativity, but want to ensure that the art displayed in the Personas gallery meets a basic set of guidelines. Specifically:");?></p>
               
                   <ol>
                      <li><?= _("The logo, image, or art within your design is either your original work, or you are authorized to license and distribute it");?></li>
                      <li><?= _("Your design does not contain provocative, lewd, or sexual content (ie, it is PG-rated)");?></li>
                      <li><?= _("Your design does not include any identifiable non-celebrity person(s)");?></li>
                      <li><?= _("Your design does not contain violence or violent acts, nor does it exhibit discriminatory behavior or signs");?></li>
                      <li><?= _("Your design does not violate any applicable law, regulation or ordinance");?></li>
                      </ol></dd>
               
               <dt><?= _("After creating a Persona, can I edit the design?");?></dt>
               <dd><p><?printf(_("Yes. If at any point you need to edit your Persona design 
                after it has been submitted to the gallery, simply visit <a href=\"%s\">this link</a>, log in to
               your account, and go to the \"Personas Home : Gallery : All : My\" section. 
               From there, you can make changes to the settings you entered upon creation."), $locale_conf->url('/gallery/All/My'));?></p></dd>
               
               <dt><?= _("What's the maximum file size allowable for my persona?");?></dt>

               <dd><p><?= _("The persona that you upload may not exceed 300KB for the header or the footer
               image.");?></p></dd>
               
               

               <dt><?= _("Do I still retain ownership over Persona artwork that I upload to the Personas website?");?></dt>

               <dd><p><?= _("Yes.");?></p></dd> 


               <dt><?= _("What's the difference between a Firefox theme and Personas for Firefox?");?></dt>

               <dd><p><?= _("Personas allow you to \"skin\" the top and bottom areas of Firefox only (the header and footer of the browser chrome) without any change to the look of the navigation buttons or menus. With Personas, you can easily switch between many different lightweight skins with no further installation required.");?></p>

               <p><?= _("Like Personas, a Firefox theme is a type of Firefox add-on that extends the functionality of your browser and allows you to \"skin\" it in a variety of ways. However, unlike Personas, a theme changes the appearance of navigation buttons, toolbars and menus.");?></p></dd>


               <dt><?= _("If I have an existing Firefox theme installed, will Personas still work?");?></dt>

               <dd><p><?= _("Yes, Personas will work. However, it's strongly recommended that you uninstall a theme when using Personas. To disable a current Firefox theme, go to the \"Tools\" menu and select \"Add-ons\" to display the add-ons manager. Then click on the \"Themes\" button at the top of the add-ons manager window, click on the \"Default\" theme for Firefox, and click on \"Use theme\". After a quick restart, you'll be ready to dress up your browser with Personas.");?></p></dd>


               <dt><? _("Do I have to add my Persona to the public gallery?");?></dt>

               <dd><p><? _("No. When you upload your Persona at GetPersonas.com, you can choose to add it to the public gallery or keep it private. If you want to upload a custom Persona without visiting GetPersonas.com, you can do so anytime by following these steps:");?></p>

            <ol>            
               <li><?= _("Enable custom Personas in your version of Personas:");?>
                   <ul>
                       <li><?= _("Click on the little fox mask icon in the lower left-hand corner of your browser");?></li>
                          <li><?= _("Select \"Preferences...\"");?></li>
                          <li><?= _("Under \"Advanced\", select \"Show Custom Persona Menu\"");?></li>
                          <li><?= _("Close the preferences window");?></li>
                   </ul>
               </li>
               
               <li><?= _("Create your custom Persona:");?>
               <ul>
                 <li><?= _("Click on the little fox mask icon in the lower left-hand corner of your browser");?></li>
                 <li><?= _("Select \"Custom Persona\", then select \"Edit\" from its sub-menu");?></li>
                 <li><?= _("Specify a header, footer, text color and accent color for a Persona that’s locally stored on your computer");?></li>
                 <li><?= _("Your new Persona will be automatically selected, or you can manually choose it from the little fox mask menu");?></li>
                 </ul>
</ol>

               <dt><?= _("What kind of computer and operating system does Personas work with?");?></dt>

               <dd><p><?= _("Personas work with any type of computer that has Firefox installed. This includes Apple Mac, Linux and Windows platforms. You must have administrative rights to add Personas to your Firefox browser.");?></p></dd>


               <dt><?= _("How do I uninstall Personas for Firefox?");?></dt>

               <dd><p><?= _("If you've decided Personas doesn't work for you, you can uninstall with a few easy steps:");?></p>
                   <ol>
                      <li><?= _("Open the Add-ons dialog box by going to \"Tools->Add-ons\"");?></li>
                      <li><?= _("Click on the \"Extensions\" button on the top");?></li>
                      <li><?= _("Select \"Personas\"");?> </li>
                      <li><?= _("Click \"Uninstall\"");?></li>
                      <li><?= _("Restart Firefox");?></li>
                      </ol></dd>


               <dt><?= _("How do I provide feedback?");?> </dt>

               <dd><p><?= _("Personas is currently in beta, so we’re always looking for ways to improve the product. We’d love to hear what you think. Visit the <a href=\"https://labs.mozilla.com/forum/?CategoryID=18\">Personas</a> forums to send us your feedback.");?></p>



               <dt><?= _("Is Personas for Firefox open source?");?></dt>

               <dd><p><?= _("Yes. The source code for Personas is available under the MPL/GPL/LGPL tri-license. You can view the source <a href=\"http://hg.mozilla.org/labs/personas/personas\">here</a>.");?></p>
                   
                   
               
                   
                 
         </dd>       
      </dl>
                
            </div>
<?php include 'templates/get_personas.php'; ?>
        </div>
    </div>
<?php include 'templates/footer.php'; ?>
</body>
</html>
