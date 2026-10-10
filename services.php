<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/media.php';

$services = fetch_all("SELECT sc.*, m.media_id FROM service_categories sc LEFT JOIN media m ON m.media_id = sc.image_media_id AND m.is_active = 1 WHERE status = 'active' ORDER BY service_id");

$pageTitle = 'Services';
require __DIR__ . '/includes/header.php';
?>

<section>
  <div class="container">

    <div class="section-title">
      <h2>All Services</h2>
      <p>Choose the service you need — every provider is approved before appearing here.</p>
    </div>

    <div class="grid grid-3">

      <?php foreach ($services as $s): ?>

        <div class="card service-card">

          <img
            src="<?= htmlspecialchars(media_url((int)($s['media_id'] ?? 0))) ?>"
            alt="<?= htmlspecialchars($s['service_name']) ?>"
            style="display:block; width:120px; height:120px; object-fit:contain; margin:0 auto 14px;"
          >

          <h3><?= htmlspecialchars($s['service_name']) ?></h3>

          <p><?= htmlspecialchars($s['description']) ?></p>

          <?php if (!empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'customer'): ?>

            <a
              href="<?= BASE_URL ?>/customer/booking.php?service_id=<?= (int) $s['service_id'] ?>"
              class="btn btn-primary btn-sm"
            >
              Book Now
            </a>

          <?php else: ?>

            <a
              href="<?= BASE_URL ?>/login.php"
              class="btn btn-primary btn-sm"
            >
              Book Now
            </a>

          <?php endif; ?>

        </div>

      <?php endforeach; ?>

    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>