<?php
$captchaType = $captchaType ?? 'customer';
?>

<div class="form-group">

    <label><?= t('captcha') ?></label>

    <div class="captcha-row">

        <img
            id="captchaImg_<?= htmlspecialchars($captchaType) ?>"
            src="<?= BASE_URL ?>/captcha-image.php?type=<?= htmlspecialchars($captchaType) ?>&refresh=1"
            alt="CAPTCHA code"
            width="200"
            height="70"
        >

        <button
            type="button"
            class="captcha-refresh"
            data-captcha-image="captchaImg_<?= htmlspecialchars($captchaType) ?>"
            data-captcha-type="<?= htmlspecialchars($captchaType) ?>"
        >
            Refresh &#8635;
        </button>

    </div>

    <input
        class="form-control mt-1"
        type="text"
        name="captcha"
        required
        placeholder="Enter the code shown above"
        autocomplete="off"
    >

</div>