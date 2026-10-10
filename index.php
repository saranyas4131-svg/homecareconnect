<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/media.php';

$services = fetch_all("SELECT sc.*, m.media_id FROM service_categories sc LEFT JOIN media m ON m.media_id = sc.image_media_id AND m.is_active = 1 WHERE sc.status = 'active' ORDER BY sc.service_id");
$hero = get_media_by_key('hero-electrician');

// Real availability per service (approved, active providers currently marked "available").
$serviceAvailability = [];
foreach (fetch_all("SELECT service_id, COUNT(*) AS n FROM service_providers WHERE approval_status = 'approved' AND status = 'active' AND availability = 'available' GROUP BY service_id") as $row) {
    $serviceAvailability[(int) $row['service_id']] = (int) $row['n'];
}

$pageTitle = 'Home';
$extraCss = ['/assets/css/orbit-services.css'];
require __DIR__ . '/includes/header.php';
?>

<section class="hero" id="home">
  <div class="container hero-inner">
    <div class="hero-text">
      <h1>Book trusted <span class="highlight">home services</span>,<br>right from your phone.</h1>
      <p>HomeCare Connect brings verified electricians, plumbers, cleaners, carpenters, appliance
         technicians and painters to your doorstep — booked in minutes, tracked in real time.</p>

      <div class="hero-cta">
        <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary">Get Started</a>
        <a href="<?= BASE_URL ?>/services.php" class="btn btn-outline">Browse Services</a>
      </div>
    </div>

    <div class="hero-image">
      <img src="<?= htmlspecialchars(media_url($hero['media_id'] ?? 0)) ?>" alt="Home service technician illustration">
    </div>
  </div>
</section>

<section id="services" class="orbit-services">
  <div class="container">

    <div class="section-title orbit-title">
      <h2>Our Services</h2>
      <p>Pick a service and book a verified professional near you.</p>
    </div>

    <?php $isCustomer = !empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'customer'; ?>

    <div class="orbit-carousel" id="orbitCarousel">

      <div class="orbit-stage" id="orbitStage" tabindex="0" role="group" aria-roledescription="carousel" aria-label="Our services">
        <div class="orbit-ambient" aria-hidden="true"></div>
        <div class="orbit-platform" aria-hidden="true"><span class="orbit-ring orbit-ring-outer"></span><span class="orbit-ring orbit-ring-inner"></span></div>

        <div class="orbit-cards" id="orbitCards">
          <?php foreach ($services as $s):
              $sid = (int) $s['service_id'];
              $availNow = (int) ($serviceAvailability[$sid] ?? 0) > 0;
              $bookUrl = $isCustomer
                  ? BASE_URL . '/customer/booking.php?service_id=' . $sid
                  : BASE_URL . '/login.php';
          ?>
            <article class="orbit-card" data-name="<?= htmlspecialchars($s['service_name']) ?>">
              <div class="orbit-card-media">
                <img
                  src="<?= htmlspecialchars(media_url((int)($s['media_id'] ?? 0))) ?>"
                  alt="<?= htmlspecialchars($s['service_name']) ?>"
                  loading="lazy" draggable="false">
              </div>
              <div class="orbit-card-body">
                <h3><?= htmlspecialchars($s['service_name']) ?></h3>
                <p class="orbit-status <?= $availNow ? 'is-on' : 'is-later' ?>">
                  <span class="orbit-status-dot"></span>
                  <?= $availNow ? 'Available today' : 'Book for a later slot' ?>
                </p>
                <a href="<?= $bookUrl ?>" class="orbit-book">Book Now</a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="orbit-nav">
        <button type="button" class="orbit-arrow" id="orbitPrev" aria-label="Previous service">&#8249;</button>
        <div class="orbit-label" id="orbitLabel" aria-live="polite"></div>
        <button type="button" class="orbit-arrow" id="orbitNext" aria-label="Next service">&#8250;</button>
      </div>
      <p class="orbit-hint">Drag sideways or use the arrows</p>

    </div>
  </div>
</section>

