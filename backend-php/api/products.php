<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $stmt = $pdo->query("
        SELECT id, name, description, price, category
        FROM products
        WHERE is_available = true
        ORDER BY category, name
    ");
    $products = $stmt->fetchAll();

    // Convertir tipos para que el JSON sea correcto
    foreach ($products as &$p) {
        $p['price'] = (float) $p['price'];
        $p['id'] = (int) $p['id'];
    }

    echo json_encode($products, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}