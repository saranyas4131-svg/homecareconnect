<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/otp.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/functions.php';

if (empty($_SESSION['reset_flow'])) {
    redirect('/forgot-password.php');
}

$flow = $_SESSION['reset_flow'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Your session expired. Please try again.';
    } else {
        $submitted = trim($_POST['otp'] ?? '');
        if (verify_otp($flow['identifier'], $flow['account_type'], $submitted)) {
            $_SESSION['reset_flow']['otp_verified'] = true;
            redirect('/reset-password.php');
        } else {
            $error = 'Invalid or expired 4-digit code. Please request a new one.';
        }
    }
}

$email = (string) ($flow['identifier'] ?? '');
$at = strpos($email, '@');
$masked = $email;
if ($at !== false) {
    $local = substr($email, 0, $at);
    $domain = substr($email, $at);
    $visible = strlen($local) <= 2 ? substr($local, 0, 1) : substr($local, 0, 2);
    $masked = $visible . str_repeat('*', max(1, min(5, strlen($local) - strlen($visible)))) . $domain;
}

$pageTitle = 'Verify Code';
$authScene = true; // animated background + rider scene
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper">
  <div class="form-card">
    <h1>Verify Your Identity</h1>
    <p class="muted text-center">
      A 4-digit verification code has been sent to <strong><?= htmlspecialchars($masked) ?></strong>.
    </p>

    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/verify-otp.php">
      <?= csrf_field() ?>
      <div class="form-group">
        <label>Enter 4-digit Code</label>
        <input class="form-control" type="text" name="otp" maxlength="4" minlength="4" pattern="[0-9]{4}" inputmode="numeric" autocomplete="one-time-code" required autofocus>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Verify Code</button>
    </form>
    <div class="auth-links"><a href="<?= BASE_URL ?>/forgot-password.php">&larr; Start over</a></div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
