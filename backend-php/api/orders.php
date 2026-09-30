<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    // ============ GET: listar órdenes ============
    if ($method === 'GET') {
        $status = $_GET['status'] ?? null;
        $order_id = $_GET['id'] ?? null;

        // GET /orders.php?id=1 → detalle de una orden
        if ($order_id) {
            $stmt = $pdo->prepare("
                SELECT id, customer_name, status, total, created_at
                FROM orders
                WHERE id = :id
            ");
            $stmt->execute([':id' => $order_id]);
            $order = $stmt->fetch();

            if (!$order) {
                http_response_code(404);
                echo json_encode(['error' => 'Orden no encontrada']);
                exit;
            }

            // Traer los ítems con nombre del producto
            $stmtItems = $pdo->prepare("
                SELECT oi.id, oi.product_id, oi.quantity, oi.unit_price, p.name AS product_name
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = :id
            ");
            $stmtItems->execute([':id' => $order_id]);
            $items = $stmtItems->fetchAll();

            $order['id'] = (int) $order['id'];
            $order['total'] = (float) $order['total'];
            foreach ($items as &$it) {
                $it['id'] = (int) $it['id'];
                $it['product_id'] = (int) $it['product_id'];
                $it['quantity'] = (int) $it['quantity'];
                $it['unit_price'] = (float) $it['unit_price'];
            }
            $order['items'] = $items;

            echo json_encode($order, JSON_UNESCAPED_UNICODE);
            exit;
        }

        // GET /orders.php?status=pending → listar órdenes (con filtro opcional)
        if ($status) {
            $stmt = $pdo->prepare("
                SELECT id, customer_name, status, total, created_at
                FROM orders
                WHERE status = :status
                ORDER BY created_at ASC
            ");
            $stmt->execute([':status' => $status]);
        } else {
            $stmt = $pdo->query("
                SELECT id, customer_name, status, total, created_at
                FROM orders
                ORDER BY created_at ASC
            ");
        }

        $orders = $stmt->fetchAll();
        foreach ($orders as &$o) {
            $o['id'] = (int) $o['id'];
            $o['total'] = (float) $o['total'];
        }

        echo json_encode($orders, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ============ POST: crear orden ============
    if ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true);

        if (!$body || !isset($body['customer_name']) || !isset($body['items'])) {
            http_response_code(422);
            echo json_encode(['error' => 'Faltan customer_name o items']);
            exit;
        }

        if (empty($body['items'])) {
            http_response_code(422);
            echo json_encode(['error' => 'La orden no puede estar vacía']);
            exit;
        }

        // Calcular total
        $total = 0;
        foreach ($body['items'] as $item) {
            $total += $item['unit_price'] * $item['quantity'];
        }

        $pdo->beginTransaction();

        // Insertar orden
        $stmt = $pdo->prepare("
            INSERT INTO orders (customer_name, total, status)
            VALUES (:name, :total, 'pending')
            RETURNING id, created_at
        ");
        $stmt->execute([
            ':name' => $body['customer_name'],
            ':total' => $total,
        ]);
        $order = $stmt->fetch();
        $order_id = $order['id'];
        $created_at = $order['created_at'];

        // Insertar items
        $stmtItem = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, unit_price)
            VALUES (:order_id, :product_id, :quantity, :unit_price)
        ");

        foreach ($body['items'] as $item) {
            $stmtItem->execute([
                ':order_id' => $order_id,
                ':product_id' => $item['product_id'],
                ':quantity' => $item['quantity'],
                ':unit_price' => $item['unit_price'],
            ]);
        }

        $pdo->commit();

        http_response_code(201);
        echo json_encode([
            'id' => (int) $order_id,
            'customer_name' => $body['customer_name'],
            'status' => 'pending',
            'total' => (float) $total,
            'created_at' => $created_at,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Método no soportado
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}