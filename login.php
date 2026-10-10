<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/functions.php';

$error = '';
$selectedType = $_POST['account_type'] ?? $_GET['as'] ?? 'customer';

// Admin login is handled separately through /admin/login.php.
if ($selectedType === 'admin') {
    $selectedType = 'customer';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Your session expired. Please try again.';
    } else {
        $identifier = sanitize($_POST['identifier'] ?? '');
        $password   = $_POST['password'] ?? '';

        if ($selectedType === 'customer' || $selectedType === 'provider') {

            $table = $selectedType === 'customer'
                ? 'users'
                : 'service_providers';

            $mobileField = $selectedType === 'customer'
                ? 'mobile'
                : 'phone';

            $isEmail = is_valid_email($identifier);

            $sql = "SELECT * FROM $table WHERE "
                 . ($isEmail ? 'email' : $mobileField)
                 . " = ?";

            $rows = fetch_all($sql, 's', [$identifier]);

            if ($isEmail && count($rows) > 1) {

                $error = 'Multiple accounts share this email. Please log in using your mobile number instead.';

            } elseif (empty($rows)) {

                $error = 'No account found with those details.';

            } else {

                $account = $rows[0];

                if (!password_verify($password, $account['password'])) {

                    $error = 'Incorrect password. Please try again.';

                } elseif ($account['status'] !== 'active') {

                    $error = 'This account has been disabled. Please contact support.';

                } elseif (
                    $selectedType === 'provider'
                    && $account['approval_status'] !== 'approved'
                ) {

                    $error = 'Your provider account is still pending admin approval.';

                } else {

                    session_regenerate_id(true);

                    if ($selectedType === 'customer') {

                        $_SESSION['user_id'] = $account['user_id'];
                        $_SESSION['role'] = 'customer';
                        $_SESSION['name'] = $account['name'];

                        set_language($account['language']);

                        redirect('/customer/dashboard.php');

                    } else {

                        $_SESSION['provider_id'] = $account['provider_id'];
                        $_SESSION['role'] = 'provider';
                        $_SESSION['name'] = $account['name'];
                        set_language($account['language'] ?? 'en');

                        redirect('/provider/dashboard.php');
                    }
                }
            }
        }
    }
}

$pageTitle = 'Login';
$authScene = true; // animated background + rider scene
require __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
  <div class="form-card">

    <h1><?= t('login_title') ?></h1>

    <?php if ($error): ?>
      <div class="alert alert-error">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/login.php" novalidate>

      <?= csrf_field() ?>

      <label
        class="form-hint"
        style="font-weight:700;display:block;margin-bottom:10px;"
      >
        <?= t('login_as') ?>
      </label>

      <div class="role-toggle">

        <label>
          <input
            type="radio"
            name="account_type"
            value="customer"
            <?= $selectedType === 'customer' ? 'checked' : '' ?>
          >
          <span>&#128100; <?= t('customer') ?></span>
        </label>

        <label>
          <input
            type="radio"
            name="account_type"
            value="provider"
            <?= $selectedType === 'provider' ? 'checked' : '' ?>
          >
          <span>&#129517; <?= t('provider') ?></span>
        </label>

      </div>

      <div class="form-group">

        <label for="identifier">
          <?= t('login_identifier') ?>
        </label>

        <input
          class="form-control"
          type="text"
          id="identifier"
          name="identifier"
          required
          placeholder="name@example.com or 98XXXXXXXX"
        >

      </div>

      <div class="form-group">

        <label for="password">
          <?= t('password') ?>
        </label>

        <input
          class="form-control"
          type="password"
          id="password"
          name="password"
          required
        >

      </div>

      <button
        type="submit"
        class="btn btn-primary btn-block"
      >
        <?= t('login_btn') ?>
      </button>

    </form>

    <div class="auth-links">

      <a href="<?= BASE_URL ?>/forgot-password.php">
        <?= t('forgot_password') ?>
      </a>

      &nbsp;|&nbsp;

      <?= t('no_account') ?>

      <a href="<?= BASE_URL ?>/register.php">
        Create one
      </a>

    </div>

  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>