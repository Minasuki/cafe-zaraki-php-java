<?php
session_start();
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar que el usuario tenga permiso para cobrar
if (!tienePermiso('cobrar')) {
    http_response_code(403);
    echo json_encode(['error' => 'No tienes permiso para realizar esta acción.']);
    exit;
}

session_start(); // Iniciar sesión para saber quién cobra

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

    // Si el estado al que quiere cambiar es 'paid', usamos el Procedimiento Almacenado
    if ($status === 'paid') {
        $stmt = $pdo->prepare("CALL sp_pagar_orden(:id)");
        $stmt->execute([':id' => $order_id]);

        if (isset($_SESSION['usuario_id'])) {
            $stmtCajero = $pdo->prepare("UPDATE orders SET cajero_id = :cajero WHERE id = :id");
            $stmtCajero->execute([
                ':cajero' => $_SESSION['usuario_id'],
                ':id' => $order_id
            ]);
        }
    } else {
        // Para los otros estados (preparing, ready), seguimos usando PHP normal
        $valid_statuses = ['pending', 'preparing', 'ready'];
        if (!in_array($status, $valid_statuses)) {
            http_response_code(400);
            echo json_encode(['error' => 'Estado inválido.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $order_id]);
    }

    // Obtener la orden actualizada para devolverla al frontend
    $stmtGet = $pdo->prepare("SELECT id, customer_name, status, total, created_at FROM orders WHERE id = :id");
    $stmtGet->execute([':id' => $order_id]);
    $order = $stmtGet->fetch();

    $order['id'] = (int) $order['id'];
    $order['total'] = (float) $order['total'];

    echo json_encode($order, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    // Si el Procedimiento Almacenado lanza un error (SIGNAL), PDO lo captura aquí
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
