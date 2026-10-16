<?php
require_once 'config.php';

try {
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM products");
    $row = $stmt->fetch();
    echo "✅ Conectado a MariaDB. Productos: " . $row['total'];
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}