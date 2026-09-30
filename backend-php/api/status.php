<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido. Usa PATCH.']);
    exit;
}

try {
    $order_id = $_GET['id'] ?? null;
    $body = json_decode(file_get_contents('php://input'), true);
    $status = $body['status'] ?? null;

    if (!$order_id || !$status) {
        http_response_code(422);
        echo json_encode(['error' => 'Falta el id en la URL o el status en el body']);
        exit;
    }

    $valid_statuses = ['pending', 'preparing', 'ready', 'paid'];
    if (!in_array($status, $valid_statuses)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Estado inválido. Debe ser uno de: ' . implode(', ', $valid_statuses)
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE orders
        SET status = :status
        WHERE id = :id
        RETURNING id, customer_name, status, total, created_at
    ");
    $stmt->execute([
        ':status' => $status,
        ':id' => $order_id,
    ]);
    $order = $stmt->fetch();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Orden no encontrada']);
        exit;
    }

    $order['id'] = (int) $order['id'];
    $order['total'] = (float) $order['total'];

    echo json_encode($order, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}