<div class="page-box">
  
  <!-- Latest Posts Section -->
  <section class="news-section">
    <h2 class="section-title">OUR LATEST POSTS:</h2>
    <p class="section-subtitle">Discover the newest games, fresh experiences, and upcoming adventures. Explore the latest releases from our creators, featuring innovative gameplay, immersive worlds, and unique stories waiting to be discovered.</p>
    
    <div class="news-grid">
      <!-- News 1 -->
      <article class="news-card">
        <div class="card-img">
          <?php
            $image = __DIR__ . "/../../../../public/assets/press/game1.png";
            if (file_exists($image)) {
                echo '<img src="/wasd/public/assets/press/game1.png" alt="beast">';
            } else {
                echo "Image not found";
            } ?>
        </div>
        <div class="card-content">
          <div class="card-meta">
            <span class="category xbox">XBOX</span>
            <span class="date">MAR 14, 2026</span>
          </div>
          <h3 class="card-title">Shadow Protocol: Version 2.0 (Coming Soon)</h3>
          <p class="card-text">Shadow Protocol: Version 2.0 introduces an enhanced open-world experience with upgraded graphics, improved AI, and an expanded multiplayer mode.</p>
          <div class="card-footer">
            <span>👤 by Admin</span>
            <span>💬 17K</span>
          </div>
        </div>
      </article>

      <!-- News 2 -->
      <article class="news-card">
        <div class="card-img"> 
          <?php
            $image = __DIR__ . "/../../../../public/assets/press/game2.png";
            if (file_exists($image)) {
                echo '<img src="/wasd/public/assets/press/game2.png" alt="beast">';
            } else {
                echo "Image not found";
            } ?>
        </div>
        <div class="card-content">
          <div class="card-meta">
            <span class="category ps">PS 5</span>
            <span class="date">MAR 18, 2026</span>
          </div>
          <h3 class="card-title">Legends of Aether: Reborn (Coming Soon)</h3>
          <p class="card-text">Legends of Aether: Reborn is the next evolution of the popular fantasy RPG series. The new version features a redesigned combat system.</p>
          <div class="card-footer">
            <span>👤 by Admin</span>
            <span>💬 432</span>
          </div>
        </div>
      </article>

      <!-- News 3 (Added your missing card slot!) -->
      <article class="news-card">
        <div class="card-img">
           <?php
            $image = __DIR__ . "/../../../../public/assets/press/game3.png";
            if (file_exists($image)) {
                echo '<img src="/wasd/public/assets/press/game3.png" alt="beast">';
            } else {
                echo "Image not found";
            } ?>
        </div>
        <div class="card-content">
          <div class="card-meta">
            <span class="category pc">PC</span>
            <span class="date">MAR 20, 2026</span>
          </div>
          <h3 class="card-title">Cyber Nexus: Evolution (Coming Soon)</h3>
          <p class="card-text">Cyber Nexus: Evolution is an action-packed sci-fi adventure that brings players into a futuristic cyber world filled with advanced technology.</p>
          <div class="card-footer">
            <span>👤 by Admin</span>
            <span>💬 562</span>
          </div>
        </div>
      </article>

      <!-- News 4 -->
      <article class="news-card">
        <div class="card-img">
           <?php
            $image = __DIR__ . "/../../../../public/assets/press/game4.png";
            if (file_exists($image)) {
                echo '<img src="/wasd/public/assets/press/game4.png" alt="beast">';
            } else {
                echo "Image not found";
            } ?>
        </div>
        <div class="card-content">
          <div class="card-meta">
            <span class="category android">ANDROID</span>
            <span class="date">MAR 23, 2026</span>
          </div>
          <h3 class="card-title">Last Transmissionn: RELEASE DATE 12 DEC 2026</h3>
          <p class="card-text">After Earth’s first deep-space mission goes silent, a special operative is sent to recover the lost crew. Inside the spacecraft, they discover an experimental AI system.</p>
          <div class="card-footer">
            <span>👤 by Admin</span>
            <span>💬 362</span>
          </div>
        </div>
      </article>
    </div>
  </section>
</div>

  </section>

  <!-- comunity Section -->
  <section class="com-section">
  <h2 class="section-title text-center">Community Feedback</h2>
  
  <div class="com-slider-container">
    <!-- Pinned Side Arrow Buttons -->
    <button class="slider-arrow left" onclick="scrollSlider(-1)">‹</button>
    
    <div class="com-flex">
      <!-- BOX 1 -->
      <div class="com-card">
        <div class="human-container">
          <?php
              $image = __DIR__ . "/../../../../public/assets/press/beast.png";
              if (file_exists($image)) {
                  echo '<img src="/wasd/public/assets/press/beast.png" alt="beast">';
              } else {
                  echo "Image not found";
              } ?>
        </div>
        <h4 class="user-name">MICHAEL</h4>
        <span class="user-tag">GAMER</span>
        <p class="user-quote">"The attention to detail in WSD Co.'s games is exceptional. From immersive environments to responsive gameplay, every title feels polished and enjoyable."</p>
      </div>

      <!-- Box 2 -->
      <div class="com-card">
        <div class="human-container">
             <?php
              $image = __DIR__ . "/../../../../public/assets/press/pew.png";
              if (file_exists($image)) {
                  echo '<img src="/wasd/public/assets/press/pew.png" alt="pew">';
              } else {
                  echo "Image not found";
              } ?>
        </div>
        <h4 class="user-name">MIGUEL CARPENTER</h4>
        <span class="user-tag">GAMER</span>
        <p class="user-quote">"WSD Co. consistently delivers high-quality games with outstanding graphics and smooth performance. Every update introduces exciting new features."</p>
      </div>

      <!-- Box 3 -->
      <div class="com-card">
        <div class="human-container">
             <?php
              $image = __DIR__ . "/../../../../public/assets/press/jack.png";
              if (file_exists($image)) {
                  echo '<img src="/wasd/public/assets/press/jack.png" alt="jack">';
              } else {
                  echo "Image not found";
              } ?>
        </div>
        <h4 class="user-name">Jack.M</h4>
        <span class="user-tag">GAMER</span>
        <p class="user-quote">"After spending some time with this game, I can confidently say that it has a lot of potential. The first thing that caught my attention was the incredible art direction."</p>
      </div>
    </div> <!-- /com-flex -->
    
    <button class="slider-arrow right" onclick="scrollSlider(1)">›</button>
  </div>

</div> 
      
  
  <!-- TO MAKE THE BUTTON WORK-->
  <script>
  function scrollSlider(direction) {
  const container = document.querySelector('.com-flex');
  const card = document.querySelector('.com-card');
  
  if (container && card) {
    
    const gap = 30; 
    const scrollAmount = (card.offsetWidth + gap) * direction;
    
    container.scrollBy({
      left: scrollAmount,
      behavior: 'smooth'
    });
  }
}
</script>
    </div>

    <div class="slide-dots">
    <span class="dot active" onclick="currentSlide(0)"></span>
    <span class="dot" onclick="currentSlide(1)"></span>
    <span class="dot" onclick="currentSlide(2)"></span>
  </div>
  </section>

</div>