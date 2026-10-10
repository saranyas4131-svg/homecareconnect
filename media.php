<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
$id = (int)($_GET['id'] ?? 0);
$media = fetch_one("SELECT mime_type, image_data, file_size FROM media WHERE media_id = ? AND is_active = 1", 'i', [$id]);
if (!$media) { http_response_code(404); exit; }
header('Content-Type: ' . $media['mime_type']);
header('Content-Length: ' . (int)$media['file_size']);
header('Cache-Control: public, max-age=86400');
echo $media['image_data'];
