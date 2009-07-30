<?php
	require_once 'lib/personas_constants.php';
	require_once 'lib/user.php';	

	$user = new PersonaUser();
	$title = _("Privacy Policy"); 
	include 'templates/header.php'; 
?>
<body>
    <div id="outer-wrapper">
        <div id="inner-wrapper">
<?php include 'templates/nav.php'; ?>
            <div id="header">
                <h2>Privacy Policy</h2>
                
            </div>
            <div id="maincontent" class="demo">
                <div id="breadcrumbs">
                    <?printf("<a href=\"%s\">" . _("Personas Home") . "</a> :  " . _("Privacy Policy"), $locale_conf->url('/'));?>
                </div>
               
               
                <h3><?= _("Mozilla Personas Privacy Policy");?></h3>
                <p><?= _("Dated: March 20, 2009");?></p>
                <p><?= _("We're working hard to protect your privacy while delivering products and services that bring you the performance and protection you desire in your personal computing.  This privacy policy explains how Mozilla Corporation (\"Mozilla\"), a wholly-owned subsidiary of the non-profit Mozilla Foundation, collects and uses information about users of the official Mozilla Personas add-on (\"Personas\") for Mozilla Firefox® web browser (\"Firefox\").  It does not apply to other Mozilla websites, products or services.");?>  </p>
                <h4><?= _("Types of Information");?></h4>
                <p><?= _("Personas collects certain information that falls into the following categories:");?></p>
                <p><?= _("<em>\"Personal Information\"</em> is information that you provide to us that personally identifies you, such as your name and email address.  Except as described below, Mozilla does not collect or require users of Personas to furnish Personal Information.  To protect your privacy any Personal Information will be available only to Mozilla employees, contractors, and selected contributors who signed confidentiality agreements that prohibit them from using or disclosing such information other than for internal Mozilla purposes.");?></p>
                <p><?= _("<em>\"Non-Personal Information\"</em> is information that cannot be directly associated with a specific person or entity.  Non-Personal Information includes but is not limited to your computer's configuration, the Persona Design (as defined below) and the version of Personas you use.");?></p>
                <p><?= _("<em>\"Potentially Personal Information\"</em> is information that is Non-Personal Information in and of itself but that could be used in conjunction with other information to personally identify you.  For example, Internet Protocol (\"IP\") addresses (the addresses of computers on the internet), which is Non-Personal Information in and of itself, could be Personal Information when combined with internet service provider (\"ISP\") records.");?></p>
                <p><?= _("<em>\"Aggregate Data\"</em> is information that is recorded about users and collected into groups so it no longer reflects or references an individually identifiable user.  Aggregate Data does not contain any Personal Information.");?></p>
                <h4><?= _("Personal Information");?></h4>
                <p><?= _("<em>Downloading Personas.</em>  Mozilla does not collect any Personal Information if you are downloading Personas.");?></p>
                <p><?= _("<em>Creating a Custom Persona.</em>  If you are creating a Custom Persona for your own use, Mozilla does not collect any Personal Information.");?></p>
                <p><?= _("<em>Contributing a Design to the Personas Gallery.</em> The Personas gallery is where you can browse all the available designs.  If you contribute a design or image (each a \"Persona Design\") to the Personas gallery, Mozilla collects the following Personal Information: (1) your user name and (2) your email address.  Your user name will be used to attribute your Persona Design to you and will be publicly available on the Personas gallery.  You do not have to provide your real name; you can use a nickname or avatar.  Your email address will not be publicly available or shared with any third parties.  Mozilla will use your email address only to contact you regarding your design or to provide any additional information that you elect or opt in to receive.");?></p>
                <p><?= _("In addition, Mozilla does not publicly release information gathered in connection with commercial transactions (i.e., transactions involving money).");?> </p>
                <p><?= _("Mozilla does not make publicly available Personal Information that it specifically promises at the time of collection to maintain in confidence.");?> </p>
                <h4><?= _("Interactive Product Features");?></h4>
                <p><?= _("If you have Personas installed, each time you open Firefox Personas loads your selected Persona Design from the Mozilla server.  Once per day Personas checks to see if your selected Persona Design still is available in its list of Persona Designs.  This feature sends the same information that web browsers typically transfer with any HTTP requests including user agent and, Potentially Personally-Identifying Information such as, your IP address.");?> </p>
                <p><?= _("Personas has a feature that refreshes the Personas gallery once per day.  This feature sends the following Non-Personal Information to Mozilla: ");?></p>
                <ul>
                <li><?= _("the category and ID of Persona Design selected;");?></li>
                <li><?= _("the date/time the Persona Design was selected;");?></li>
                <li><?= _("the ID and version of the application you used (e.g., Firefox 3.0.7);");?></li>
                <li><?= _("your locale (e.g., English-US);");?></li>
                <li><?= _("your operating system (i.e.,  Linux, Windows or Mac); and");?></li>
                <li><?= _("your computer's architecture.");?></li>
                </ul>
                <p><?= _("Personas relies on Firefox’s automatic update feature for updates.  See the <a href=\"http://www.mozilla.com/legal/privacy/firefox-en.html\">Firefox Privacy Policy</a> for privacy details of the Firefox automatic update feature. ");?></p>
                <p><?= _("We use this information to improve our products and services and to support decision making regarding feature and capacity planning.  Mozilla is an open organization that believes in sharing as much information as possible about its products, its operations, and its associations. Accordingly, we may release public reports containing Aggregate Data so that our global community and Personas partners may make better product and design decisions and so that users of Personas will know which are the most popular Persona Designs and Personas designers will know how many times their Persona Design was downloaded.  ");?></p>
                <h4><?= _("Privacy Policy Changes");?></h4>
                
                <p><?printf("Mozilla may change the Personas Privacy Policy from time to time.  Any and all changes will be reflected on this page.  When Mozilla changes this policy in a material way, a notice will be posted on the <a href=\"%s\">www.getpersonas.com</a> Web site.  Substantive changes may also be announced through the standard mechanisms by which Mozilla communicates with its users and community, including Mozilla's \"announce\" <a href=\"https://lists.mozilla.org/listinfo/announce\">mailing list and newsgroup</a>.  It is your responsibility to ensure that you understand the terms of this Privacy Policy.  You should periodically check this page for any changes to the current policy.", $locale_conf->url('/'));?></p>
                <h4><?= _("For More Information");?></h4>
                <p><?= _("If you have questions about this privacy policy, please contact Mozilla at <a href=\"mailto:privacy@mozilla.com\">privacy@mozilla.com</a>.  In your email, please identify the specific product or policy about which you have questions.");?></p>
                
            </div>
<?php include 'templates/get_personas.php'; ?>
        </div>
    </div>
<?php include 'templates/footer.php'; ?>
</body>
</html>
