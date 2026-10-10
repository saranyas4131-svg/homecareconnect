<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/captcha.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/profile.php';

$error = '';
$success = '';

$selectedType = $_POST['account_type'] ?? $_GET['as'] ?? 'customer';

if ($selectedType !== 'customer' && $selectedType !== 'provider') {
    $selectedType = 'customer';
}

/* =========================
   LANGUAGE SWITCHING
   ========================= */

if (isset($_GET['lang'])) {
    set_language($_GET['lang']);
}

$currentLang = $_SESSION['lang'] ?? 'en';


/* =========================
   PROCESS REGISTRATION
   ========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf()) {

        $error = t('error_session_expired');

    } elseif (!verify_captcha($_POST['captcha'] ?? '', $selectedType)) {

        $error = t('error_captcha');

    } else {

        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        /*
         * Registration no longer asks the user to choose a language.
         * Use the language currently selected on the website.
         */
        $language = $_SESSION['lang'] ?? 'en';

        if (!isset($GLOBALS['TRANSLATIONS'][$language])) {
            $language = 'en';
        }


        /* =========================
           COMMON VALIDATION
           ========================= */

        if ($name === '' || $email === '' || $mobile === '') {

            $error = t('error_fill_required');

        } elseif (!is_valid_email($email)) {

            $error = t('error_invalid_email');

        } elseif (!is_valid_indian_mobile($mobile)) {

            $error = t('error_invalid_mobile');

        } elseif ($password !== $confirm) {

            $error = t('error_password_mismatch');

        } elseif (!is_strong_password($password)) {

            $error = t('error_weak_password');

        } else {

            $hashed = password_hash($password, PASSWORD_DEFAULT);


            /* =========================
               CUSTOMER REGISTRATION
               ========================= */

            if ($selectedType === 'customer') {

                $existing = fetch_one(
                    "SELECT user_id FROM users WHERE mobile = ?",
                    's',
                    [$mobile]
                );

                if ($existing) {

                    $error = t('error_mobile_exists');

                } else {

                    $address = sanitize($_POST['address'] ?? '');
                    $city = sanitize($_POST['city'] ?? '');
                    $state = sanitize($_POST['state'] ?? '');
                    $pincode = trim($_POST['pincode'] ?? '');
                    $gender = normalize_gender($_POST['gender'] ?? '');

                    if ($gender === null) {

                        // Gender is mandatory for customers (Male / Female).
                        $error = t('error_select_gender');

                    } elseif ($address === '' || $city === '' || $state === '') {

                        $error = t('error_incomplete_address');

                    } elseif (!is_valid_indian_pincode($pincode)) {

                        $error = t('error_invalid_pincode');

                    } else {

                        ensure_user_gender_schema();

                        run_query(
                            "INSERT INTO users
                            (
                                name,
                                email,
                                mobile,
                                password,
                                language,
                                gender,
                                address,
                                city,
                                state,
                                pincode
                            )
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            'ssssssssss',
                            [
                                $name,
                                $email,
                                $mobile,
                                $hashed,
                                $language,
                                $gender,
                                $address,
                                $city,
                                $state,
                                $pincode
                            ]
                        );

                        $newUserId = db()->insert_id;

                        session_regenerate_id(true);

                        $_SESSION['user_id'] = $newUserId;
                        $_SESSION['role'] = 'customer';
                        $_SESSION['name'] = $name;

                        set_language($language);

                        redirect('/customer/dashboard.php');
                    }
                }


            /* =========================
               SERVICE PROVIDER REGISTRATION
               ========================= */

            } else {

                $altMobile = trim($_POST['alt_mobile'] ?? '');
                $serviceId = (int)($_POST['service_id'] ?? 0);
                $experience = (int)($_POST['experience'] ?? 0);

                $address = sanitize($_POST['address'] ?? '');
                $city = sanitize($_POST['city'] ?? '');
                $state = sanitize($_POST['state'] ?? '');
                $pincode = trim($_POST['pincode'] ?? '');


                if (
                    $altMobile !== '' &&
                    !is_valid_indian_mobile($altMobile)
                ) {

                    $error = t('error_invalid_alt_mobile');

                } elseif ($serviceId <= 0) {

                    $error = t('error_select_service');

                } elseif (
                    $address === '' ||
                    $city === '' ||
                    $state === ''
                ) {

                    $error = t('error_incomplete_address');

                } elseif (!is_valid_indian_pincode($pincode)) {

                    $error = t('error_invalid_pincode');

                } else {

                    $existing = fetch_one(
                        "SELECT provider_id
                         FROM service_providers
                         WHERE phone = ?",
                        's',
                        [$mobile]
                    );

                    if ($existing) {

                        $error = t('error_provider_mobile_exists');

                    } else {

                        run_query(
                            "INSERT INTO service_providers
                            (
                                name,
                                email,
                                phone,
                                alternate_phone,
                                service_id,
                                years_experience,
                                address,
                                city,
                                state,
                                pincode,
                                password,
                                approval_status,
                                language
                            )
                            VALUES
                            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)",
                            'ssssiissssss',
                            [
                                $name,
                                $email,
                                $mobile,
                                $altMobile ?: null,
                                $serviceId,
                                $experience,
                                $address,
                                $city,
                                $state,
                                $pincode,
                                $hashed,
                                $language
                            ]
                        );

                        $success = t('provider_success_msg');
                    }
                }
            }
        }
    }
}


