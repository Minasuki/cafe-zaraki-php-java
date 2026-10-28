<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // 1. Añadir 'stock' al SELECT
    $stmt = $pdo->query("
    SELECT id, name, description, price, category, stock
    FROM products
    WHERE is_available = true AND stock > 0 AND deleted_at IS NULL
    ORDER BY category, name
");
    $products = $stmt->fetchAll();

    // 2. Convertir el stock a número entero
    foreach ($products as &$p) {
        $p['price'] = (float) $p['price'];
        $p['id'] = (int) $p['id'];
        $p['stock'] = (int) $p['stock']; // <-- Agrega esta línea
    }

    echo json_encode($products, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
