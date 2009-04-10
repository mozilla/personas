<?php
	require_once 'lib/personas_constants.php';
	require_once 'lib/user.php';	


	$user = new PersonaUser();
	$title = "Review Guidelines"; 
	include 'templates/header.php'; 
?>
<body>
    <div id="outer-wrapper">
        <div id="inner-wrapper">
<?php include 'templates/nav.php'; ?>
            <div id="header">
                <h2>Review Guidelines</h2>
            </div>
            <div id="maincontent" class="demo">
                <div id="breadcrumbs">
                    <a href="http://www.getpersonas.com">Personas Home</a> : Review Guidelines
                </div>

                <p>Though it would be great if we could accept every image or design (“Design”) submitted to the Personas gallery, the reality is that we’re unable to do so – either for legal reasons or because we do not want certain acts or images associated with Mozilla. Therefore, we have created the following guidelines to review the Designs submitted for the gallery. </p>

                <h3 id="images">I.   Images Not Allowed.  </h3>

                <p>Describing inappropriate content is difficult since it is culturally driven; however, there are standards that cross cultural boundaries.  Mozilla in its sole discretion may refuse any Design.  Some of the reasons we will not include a Design in the Personas gallery may include, but are not limited to, the following:</p>

                <h5>Obscene:</h5>
                <ul>
                    	<li>Sexually explicit</li>
                        <li>Contains sexually derived words (e.g., f*ck)</li>
                        <li>Explicit nudity 
                            <ul>
                                <li>Full frontal nudity</li>
                                <li>Genitalia</li>
                                <li>Buttocks nudity</li>
                            </ul>
                            <li>Obscene gestures (this is cultural and we’ll have to rely on our community members to monitor this category (e.g., flipping someone off in the U.S. (middle finger) vs. in the UK (2 finger salute with index and middle finger – a reverse peace sign)).</li>
                </ul>


                <h5>Violent:</h5>
                <ul>
                    <li>Graphic violence</li>
                    <li>Gore</li>
                    <li>Person or animal being harmed or threatened</li>
                    <li>Bloodshed</li>
                    <li>Advocating the overthrow of a government</li>
                </ul>
                
                <h5>Hate: </h5>
                <ul>
                    <li>Targets anyone because of his or her membership in a certain social group, including race, gender, color, religion, belief, sexual orientation, disability, ethnicity, nationality, age, gender identity, or political affiliation.</li>
                    <li>Symbolic representation of any group that targets anyone because of his or her membership in a certain social group.</li>
                </ul>
                
                <h5>Drugs: </h5>
                <ul>
                    <li>Illegal drugs or controlled substances</li>
                    <li>Use of illegal drugs or controlled substances</li>
                    <li>Drug paraphernalia</li>
                </ul>
                
                <h5>Privacy:</h5>
                <ul>
                    <li>Violate privacy rights of any third party</li>
                </ul>


                <h3>II.  Trademark or Copyright Violations</h3>
                
                <p><strong class="legal">Mozilla Trademarks.</strong> Except for Foxkeh, any modification or manipulation of a Mozilla trademark or logo is not permitted.</p>

                <p><strong class="legal">Third Party Intellectual Property.</strong>  Our users are representing that they have the rights images they are contributing.</p> 
                <p>[Version A] We are relying on this statement and will not review the submitted designs and images for third party intellectual property rights.  We will comply with proper take-down notices sent the copyright or trademark owner or licensee.</p>

                <p>[Version B] However, if there is an obvious infringement (e.g., a Coca-Cola logo, Manchester United logo) we will not include that image or design.</p>

                <p>[Version C] However, if there is an obvious infringement (e.g., a Coca-Cola logo, Manchester United logo) we will not include that image or design.  We will contact the person who submitted the design asking them if they have the rights to that image and if (i) they are licensing this image under the Creative Commons license; or (ii) they want the enter into a separate licensing agreement with us.</p>

                <h3>III.  Reply to Designer About Submitted Design</h3>

                <p><strong class="legal">Design Acceptance:</strong> If a design is accepted, we will send the following message:</p>

                <blockquote>“Congratulations, your Design has been accepted and now is part of the Personas gallery.  Thank you for contributing your Design and licensing it to Mozilla [and to the public under the Creative Commons license].”  [CC language included if the designer checked the Creative Commons box] </blockquote>

                <p><strong class="legal">Design Rejection:</strong> If a design is rejected, we will send the following message:</p>

                <blockquote>“Thank you for submitting your Design to the Personas gallery.  However, this design does not meet our <a href="#images">guidelines</a> and we are unable to include it in our gallery.  You may create your own custom persona [hyperlink to instructions on using the custom setting].”</blockquote>

                <h3>IV.  DMCA and Trademark Violation Take Down Notices</h3>

                <p>When we receive a take-down notice, either through DMCA notice or trademark notice, we will take the following steps:</p>
                <ol>
                <li>Promptly take down the image in question;</li>
                <li>Notify the designer of the take down notice;
                <li>Respond to the entity submitting the take down notice;</li>
                <li>Track the following information and retain it for three years:
                    <ol>
                        <li>Name, email address [and IP address] of the designer</li>
                        <li>Date the notice was received, </li>
                        <li>Name of the copyright or trademark holder; and</li>
                        <li>Date the Design was taken down.</li>
                    </ol>
                </li>
                </ol>
                
                <p><strong class="legal">Frequent Offenders.</strong>  If a designer is a frequent offender, we will no longer accept Designs from him or her.  What is deemed frequent is somewhat case specific (e.g., 2 Designs rejected out of a total of 20 designs submitted may not be frequent; however 3 rejected out of 3 or 5 Designs submitted is). Once a designer has a third Design rejected, legal will review whether this person shall be banned from submitting Designs to the Personas gallery.</p>

                
                
                
                
                
                
                
                
                
                
                
                
                
                
         
         
            </div>
        </div>
    </div>
<?php include 'templates/footer.php'; ?>
</body>
</html>