<section class="section-alt" id="how-it-works">
  <div class="container">

    <div class="section-title">
      <h2>How It Works</h2>
      <p>From booking to payment — a simple, transparent flow.</p>
    </div>

    <div class="grid grid-4">

      <div class="card step-card">
        <div class="step-number">1</div>
        <h3>Book a Service</h3>
        <p>Choose a service, date, time and your address.</p>
      </div>

      <div class="card step-card">
        <div class="step-number">2</div>
        <h3>Get Matched</h3>
        <p>A verified provider accepts your request instantly.</p>
      </div>

      <div class="card step-card">
        <div class="step-number">3</div>
        <h3>Track Live Status</h3>
        <p>Follow every step, from "on the way" to "completed".</p>
      </div>

      <div class="card step-card">
        <div class="step-number">4</div>
        <h3>Pay &amp; Review</h3>
        <p>Pay securely and rate your experience.</p>
      </div>

    </div>
  </div>
</section>

<section id="why-us">
  <div class="container">

    <div class="section-title">
      <h2>Why Choose HomeCare Connect</h2>
    </div>

    <div class="grid grid-2">

      <div class="why-card">
        <span class="why-icon">&#9733;</span>
        <div>
          <h3>Verified Professionals</h3>
          <p>Every provider is reviewed and approved before going live.</p>
        </div>
      </div>

      <div class="why-card">
        <span class="why-icon">&#128274;</span>
        <div>
          <h3>Secure Payments</h3>
          <p>Pay only after your service is completed, in Indian Rupees.</p>
        </div>
      </div>

      <div class="why-card">
        <span class="why-icon">&#128269;</span>
        <div>
          <h3>Live Status Tracking</h3>
          <p>Know exactly where your booking stands at every step.</p>
        </div>
      </div>

      <div class="why-card">
        <span class="why-icon">&#128172;</span>
        <div>
          <h3>Ratings You Can Trust</h3>
          <p>Provider ratings are calculated from real customer feedback.</p>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- About HomeCare Connect -->
<section id="about">
  <div class="container">

    <div class="section-title">
      <h2>About HomeCare Connect</h2>
      <p>Making home services simple, transparent and reliable.</p>
    </div>

    <div class="grid grid-2">

      <div class="card">
        <h3>Our Purpose</h3>
        <p>
          HomeCare Connect exists to make booking a home professional as easy
          as ordering food online — no phone calls to multiple shops, no
          uncertainty about pricing or arrival times.
        </p>
      </div>

      <div class="card">
        <h3>Easy Booking</h3>
        <p>
          Pick a service, choose a convenient date and time, add your address,
          and confirm — your request is on its way to a qualified provider
          within moments.
        </p>
      </div>

      <div class="card">
        <h3>Connecting Customers &amp; Providers</h3>
        <p>
          We connect customers directly with verified, approved service
          providers across electrical, plumbing, cleaning, carpentry,
          appliance repair and painting work.
        </p>
      </div>

      <div class="card">
        <h3>Booking &amp; Tracking</h3>
        <p>
          Every booking moves through a clear set of stages — assigned,
          accepted, on the way, arrived, in progress, completed — visible
          to you at every step.
        </p>
      </div>

      <div class="card">
        <h3>Secure Payment</h3>
        <p>
          Once your service is completed, the provider enters the final
          amount and you pay securely through the platform in Indian Rupees.
        </p>
      </div>

      <div class="card">
        <h3>Feedback &amp; Ratings</h3>
        <p>
          After every completed booking, you can rate your experience —
          helping us maintain a high standard of service across every
          provider on the platform.
        </p>
      </div>

    </div>

  </div>
</section>



<section id="customer-care" class="section-alt">
  <div class="container text-center">
    <div class="section-title">
      <h2>Customer Care</h2>
      <p>Need help with a booking, provider, payment, tracking, cancellation or account problem? Get help or record the issue for our Admin team.</p>
      <a href="<?= BASE_URL ?>/support.php" class="btn btn-primary">Open Customer Care</a>
    </div>
  </div>
</section>

<section class="section-alt text-center">
  <div class="container">

    <h2>Ready to get your home sorted?</h2>

    <p>Join thousands of customers across India using HomeCare Connect.</p>

    <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary">
      Create your free account
    </a>

  </div>
</section>

<script src="<?= BASE_URL ?>/assets/js/orbit-services.js"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>