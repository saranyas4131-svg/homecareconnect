<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/functions.php';

if (empty($_SESSION['reset_flow']['otp_verified'])) {
    redirect('/forgot-password.php');
}
$flow = $_SESSION['reset_flow'];
$error = '';
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Your session expired. Please try again.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        if ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } elseif (!is_strong_password($password)) {
            $error = 'Password must be at least 8 characters and include letters and numbers.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $table = $flow['account_type'] === 'customer' ? 'users' : 'service_providers';
            $idField = $flow['account_type'] === 'customer' ? 'user_id' : 'provider_id';
            $accountId = (int)($flow['account_id'] ?? 0);

            if ($accountId <= 0) {
                $error = 'Your password reset session is invalid. Please start again.';
            } else {
                $updated = run_query(
                    "UPDATE $table SET password = ? WHERE $idField = ? AND email = ?",
                    'sis',
                    [$hashed, $accountId, $flow['identifier']]
                );

                if ($updated === false || db()->affected_rows < 1) {
                    $error = 'We could not update the password. Please start the password reset again.';
                } else {
                    unset($_SESSION['reset_flow']);
                    $done = true;
                }
            }
        }
    }
}

$pageTitle = 'Reset Password';
$authScene = true; // animated background + rider scene
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrapper">
  <div class="form-card">
    <h1>Set a New Password</h1>

    <?php if ($done): ?>
      <div class="alert alert-success">Your password has been updated successfully.</div>
      <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary btn-block">Go to Login</a>
    <?php else: ?>
      <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <form method="POST" action="<?= BASE_URL ?>/reset-password.php">
        <?= csrf_field() ?>
        <div class="form-group"><label>New Password</label>
          <input class="form-control" type="password" id="password" name="password" required></div>
        <div class="form-group"><label>Confirm New Password</label>
          <input class="form-control" type="password" id="confirm_password" name="confirm_password" required>
          <div class="form-error" id="pwdMatchMsg"></div></div>
        <button type="submit" class="btn btn-primary btn-block">Update Password</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