/* =========================
   LOAD ACTIVE SERVICES
   ========================= */

$services = fetch_all(
    "SELECT *
     FROM service_categories
     WHERE status = 'active'
     ORDER BY service_name"
);

$pageTitle = 'Register';

$authScene = true; // animated background + rider scene
require __DIR__ . '/includes/header.php';
?>


<div class="auth-wrapper">

    <div class="form-card">

        <h1><?= t('register_title') ?></h1>


        <?php if ($error): ?>

            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <?php if ($success): ?>

            <div class="alert alert-success">

                <?= htmlspecialchars($success) ?>

                <a href="<?= BASE_URL ?>/login.php">
                    <?= t('go_to_login') ?> &rarr;
                </a>

            </div>

        <?php endif; ?>


        <?php if (!$success): ?>


            <label
                class="form-hint"
                style="font-weight:700;display:block;margin-bottom:10px;"
            >
                <?= t('register_as') ?>
            </label>


            <!-- CUSTOMER / PROVIDER SWITCH -->

            <div class="role-toggle">

                <label>

                    <input
                        type="radio"
                        name="account_type_switch"
                        value="customer"
                        onchange="toggleForm('customer')"
                        <?= $selectedType === 'customer' ? 'checked' : '' ?>
                    >

                    <span>
                        &#128100; <?= t('customer') ?>
                    </span>

                </label>


                <label>

                    <input
                        type="radio"
                        name="account_type_switch"
                        value="provider"
                        onchange="toggleForm('provider')"
                        <?= $selectedType === 'provider' ? 'checked' : '' ?>
                    >

                    <span>
                        &#129517; <?= t('provider') ?>
                    </span>

                </label>

            </div>


            <!-- =========================
                 CUSTOMER FORM
                 ========================= -->

            <form
                method="POST"
                action="<?= BASE_URL ?>/register.php"
                id="form-customer"
                style="display:<?= $selectedType === 'customer' ? 'block' : 'none' ?>"
            >

                <?= csrf_field() ?>

                <input
                    type="hidden"
                    name="account_type"
                    value="customer"
                >


                <div class="form-group">

                    <label><?= t('full_name') ?></label>

                    <input
                        class="form-control"
                        type="text"
                        name="name"
                        required
                    >

                </div>


                <div class="form-group">

                    <label><?= t('email') ?></label>

                    <input
                        class="form-control"
                        type="email"
                        name="email"
                        required
                    >

                </div>


                <div class="form-group">

                    <label><?= t('mobile') ?></label>

                    <input
                        class="form-control"
                        type="text"
                        name="mobile"
                        data-mobile
                        required
                        placeholder="10-digit mobile number"
                    >

                    <div class="form-hint">
                        <?= t('email_shared_hint') ?>
                    </div>

                </div>


                <!-- GENDER (mandatory) -->

                <div class="form-group">

                    <label><?= t('gender_label') ?></label>

                    <div class="gender-options" role="radiogroup" aria-label="<?= htmlspecialchars(t('gender_label')) ?>">

                        <label class="gender-option">
                            <input
                                type="radio"
                                name="gender"
                                value="Male"
                                required
                                <?= (($_POST['gender'] ?? '') === 'Male' && $selectedType === 'customer') ? 'checked' : '' ?>
                            >
                            <span>
                                <img src="<?= BASE_URL ?>/assets/images/avatars/avatar-boy.svg" alt="">
                                <?= t('gender_male') ?>
                            </span>
                        </label>

                        <label class="gender-option">
                            <input
                                type="radio"
                                name="gender"
                                value="Female"
                                required
                                <?= (($_POST['gender'] ?? '') === 'Female' && $selectedType === 'customer') ? 'checked' : '' ?>
                            >
                            <span>
                                <img src="<?= BASE_URL ?>/assets/images/avatars/avatar-girl.svg" alt="">
                                <?= t('gender_female') ?>
                            </span>
                        </label>

                    </div>

                </div>


                <div class="form-row">

                    <div class="form-group">

                        <label><?= t('password') ?></label>

                        <input
                            class="form-control"
                            type="password"
                            name="password"
                            id="password"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label><?= t('confirm_password') ?></label>

                        <input
                            class="form-control"
                            type="password"
                            name="confirm_password"
                            id="confirm_password"
                            required
                        >

                        <div
                            class="form-error"
                            id="pwdMatchMsg"
                        ></div>

                    </div>

                </div>


                <!-- CUSTOMER ADDRESS -->

                <div class="form-group">

                    <label><?= t('address_label') ?></label>

                    <input
                        class="form-control"
                        type="text"
                        name="address"
                        required
                    >

                </div>


                <div class="form-row">

                    <div class="form-group">

                        <label><?= t('city_label') ?></label>

                        <input
                            class="form-control"
                            type="text"
                            name="city"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label><?= t('state_label') ?></label>

                        <input
                            class="form-control"
                            type="text"
                            name="state"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label><?= t('pincode_label') ?></label>

                        <input
                            class="form-control"
                            type="text"
                            name="pincode"
                            data-pincode
                            required
                        >

                    </div>

                </div>


                <!-- CUSTOMER CAPTCHA -->

                <?php
                $captchaType = 'customer';
                include __DIR__ . '/captcha_field.php';
                ?>


                <button
                    type="submit"
                    class="btn btn-primary btn-block mt-2"
                >
                    <?= t('register_btn') ?>
                </button>

            </form>


            <!-- =========================
                 SERVICE PROVIDER FORM
                 ========================= -->

            <form
                method="POST"
                action="<?= BASE_URL ?>/register.php"
                id="form-provider"
                style="display:<?= $selectedType === 'provider' ? 'block' : 'none' ?>"
            >

                <?= csrf_field() ?>

                <input
                    type="hidden"
                    name="account_type"
                    value="provider"
                >


                <div class="form-group">

                    <label><?= t('full_name') ?></label>

                    <input
                        class="form-control"
                        type="text"
                        name="name"
                        required
                    >

                </div>


                <div class="form-group">

                    <label><?= t('email') ?></label>

                    <input
                        class="form-control"
                        type="email"
                        name="email"
                        required
                    >

                </div>


                <div class="form-row">

                    <div class="form-group">

                        <label><?= t('mobile') ?></label>

                        <input
                            class="form-control"
                            type="text"
                            name="mobile"
                            data-mobile
                            required
                            placeholder="10-digit mobile number"
                        >

                    </div>


                    <div class="form-group">

                        <label><?= t('alt_mobile_label') ?></label>

                        <input
                            class="form-control"
                            type="text"
                            name="alt_mobile"
                            data-mobile
                            placeholder="Optional"
                        >

                    </div>

                </div>


                <div class="form-row">

                    <div class="form-group">

                        <label><?= t('service_category_label') ?></label>

                        <select
                            class="form-control"
                            name="service_id"
                            required
                        >

                            <option value="">
                                <?= t('select_service_placeholder') ?>
                            </option>

                            <?php foreach ($services as $s): ?>

                                <option
                                    value="<?= (int)$s['service_id'] ?>"
                                >
                                    <?= htmlspecialchars($s['service_name']) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="form-group">

                        <label><?= t('experience_label') ?></label>

                        <input
                            class="form-control"
                            type="number"
                            name="experience"
                            min="0"
                            max="60"
                            required
                        >

                    </div>

                </div>


                <div class="form-group">

                    <label><?= t('address_label') ?></label>

                    <input
                        class="form-control"
                        type="text"
                        name="address"
                        required
                    >

                </div>


                <div class="form-row">

                    <div class="form-group">

                        <label><?= t('city_label') ?></label>

                        <input
                            class="form-control"
                            type="text"
                            name="city"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label><?= t('state_label') ?></label>

                        <input
                            class="form-control"
                            type="text"
                            name="state"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label><?= t('pincode_label') ?></label>

                        <input
                            class="form-control"
                            type="text"
                            name="pincode"
                            data-pincode
                            required
                        >

                    </div>

                </div>


                <div class="form-row">

                    <div class="form-group">

                        <label><?= t('password') ?></label>

                        <input
                            class="form-control"
                            type="password"
                            name="password"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label><?= t('confirm_password') ?></label>

                        <input
                            class="form-control"
                            type="password"
                            name="confirm_password"
                            required
                        >

                    </div>

                </div>


                <!-- PROVIDER CAPTCHA -->

                <?php
                $captchaType = 'provider';
                include __DIR__ . '/captcha_field.php';
                ?>


                <div class="form-hint mt-1">
                    <?= t('provider_pending_hint') ?>
                </div>


                <button
                    type="submit"
                    class="btn btn-primary btn-block mt-2"
                >
                    <?= t('register_btn') ?>
                </button>

            </form>


            <div class="auth-links">

                <?= t('have_account') ?>

                <a href="<?= BASE_URL ?>/login.php">
                    <?= t('login') ?>
                </a>

            </div>

        <?php endif; ?>

    </div>

</div>


<script>

function toggleForm(type) {

    document.getElementById('form-customer').style.display =
        (type === 'customer') ? 'block' : 'none';

    document.getElementById('form-provider').style.display =
        (type === 'provider') ? 'block' : 'none';
}

</script>


<?php require __DIR__ . '/includes/footer.php'; ?>