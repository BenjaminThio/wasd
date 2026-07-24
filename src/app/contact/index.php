<?php require_once './models/Icon.php'; ?>

<!---THIS IS THE MAIN BODY OF THIS PAGE -->
<main class="contact">
    <div class="contact-heading">
        <!--This is the first heading-->
        <h2>Contact Us</h2>
        <p>We value your feedback.Contact us for any questions,technical support or information about WADS</p>
    </div>

    <div class="cards-layout-wrapper">

        <!--This is the first box-->
        <div class="contact-box">
            <div class="Contact-support_Box">
                <div class="icon">
                <?php

                    echo Icon::get('sup', 80);
                ?>
                </div>
                <h2>SUPPORT</h2>
                <p> Need assistance with your problem? Our support team is ready to help you. Reach us out adn let us help you!</p>
                <a href ="/wasd/contact/support">supportXcontactus</a>
            </div>
        </div>

    
        <!--This is the second Box-->
        <div class="support-box">
            <div class="Contact-partners_Box">
                
                <!--a method to replace image-->
                <div class="icon">
                <?php
                    echo Icon::get('sport', 80);
                ?>
                </div>
                <h2>PARTNERS</h2>
                <p>Powered by great partnerships.Built for amazing players.</P>
                <a href ="/wasd/contact/partneshipX">partnersXcontactus</a>
            </div>
        </div>

        <!--This is the third box-->
        <div class="press-Box">
            <div class="Contact-press_Box">
                <div class="icon">
                    <?php 
                    echo Icon::get('press',80);
                    ?>
                </div>
                <h2>PRESS</h2>
                <p>Discover the latest updates,game releases, and exiciting news from our team.</p>
                <a href="/wasd/contact/press">pressXcontactus</a>
            </div>
        </div>
    </div>
</main>
