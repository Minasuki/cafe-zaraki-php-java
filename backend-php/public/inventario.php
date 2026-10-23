<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

$mensaje = '';

// Procesar el formulario para agregar stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['producto_id'], $_POST['cantidad'])) {
    $pid = (int)$_POST['producto_id'];
    $cantidad = (int)$_POST['cantidad'];

    if ($cantidad > 0) {
        try {
            // Sumar el stock. El trigger se encargará de poner is_available = TRUE automáticamente
            $stmt = $pdo->prepare("UPDATE products SET stock = stock + :cant WHERE id = :id AND stock < 9000");
            $stmt->execute([':cant' => $cantidad, ':id' => $pid]);
            $mensaje = "✅ Stock actualizado correctamente.";
        } catch (Exception $e) {
            $mensaje = "❌ Error: " . $e->getMessage();
        }
    }
}

// Obtener todos los productos (incluyendo los no disponibles)
$stmt = $pdo->query("SELECT id, name, stock, is_available FROM products ORDER BY name");
$productos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario - Café Zaraki</title>
    <link rel="stylesheet" href="css/base.css">
    <style>
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f9f9f9; }
        .badge-off { background: #ffebee; color: #c62828; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; }
        .badge-on { background: #e8f5e9; color: #2e7d32; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; }
        .form-inline { display: flex; gap: 10px; align-items: center; }
        .form-inline input { width: 80px; padding: 6px; border: 1px solid #ccc; border-radius: 4px; }
    </style>
</head>
<body>
    <header style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1>📦 Gestión de Inventario</h1>
            <div class="subtitle">Café Zaraki</div>
        </div>
        <div>
            <a href="dashboard.php" class="btn-primary" style="text-decoration: none; padding: 8px 16px; border-radius: 8px; margin-right: 10px;">Volver al Panel</a>
            <a href="logout.php" class="btn-danger" style="text-decoration: none; padding: 8px 16px; border-radius: 8px;">Cerrar Sesión</a>
        </div>
    </header>

    <main class="container">
        <?php if ($mensaje): ?>
            <div style="background: #e8f5e9; color: #2e7d32; padding: 10px; border-radius: 6px; margin-top: 20px;"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>Reabastecer Productos</h3>
            <p style="color: #666; margin-bottom: 15px; font-size: 0.9rem;">Los productos con stock "9999" son de preparación ilimitada. Los productos con stock "0" no se muestran en el menú del cliente hasta que se les agregue stock.</p>
            
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Stock Actual</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $p): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                            <td>
                                <?= $p['stock'] >= 9000 ? '∞ (Ilimitado)' : (int)$p['stock'] ?>
                            </td>
                            <td>
                                <?php if ($p['is_available']): ?>
                                    <span class="badge-on">Visible</span>
                                <?php else: ?>
                                    <span class="badge-off">Oculto</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($p['stock'] < 9000): ?>
                                    <form method="POST" class="form-inline">
                                        <input type="hidden" name="producto_id" value="<?= $p['id'] ?>">
                                        <input type="number" name="cantidad" value="10" min="1" required>
                                        <button type="submit" class="btn-success" style="padding: 6px 12px;">+ Agregar</button>
                                    </form>
                                <?php else: ?>
                                    <em style="color: #999; font-size: 0.85rem;">No requiere gestión</em>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>