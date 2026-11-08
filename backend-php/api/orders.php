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
    if ($method === 'GET') {
        $order_id = $_GET['id'] ?? null;
        $status = $_GET['status'] ?? null;
        $date_filter = $_GET['date'] ?? null;

        // 1. Detalle de una orden
        if ($order_id) {
            $stmt = $pdo->prepare("
                SELECT o.id, o.customer_name, o.status, o.total, o.created_at, u.nombre_completo AS cajero
                FROM orders o
                LEFT JOIN usuarios u ON o.cajero_id = u.id
                WHERE o.id = :id
            ");
            $stmt->execute([':id' => $order_id]);
            $order = $stmt->fetch();

            if (!$order) {
                http_response_code(404);
                echo json_encode(['error' => 'Orden no encontrada']);
                exit;
            }

            $stmtItems = $pdo->prepare("
                SELECT oi.id, oi.product_id, oi.quantity, oi.unit_price, oi.customizations, p.name AS product_name
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

        // 2. Lista de órdenes con filtros
        $query = "SELECT o.id, o.customer_name, o.status, o.total, o.created_at, u.nombre_completo AS cajero 
                  FROM orders o 
                  LEFT JOIN usuarios u ON o.cajero_id = u.id 
                  WHERE 1=1";
        $params = [];

        if ($status) {
            $query .= " AND o.status = :status";
            $params[':status'] = $status;
        }

        if ($date_filter === 'today') {
            $query .= " AND DATE(o.created_at) = CURRENT_DATE";
        } elseif ($date_filter === 'week') {
            $query .= " AND o.created_at >= NOW() - INTERVAL 7 DAY";
        } elseif ($date_filter === 'month') {
            $query .= " AND o.created_at >= NOW() - INTERVAL 1 MONTH";
        }

        $query .= " ORDER BY o.created_at ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();

        foreach ($orders as &$o) {
            $o['id'] = (int) $o['id'];
            $o['total'] = (float) $o['total'];
        }

        echo json_encode($orders, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true);

        if (!$body || !isset($body['customer_name']) || !isset($body['items']) || empty($body['items'])) {
            http_response_code(422);
            echo json_encode(['error' => 'Faltan datos o el carrito está vacío']);
            exit;
        }

        $total = 0;
        foreach ($body['items'] as $item) {
            $total += $item['unit_price'] * $item['quantity'];
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO orders (customer_name, total, status) VALUES (:name, :total, 'pending')");
        $stmt->execute([':name' => $body['customer_name'], ':total' => $total]);
        $order_id = $pdo->lastInsertId();

        $stmt2 = $pdo->prepare("SELECT created_at FROM orders WHERE id = :id");
        $stmt2->execute([':id' => $order_id]);
        $created_at = $stmt2->fetch()['created_at'];

        $stmtItem = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, unit_price, customizations)
            VALUES (:order_id, :product_id, :quantity, :unit_price, :customizations)
        ");

        foreach ($body['items'] as $item) {
            $custom = isset($item['customizations']) && is_array($item['customizations']) 
                      ? implode(', ', $item['customizations']) : null;

            $stmtItem->execute([
                ':order_id' => $order_id,
                ':product_id' => $item['product_id'],
                ':quantity' => $item['quantity'],
                ':unit_price' => $item['unit_price'],
                ':customizations' => $custom
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

    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    // Si hay un error en SQL, lo devolvemos como JSON para que JS no falle
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}