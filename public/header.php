<link rel="stylesheet" href="header.css">
<?php
// header.php
$current = basename($_SERVER['PHP_SELF']);
?>
<header class="lpHeader" id="lpHeader">
  <div class="lpTop">
    <a class="lpSkip" href="#main-content">Skip to main content</a>

    <button class="lpBurger" id="lpBurger" aria-label="Menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>

    <a class="lpLogo" href="index.php" aria-label="Home">
      <span class="lpLogoMark">TravelGuide</span>
    </a>

    <a class="lpCta" href="index.php#reserve">Reserve</a>
  </div>

  <nav class="lpNav" aria-label="Primary">
    <div class="lpNavInner">
      <!-- Top categories (Little Palm style) -->
      <div class="lpItem">
        <button class="lpLink" data-menu="stay" aria-expanded="false">Stay</button>
        <div class="lpDrop" id="menu-stay">
          <div class="lpDropGrid">
            <a href="#" class="lpDropLink">Suites</a>
            <a href="#" class="lpDropLink">Accessible</a>
            <a href="#" class="lpDropLink">Packages</a>
          </div>
        </div>
      </div>

      <div class="lpItem">
        <button class="lpLink" data-menu="explore" aria-expanded="false">Explore</button>
        <div class="lpDrop" id="menu-explore">
          <div class="lpDropGrid">
            <a href="#" class="lpDropLink">Experiences</a>
            <a href="#" class="lpDropLink">Events Calendar</a>
            <a href="#" class="lpDropLink">Amenities & Services</a>
            <a href="#" class="lpDropLink">Shop</a>
          </div>
        </div>
      </div>

      <div class="lpItem">
        <button class="lpLink" data-menu="dine" aria-expanded="false">Dine</button>
        <div class="lpDrop" id="menu-dine">
          <div class="lpDropGrid">
            <a href="#" class="lpDropLink">The Dining Room</a>
            <a href="#" class="lpDropLink">Bar</a>
            <a href="#" class="lpDropLink">Private Dining</a>
          </div>
        </div>
      </div>

      <div class="lpItem">
        <button class="lpLink" data-menu="gather" aria-expanded="false">Gather</button>
        <div class="lpDrop" id="menu-gather">
          <div class="lpDropGrid">
            <a href="#" class="lpDropLink">Weddings</a>
            <a href="#" class="lpDropLink">Private Events</a>
            <a href="#" class="lpDropLink">Meetings</a>
          </div>
        </div>
      </div>

      <div class="lpItem">
        <button class="lpLink" data-menu="wellness" aria-expanded="false">Wellness</button>
        <div class="lpDrop" id="menu-wellness">
          <div class="lpDropGrid">
            <a href="#" class="lpDropLink">Spa</a>
            <a href="#" class="lpDropLink">Treatments</a>
            <a href="#" class="lpDropLink">Packages</a>
          </div>
        </div>
      </div>

      <a class="lpLink lpLinkA <?= $current=='offers.php'?'active':'' ?>" href="offers.php">Offers</a>
    </div>
  </nav>

  <!-- Mobile menu -->
  <div class="lpMobile" id="lpMobile" hidden>
    <a href="index.php" class="lpMItem">Home</a>
    <a href="marker.php?id=1" class="lpMItem">Example Single</a>
    <a href="admin_add_marker.php" class="lpMItem">Add Marker</a>

    <div class="lpMGroup">
      <div class="lpMTitle">Explore</div>
      <a href="#" class="lpMSub">Experiences</a>
      <a href="#" class="lpMSub">Events Calendar</a>
      <a href="#" class="lpMSub">Amenities & Services</a>
      <a href="#" class="lpMSub">Shop</a>
    </div>

    <div class="lpMGroup">
      <div class="lpMTitle">Dine</div>
      <a href="#" class="lpMSub">The Dining Room</a>
      <a href="#" class="lpMSub">Bar</a>
      <a href="#" class="lpMSub">Private Dining</a>
    </div>

    <a href="offers.php" class="lpMItem">Offers</a>
    <a class="lpMCta" href="index.php#reserve">Reserve</a>
  </div>
</header>
