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
        $date_filter = $_GET['date'] ?? null;

        // 1. Detalle de una orden
        if ($order_id) {
            $stmt = $pdo->prepare("
                SELECT o.id, o.customer_name, o.status, o.total, o.created_at, u.nombre_completo AS cajero
                FROM orders o LEFT JOIN usuarios u ON o.cajero_id = u.id
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
                FROM order_items oi JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = :id
            ");
            $stmtItems->execute([':id' => $order_id]);
            $order['items'] = $stmtItems->fetchAll();

            $order['id'] = (int) $order['id'];
            $order['total'] = (float) $order['total'];
            echo json_encode($order, JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 2. Listar órdenes (Solo filtramos por FECHA en SQL, el estado lo filtra JS)
        $query = "SELECT o.id, o.customer_name, o.status, o.total, o.created_at, u.nombre_completo AS cajero 
                  FROM orders o LEFT JOIN usuarios u ON o.cajero_id = u.id WHERE 1=1";
        $params = [];

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
    // ... (El método POST se queda exactamente igual que en tu versión anterior) ...

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
        ");
        $stmt->execute([
            ':name' => $body['customer_name'],
            ':total' => $total,
        ]);
        $order_id = $pdo->lastInsertId();

        $stmt2 = $pdo->prepare("SELECT created_at FROM orders WHERE id = :id");
        $stmt2->execute([':id' => $order_id]);
        $created_at = $stmt2->fetch()['created_at'];

        // Insertar items
        $stmtItem = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, unit_price, customizations)
            VALUES (:order_id, :product_id, :quantity, :unit_price, :customizations)
        ");

        foreach ($body['items'] as $item) {
            // Si el item trae personalizaciones, las convertimos a texto. Si no, guardamos NULL.
            $custom = isset($item['customizations']) && is_array($item['customizations'])
                ? implode(', ', $item['customizations'])
                : null;

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
