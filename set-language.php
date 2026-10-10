<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/lang.php';
$lang = $_GET['lang'] ?? 'en';
set_language($lang);
$return = $_GET['return'] ?? (BASE_URL . '/index.php');
if (!is_string($return) || strpos($return, '/') !== 0 || strpos($return, '//') === 0) $return = BASE_URL . '/index.php';
header('Location: ' . $return);
exit;
