<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/otp.php';
require_once __DIR__ . '/includes/functions.php';

$error = '';
$accountType = $_POST['account_type'] ?? $_GET['account_type'] ?? 'customer';
$identifier = trim($_POST['identifier'] ?? '');

if (!in_array($accountType, ['customer', 'provider'], true)) {
    $accountType = 'customer';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Your session expired. Please try again.';
    } elseif (!is_valid_email($identifier)) {
        $error = 'Please enter a valid registered email address.';
    } else {
        $table = $accountType === 'customer' ? 'users' : 'service_providers';
        $idField = $accountType === 'customer' ? 'user_id' : 'provider_id';
        $account = fetch_one("SELECT * FROM $table WHERE email = ? LIMIT 1", 's', [$identifier]);

        if (!$account) {
            $error = 'No account found with that email address.';
        } else {
            try {
                create_otp($identifier, $accountType);
                $_SESSION['reset_flow'] = [
                    'account_type' => $accountType,
                    'identifier' => $identifier,
                    'account_id' => (int) $account[$idField],
                    'otp_verified' => false,
                ];
                redirect('/verify-otp.php');
            } catch (Throwable $e) {
                error_log('HomeCare Connect email OTP error: ' . $e->getMessage());
                $error = 'We could not send the verification email. Please check the Gmail SMTP settings in includes/config.php and try again.';
            }
        }
    }
}

$pageTitle = 'Forgot Password';
$authScene = true; // animated background + rider scene
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper">
  <div class="form-card">
    <h1>Forgot Password</h1>
    <p class="muted text-center">Enter your registered email and we'll send a 4-digit verification code.</p>

    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/forgot-password.php">
      <?= csrf_field() ?>
      <div class="role-toggle">
        <label><input type="radio" name="account_type" value="customer" <?= $accountType === 'customer' ? 'checked' : '' ?>><span>&#128100; Customer</span></label>
        <label><input type="radio" name="account_type" value="provider" <?= $accountType === 'provider' ? 'checked' : '' ?>><span>&#129517; Provider</span></label>
      </div>
      <div class="form-group">
        <label>Registered Email Address</label>
        <input class="form-control" type="email" name="identifier" value="<?= htmlspecialchars($identifier) ?>" required autocomplete="email">
      </div>
      <button type="submit" class="btn btn-primary btn-block">Send Verification Code</button>
    </form>
    <div class="auth-links"><a href="<?= BASE_URL ?>/login.php">&larr; Back to login</a></div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
