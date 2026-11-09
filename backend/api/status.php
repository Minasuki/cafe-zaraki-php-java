<?php
// Iniciar sesión de forma segura
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Atrapar cualquier error de PHP y convertirlo en texto para que no rompa el JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

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

    $valid_statuses = ['pending', 'preparing', 'ready', 'paid', 'cancelled'];
    if (!in_array($status, $valid_statuses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Estado inválido. Debe ser uno de: ' . implode(', ', $valid_statuses)]);
        exit;
    }

    // Si el estado es 'paid', usamos el Procedimiento Almacenado
    if ($status === 'paid') {
        $stmt = $pdo->prepare("CALL sp_pagar_orden(:id)");
        $stmt->execute([':id' => $order_id]);

        // Registrar qué cajero atendió
        if (isset($_SESSION['usuario_id'])) {
            $stmtCajero = $pdo->prepare("UPDATE orders SET cajero_id = :cajero WHERE id = :id");
            $stmtCajero->execute([
                ':cajero' => $_SESSION['usuario_id'],
                ':id' => $order_id
            ]);
        }
    } else {
        // Para otros estados (preparing, ready, cancelled)
        $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $order_id]);
    }

    // Obtener la orden actualizada
    $stmtGet = $pdo->prepare("SELECT id, customer_name, status, total, created_at FROM orders WHERE id = :id");
    $stmtGet->execute([':id' => $order_id]);
    $order = $stmtGet->fetch();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Orden no encontrada']);
        exit;
    }

    $order['id'] = (int) $order['id'];
    $order['total'] = (float) $order['total'];

    echo json_encode($order, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // Si el Procedimiento Almacenado lanza un error (ej. Stock insuficiente)
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    // Atrapa CUALQUIER otro error fatal (ej. una función mal escrita)
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}