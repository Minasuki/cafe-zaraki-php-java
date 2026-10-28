<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

// Lógica para ARCHIVAR un producto (Soft Delete)
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    try {
        // En lugar de borrarlo, le ponemos la fecha actual
        $stmt = $pdo->prepare("UPDATE products SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $mensaje = "Producto archivado correctamente. Ya no aparecerá en el menú.";
    } catch (Exception $e) {
        $error = "Error al archivar: " . $e->getMessage();
    }
}

// Obtener todos los productos
$stmt = $pdo->query("SELECT * FROM products WHERE deleted_at IS NULL ORDER BY id DESC");
$productos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Productos - Café Zaraki</title>
    <link rel="stylesheet" href="css/base.css">
    <style>
        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f9f9f9;
        }

        .acciones {
            display: flex;
            gap: 10px;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.85rem;
            border-radius: 4px;
            text-decoration: none;
            color: white;
        }

        .btn-edit {
            background: #2196f3;
        }

        .btn-delete {
            background: #e53935;
        }
    </style>
</head>

<body>
    <header style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1>📋 Gestión de Productos</h1>
            <div class="subtitle">Café Zaraki</div>
        </div>
        <div>
            <a href="dashboard.php" class="btn-primary" style="text-decoration: none; padding: 8px 16px; border-radius: 8px; margin-right: 10px;">Volver al Panel</a>
            <a href="logout.php" class="btn-danger" style="text-decoration: none; padding: 8px 16px; border-radius: 8px;">Cerrar Sesión</a>
        </div>
    </header>

    <main class="container">
        <?php if (isset($mensaje)): ?>
            <div style="background: #e8f5e9; color: #2e7d32; padding: 10px; border-radius: 6px; margin-top: 20px;"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div style="background: #ffebee; color: #c62828; padding: 10px; border-radius: 6px; margin-top: 20px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Catálogo de Productos Y Activos</h3>
                <div>
                    <a href="productos_archivados.php" class="btn-info" style="text-decoration: none; padding: 10px 16px; border-radius: 6px; color: white; margin-right: 10px;">Ver Archivados</a>
                    <a href="producto_form.php" class="btn-success" style="text-decoration: none; padding: 10px 16px; border-radius: 6px; color: white;">+ Nuevo Producto</a>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $p): ?>
                        <tr>
                            <td><?= $p['id'] ?></td>
                            <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                            <td><?= htmlspecialchars($p['category']) ?></td>
                            <td>$<?= number_format($p['price'], 2) ?></td>
                            <td><?= $p['stock'] >= 9000 ? '∞' : $p['stock'] ?></td>
                            <td class="acciones">
                                <a href="producto_form.php?id=<?= $p['id'] ?>" class="btn-sm btn-edit">Editar</a>
                                <a href="productos.php?eliminar=<?= $p['id'] ?>" class="btn-sm btn-delete" onclick="return confirm('¿Archivar este producto? Desaparecerá del menú pero se guardará en el historial.');">Archivar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>

</html>