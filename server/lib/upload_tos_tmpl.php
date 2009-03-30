<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
            "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>Personas for Firefox | Create Your Persona</title>
	<link href="/store/css/style.css" rel="stylesheet" type="text/css" media="all" />

</head>
<body>
    <div id="outer-wrapper">
        <div id="inner-wrapper">
            <p id="account"><a href="https://personas.services.mozilla.com/upload?action=logout">Sign out</a></p>
            <div id="nav">
                <h1><a href="http://www.getpersonas.com/"><img src="/store/img/logo.png" alt="Mozilla Labs Personas"></a></h1>
                <ul>
                    <li class="gallery"><a href="http://www.getpersonas.com/store/gallery/All/Popular">Gallery</a></li>
                    <li class="create"><a href="https://personas.services.mozilla.com/upload" class="active">Create <br/>Your Own</a></li>
                    <li class="demo"><a href="http://www.getpersonas.com/store/demo_install.html">Demo</a></li>
                    <li class="faq"><a href="http://www.getpersonas.com/store/faq.html">Frequent <br/>Questions</a></li>
                </ul>
            </div>
            <div id="header">
                <h2>Create Your Persona</h2>
                <h3>Follow the easy steps below to start dressing up your browser!</h3>
            </div>
            <div id="maincontent">
                <p id="breadcrumbs"><a href="http://www.getpersonas.com">Personas Home</a> : Create Your Own</p>
                
                <h4>Terms of Service</h4>
               <form action="upload" method="post">
               <input type="hidden" name="firstterms" value="1">
                        <textarea name="agreement" id="agreement" readonly>
Personas Designer Agreement

Dated: March 12, 2009

If you upload a “persona” design for the Firefox® web browser (each a “Persona”) in connection with any “persona” distribution services or features (the “Persona Services”) provided by Mozilla Corporation (“Mozilla”), you and your Personas are subject to the following terms, as well as Mozilla’s Privacy Policy, web site notices and other policies, guidelines or requirements that may be posted in connection with Persona Services (the “Terms”).  By submitting your Persona(s), you agree to these Terms.  If you are an individual acting as a representative of a corporation or other legal entity that wishes to use any Persona Services, you represent and agree that you accept the Terms on behalf of such entity.  If you have any questions about these terms or the Persona Services, please email: [personas@mozilla.com]

1) Responsibility for Personas.  You represent and warrant that:
 ▪ the descriptions and other data that you provide about your Personas are true to the best of your knowledge; and
 ▪ your Personas do not violate any applicable law, regulation or ordinance, nor infringe or misappropriate the rights of any third party. 

2) Licenses. In order to provide the Persona Services, you grant to Mozilla and its Affiliates a non-exclusive, worldwide, royalty-free, sublicensable license to distribute, transmit, reproduce, publish, publicly and privately perform and display and otherwise use your Personas solely in connection with Mozilla’s provision of the Persona Services. Mozilla may also bundle and/or package your Personas with other extensions and add-ons for delivery to users, maintain and/or update your Personas to provide compatibility with new versions of Firefox, and include your name and/or logo in drop down menus and other categorizations relating to the selection of Personas.

3) Management of Persona Services.  Mozilla may manage the Persona Services in its sole discretion in a manner designed to facilitate the integrity and proper functioning of the Persona Services without limitation or liability. The following is a list of exemplary activities that Mozilla in its sole discretion may undertake as part of its management of the AMO Services: (i) monitor, test and review Personas; (ii) remove or disable Personas or change their listing or description; (iii) use, modify or remove authentication requirements for access to any Persona Services; and (iv) collect statistics and other data regarding your Personas, which may be made publicly available but if made publicly available will be subject to the Privacy Policy.

