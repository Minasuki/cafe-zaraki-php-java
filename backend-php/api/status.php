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
        echo json_encode(['error' => 'Estado inválido. Debe ser uno de: ' . implode(', ', $valid_statuses)]);
        exit;
    }

    $pdo->beginTransaction();

    try {
        // 1. Verificar el estado actual de la orden (para no descontar stock dos veces)
        $stmtCheck = $pdo->prepare("SELECT status FROM orders WHERE id = :id");
        $stmtCheck->execute([':id' => $order_id]);
        $ordenActual = $stmtCheck->fetch();

        if (!$ordenActual) {
            throw new Exception("Orden no encontrada");
        }

        // 2. Actualizar el estado de la orden
        $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $order_id]);

        // 3. Si el nuevo estado es 'paid' (Cobrado) y antes NO lo estaba, descontar stock
        if ($status === 'paid' && $ordenActual['status'] !== 'paid') {
            
            // Obtener todos los productos de esta orden
            $stmtItems = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = :id");
            $stmtItems->execute([':id' => $order_id]);
            $items = $stmtItems->fetchAll();

            if (empty($items)) {
                throw new Exception("La orden no tiene productos");
            }

            // Preparar la consulta para restar stock
            // Usamos "AND stock >= :qty" para evitar stock negativo
            $stmtStock = $pdo->prepare("
                UPDATE products 
                SET stock = stock - :qty 
                WHERE id = :pid AND stock >= :qty
            ");

            foreach ($items as $item) {
                $stmtStock->execute([
                    ':qty' => $item['quantity'],
                    ':pid' => $item['product_id']
                ]);

                // Si rowCount() es 0, significa que no se actualizó nada (no hay stock)
                if ($stmtStock->rowCount() === 0) {
                    throw new Exception("Stock insuficiente para el producto ID: " . $item['product_id']);
                }
            }
        }

        // Si todo salió bien, guardamos los cambios permanentemente
        $pdo->commit();

        // Obtener la orden actualizada para devolverla
        $stmtGet = $pdo->prepare("SELECT id, customer_name, status, total, created_at FROM orders WHERE id = :id");
        $stmtGet->execute([':id' => $order_id]);
        $order = $stmtGet->fetch();

        $order['id'] = (int) $order['id'];
        $order['total'] = (float) $order['total'];

        echo json_encode($order, JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        // Si algo falló, revertimos TODOS los cambios (Rollback)
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}