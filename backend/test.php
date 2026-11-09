<?php
require_once 'config.php';

try {
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM products");
    $row = $stmt->fetch();
    echo "✅ Conexión OK. Productos en la BD: " . $row['total'];
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>