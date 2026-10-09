<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_admin();
header('Content-Type: application/json');
if (!is_post()) { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit; }
if (!hash_equals(csrf_token(), (string)($_POST['_csrf'] ?? ''))) { http_response_code(419); echo json_encode(['error' => 'Session expired']); exit; }
try {
    $path = save_image($_FILES['file'] ?? null, 'inline', 8);
    if (!$path) throw new RuntimeException('No file received.');
    echo json_encode(['location' => url($path)]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
