<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

$mensaje = '';

// Lógica para RESTAURAR un producto
if (isset($_GET['restaurar'])) {
    $id = (int)$_GET['restaurar'];
    try {
        $stmt = $pdo->prepare("UPDATE products SET deleted_at = NULL WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $mensaje = "✅ Producto restaurado correctamente. Ya es visible de nuevo.";
    } catch (Exception $e) {
        $error = "Error al restaurar: " . $e->getMessage();
    }
}

// Obtener SOLO los productos archivados
$stmt = $pdo->query("SELECT * FROM products WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC");
$productos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos Archivados - Café Zaraki</title>
    <link rel="stylesheet" href="css/base.css">
    <style>
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f9f9f9; }
        .btn-sm { padding: 5px 10px; font-size: 0.85rem; border-radius: 4px; text-decoration: none; color: white; background: #4caf50; }
    </style>
</head>
<body>
   <?php include 'header.php'; ?>

    <main class="container">
        <?php if ($mensaje): ?>
            <div style="background: #e8f5e9; color: #2e7d32; padding: 10px; border-radius: 6px; margin-top: 20px;"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <div class="card">
            <p style="color: #666; margin-bottom: 15px; font-size: 0.9rem;">Estos productos fueron eliminados del menú, pero se conservan aquí para no perder el historial de ventas ni la información del negocio.</p>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Fecha de Archivado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($productos)): ?>
                        <tr><td colspan="6" style="text-align: center; color: #999;">No hay productos archivados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($productos as $p): ?>
                            <tr>
                                <td><?= $p['id'] ?></td>
                                <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                                <td><?= htmlspecialchars($p['category']) ?></td>
                                <td>$<?= number_format($p['price'], 2) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($p['deleted_at'])) ?></td>
                                <td>
                                    <a href="productos_archivados.php?restaurar=<?= $p['id'] ?>" class="btn-sm" onclick="return confirm('¿Restaurar este producto al menú activo?');">Restaurar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>