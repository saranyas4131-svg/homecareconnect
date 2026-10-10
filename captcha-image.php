<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================
   CAPTCHA TYPE
   ========================= */

$type = $_GET['type'] ?? 'customer';

if ($type !== 'customer' && $type !== 'provider') {
    $type = 'customer';
}

$sessionKey = 'captcha_' . $type;


/* =========================
   GENERATE RANDOM CODE
   ========================= */

$characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

$code = '';

for ($i = 0; $i < 5; $i++) {
    $code .= $characters[random_int(0, strlen($characters) - 1)];
}


/* =========================
   SAVE CAPTCHA IN SESSION
   ========================= */

$_SESSION[$sessionKey] = $code;
$_SESSION[$sessionKey . '_time'] = time();


/* =========================
   SEND SVG IMAGE
   ========================= */

header('Content-Type: image/svg+xml; charset=UTF-8');

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

header('Pragma: no-cache');


$positions = [
    [25, 47, -8],
    [62, 42, 7],
    [99, 47, -5],
    [136, 42, 8],
    [173, 47, -7]
];

$colors = [
    '#0F766E',
    '#4338CA'
];

echo '<?xml version="1.0" encoding="UTF-8"?>';

?>

<svg
    xmlns="http://www.w3.org/2000/svg"
    width="200"
    height="70"
    viewBox="0 0 200 70"
>

    <rect
        x="0"
        y="0"
        width="200"
        height="70"
        rx="8"
        fill="#F8FAFC"
    />

    <rect
        x="1"
        y="1"
        width="198"
        height="68"
        rx="8"
        fill="none"
        stroke="#0F766E"
        stroke-width="2"
    />

    <line
        x1="5"
        y1="15"
        x2="195"
        y2="55"
        stroke="#CBD5E1"
        stroke-width="2"
    />

    <line
        x1="5"
        y1="55"
        x2="195"
        y2="12"
        stroke="#E2E8F0"
        stroke-width="2"
    />

    <?php for ($i = 0; $i < 5; $i++): ?>

        <text
            x="<?= $positions[$i][0] ?>"
            y="<?= $positions[$i][1] ?>"
            fill="<?= $colors[$i % 2] ?>"
            font-family="Arial, sans-serif"
            font-size="25"
            font-weight="bold"
            text-anchor="middle"
            transform="rotate(
                <?= $positions[$i][2] ?>
                <?= $positions[$i][0] ?>
                <?= $positions[$i][1] ?>
            )"
        ><?= htmlspecialchars($code[$i], ENT_QUOTES, 'UTF-8') ?></text>

    <?php endfor; ?>

    <circle cx="12" cy="10" r="2" fill="#94A3B8"/>
    <circle cx="42" cy="60" r="2" fill="#94A3B8"/>
    <circle cx="78" cy="12" r="2" fill="#94A3B8"/>
    <circle cx="112" cy="60" r="2" fill="#94A3B8"/>
    <circle cx="150" cy="10" r="2" fill="#94A3B8"/>
    <circle cx="188" cy="60" r="2" fill="#94A3B8"/>

</svg>