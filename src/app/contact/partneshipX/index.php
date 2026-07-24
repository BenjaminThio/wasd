<div class="partnershipX-Box">
 
  <!-- ROW 1 -->
  <div class="row_1">
    <div class="partner L">
      <div class="partner img"> 
      <?php
      $image = __DIR__ . "/../../../../public/assets/partnership/nvdia.png";
      if (file_exists($image)) {
          echo '<img src="/wasd/public/assets/partnership/nvdia.png" alt="NVIDIA">';
      } else {
          echo "Image not found";
      } ?>
      </div>
      <div class="cont1">
        <div class="partner logo">NVIDIA</div>
        <p class="partner describ">
          NVIDIA provides WSD Co. with cutting-edge GPU technologies and AI-powered rendering solutions that significantly enhance the visual quality and performance of our games.
        </p>
      </div>
    </div>

    <div class="partner R">
      <div class="partner img">
        <?php
        $image = __DIR__ . "/../../../../public/assets/partnership/mic.png";
        if (file_exists($image)) {
            echo '<img src="/wasd/public/assets/partnership/mic.png" alt="INTELLIMIZE">';
        } else {
            echo "Image not found";
        } ?>
      </div>
      <div class="cont2">
        <div class="partner logo">INTELLIMIZE</div>
        <p class="partner describ">
          Microsoft provides WSD Co. with cloud computing infrastructure and gaming ecosystem support, enabling secure online multiplayer services.
        </p>
      </div>
    </div>
  </div>

  <!---THIS IS THE SECOND ROWW GURL---> 
  <div class="row_2">
    <div class="partner L">
      <div class="partner img">
         <?php
      $image = __DIR__ . "/../../../../public/assets/partnership/sony.png";
      if (file_exists($image)) {
          echo '<img src="/wasd/public/assets/partnership/sony.png" alt="SONY INTERACTIVE">';
      } else {
          echo "Image not found";
      } ?>
      </div>
      <div class="cont3">
        <div class="partner logo">SONY INTERACTIVE</div>
        <p class="partner describ">
          Sony Interactive Entertainment supports WSD Co. by providing PlayStation platform integration, technical development resources, and performance optimization.
        </p>
      </div>
    </div>

    <div class="partner R">
      <div class="partner img">
        <?php
      $image = __DIR__ . "/../../../../public/assets/partnership/epic.png";
      if (file_exists($image)) {
          echo '<img src="/wasd/public/assets/partnership/epic.png" alt="EPIC GAMES">';
      } else {
          echo "Image not found";
      } ?>
      </div>
      <div class="cont4">
        <div class="partner logo">EPIC GAMES</div>
        <p class="partner describ">
          Epic Games empowers WSD Co. through Unreal Engine, providing industry-leading game development technology that enables realistic environments.
        </p>
      </div>
    </div>
  </div>

  <!-- ROW 3 -->
  <div class="row_3">
    <div class="partner L">
      <div class="partner img">
         <?php
      $image = __DIR__ . "/../../../../public/assets/partnership/razerr.png";
      if (file_exists($image)) {
          echo '<img src="/wasd/public/assets/partnership/razerr.png" alt="RAZER">';
      } else {
          echo "Image not found";
      } ?>
      </div>
      <div class="cont5">
        <div class="partner logo">RAZER</div>
        <p class="partner describ">
          Razer enhances the WSD Co. gaming ecosystem through hardware compatibility, peripheral optimization, and esports collaboration.
        </p>
      </div>
    </div>

    <div class="partner R">
      <div class="partner img">
         <?php
      $image = __DIR__ . "/../../../../public/assets/partnership/dis.png";
      if (file_exists($image)) {
          echo '<img src="/wasd/public/assets/partnership/dis.png" alt="DISCORD">';
      } else {
          echo "Image not found";
      } ?>
      </div>
      <div class="cont6">
        <div class="partner logo">DISCORD</div>
        <p class="partner describ">
          Discord strengthens WSD Co.'s player community by providing integrated communication platforms that support real-time interaction.
        </p>
      </div>
    </div>
  </div>

  <!-- ROW 4 -->
  <div class="row_4">
    <div class="partner L">
      <div class="partner img">
         <?php
      $image = __DIR__ . "/../../../../public/assets/partnership/and.png";
      if (file_exists($image)) {
          echo '<img src="/wasd/public/assets/partnership/and.png" alt="ANDROID">';
      } else {
          echo "Image not found";
      } ?>
      </div>
      <div class="cont7">
        <div class="partner logo">ANDROID</div>
        <p class="partner describ">
          Android supports WSD Co. by providing Android platform integration, mobile development tools, and performance optimization. This partnership enables seamless compatibility across Android devices, enhances application performance, and delivers a smooth and reliable gaming experience for users.
        </p>
      </div>
    </div>

    <div class="partner R">
      <div class="partner img">
         <?php
      $image = __DIR__ . "/../../../../public/assets/partnership/aws.jpg";
      if (file_exists($image)) {
          echo '<img src="/wasd/public/assets/partnership/aws.jpg" alt="AWS">';
      } else {
          echo "Image not found";
      } ?>
      </div> <!-- Fixed: Added missing closing tag here for the image box -->
      <div class="cont8">
        <div class="partner logo">AWS</div>
        <p class="partner describ">
          Amazon supports WSD Co. by providing cloud infrastructure, scalable hosting services, and content delivery solutions. This partnership helps ensure reliable server performance, secure data management, and a smooth gaming experience for users worldwide.
        </p>
      </div>
    </div>
  </div>

</div>