4) Ownership, Reservation of Rights. You are welcome to use the Persona Services subject to these Terms, and Mozilla grants you the right to do so.  Mozilla and its licensors reserve all other rights in the Persona Services.  Further, nothing in the Terms shall be deemed to grant you any right to use the trademarks, trade names, service marks, or trade dress of Mozilla or its licensors and Mozilla hereby reserves all right, title and interest therein. For information on our trademarks, please see our Trademark and Logo Usage Policies. 

5) The Persona Services are provided "as-is."  Mozilla, its contributors, licensors, and distributors, disclaim all warranties, whether express or implied, including without limitation, implied warranties of merchantability, fitness for a particular purpose and non-infringement. Some jurisdictions do not allow the exclusion or limitation of implied warranties, so this disclaimer may not apply to you.

6) Except as required by law, Mozilla, its contributors, licensors, and distributors will not be liable for any indirect, special, incidental, consequential, punitive, or exemplary damages arising out of or in any way relating to the use of Persona Services.  The collective liability under these Terms will not exceed $500 (five hundred dollars). Some jurisdictions do not allow the exclusion or limitation of certain damages, so this exclusion and limitation may not apply to you.

7) Changes to the Terms. Mozilla may update these Terms as necessary from time to time. Any and all changes will be reflected on this page.  When Mozilla changes these Terms in a material way, a notice will be posted on the www.getpersonas.com Web site. These Terms may not be modified or cancelled without Mozilla’s written agreement. 

8) Eligibility.  You represent that you are of legal age to form a binding contract and that you not are a person barred from receiving or using the Persona Services under the laws of any country, including the country in which you are resident or from which you use the Persona Services.

9) Miscellaneous. These Terms are governed by the laws of the state of California, U.S.A., excluding its conflict of law provisions. If any portion of these Terms is held to be invalid or unenforceable, the remaining portions will remain in full force and effect. In the event of a conflict between a translated version of these Terms and the English language version, the English language version shall control.  Mozilla’s subsidiaries and affiliates shall be third party beneficiaries of these Terms, entitled to enforce and rely upon the provisions hereof.

10) Termination. You may terminate your use of the Persona Services at any time.  Mozilla may modify or discontinue the Persona Services at its discretion.</textarea>
                           <label class="agree" for="agree"><input type="checkbox" name="agree" value="1" id="agree" <?php if ($upload_submitted['agree'] == 1) echo "checked "; ?>/> I agree to the user agreement</label>
<?php if (array_key_exists('agree', $upload_errors)) echo '<span class="error-message tos-error">' . $upload_errors['agree'] . '</span>' ?>
                   
                   
                  <h4>How Would You Like to Share Your Personas Design?</h4>
                     <p>We encourage you to make your design publicly available, though you may choose not to submit your design under an open source license.  Please select the option below that you prefer. </p>

                     <div id="license-options">
                         <p><label for="license-cc"><input type="radio" name="license" value="cc" id="license-cc" <?php if ($upload_submitted['license'] == 'cc') echo "checked "; ?>/>Yes, I want to make my design available to everyone under a Creative Commons license.</label></p>

                            <ul>
                             <li>   People may share and modify my Persona as long as they give me credit and don’t charge for it.  <a href="http://creativecommons.org/licenses/by-nc-sa/3.0/us/">Learn more.</a></li>
                                <li>My design will be quickly added to the directory and hosted for free.</li>
                            </ul>

                            <p><label for="license-restricted"><input type="radio" name="license" value="restricted" <?php if ($upload_submitted['license'] == 'restricted') echo "checked "; ?>id="license-restricted" />I’d rather restrict any modifications and sharing of my Personas design.</label></p>

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
        <p>Copyright © 2009 Mozilla. <a href="http://labs.mozilla.com/projects/firefox-personas/">Personas</a> is a <a href="http://labs.mozilla.com">Mozilla Labs</a> experiment. | <a href="http://labs.mozilla.com/about-labs/">About Mozilla Labs</a>    |  <a href="http://www.getpersonas.com/store/privacy.html">Privacy</a></p>
    </div>
</body>
</html>
