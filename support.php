<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/notifications.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/support.php';

ensure_customer_support_table();

$error = '';
$success = '';
$customer = null;
if (!empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'customer') {
    $customer = fetch_one("SELECT user_id, name, email, mobile FROM users WHERE user_id = ?", 'i', [(int)$_SESSION['user_id']]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_problem'])) {
    if (!verify_csrf()) {
        $error = 'Your session expired. Please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $bookingCode = trim($_POST['booking_code'] ?? '');
        $bookingId = null;

        if ($name === '' || $email === '' || $mobile === '' || $description === '') {
            $error = 'Please enter your name, registered email, mobile number and describe the issue.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (!preg_match('/^[6-9][0-9]{9}$/', preg_replace('/\D+/', '', $mobile))) {
            $error = 'Please enter a valid 10-digit Indian mobile number.';
        } else {
            if ($bookingCode !== '') {
                $row = $customer
                    ? fetch_one("SELECT booking_id FROM bookings WHERE booking_code = ? AND customer_id = ?", 'si', [$bookingCode, (int)$customer['user_id']])
                    : fetch_one("SELECT booking_id FROM bookings WHERE booking_code = ?", 's', [$bookingCode]);
                if (!$row) {
                    $error = 'Please enter a valid booking ID.';
                } else {
                    $bookingId = (int)$row['booking_id'];
                }
            }

            if ($error === '') {
                $customerId = $customer ? (int)$customer['user_id'] : null;
                create_support_ticket($customerId, $bookingId, 'Customer Care', 'Customer Care Issue', $description, $name, $email, $mobile);
                $success = 'Your issue has been submitted successfully. Our Admin team will review it.';
            }
        }
    }
}

$pageTitle = 'Customer Care';
require __DIR__ . '/includes/header.php';
?>
<section>
  <div class="container">
    <div class="section-title">
      <h2>Customer Care</h2>
      <p>How can we help you? Please tell us what issue you are facing and our team will look into it.</p>
    </div>

    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="card" style="max-width:760px;margin:0 auto;">
      <h3>How can we help you?</h3>
      <p class="muted">Please describe the issue you are facing. Include your registered contact details so Admin can get back to you.</p>
      <form method="POST">
        <?= csrf_field() ?>
        <div class="grid grid-2">
          <div class="form-group"><label>Name</label><input class="form-control" name="name" value="<?= htmlspecialchars($_POST['name'] ?? ($customer['name'] ?? '')) ?>" required></div>
          <div class="form-group"><label>Email</label><input class="form-control" type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? ($customer['email'] ?? '')) ?>" required></div>
          <div class="form-group"><label>Mobile Number</label><input class="form-control" name="mobile" value="<?= htmlspecialchars($_POST['mobile'] ?? ($customer['mobile'] ?? '')) ?>" maxlength="10" required></div>
          <div class="form-group"><label>Booking ID (optional)</label><input class="form-control" name="booking_code" placeholder="Example: SHS-000001"></div>
        </div>
        <div class="form-group">
          <label>What issue are you facing?</label>
          <textarea class="form-control" name="description" rows="7" placeholder="Tell us what happened and how we can help you..." required></textarea>
        </div>
        <button class="btn btn-primary" name="submit_problem" value="1">Submit Issue</button>
      </form>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
