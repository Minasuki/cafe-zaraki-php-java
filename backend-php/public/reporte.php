<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

try {
    // Reporte 1: Ventas totales por producto (JOIN y GROUP BY)
    $query1 = "
        SELECT p.name AS producto, 
               SUM(oi.quantity) AS total_vendido, 
               SUM(oi.quantity * oi.unit_price) AS ingresos
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.status = 'paid'
        GROUP BY p.id, p.name
        ORDER BY total_vendido DESC
    ";
    $stmt1 = $pdo->query($query1);
    $ventasPorProducto = $stmt1->fetchAll();

    // Reporte 2: Productos estrella (Usando HAVING para filtrar)
    // Muestra solo los productos que han vendido MÁS de 2 unidades
    $query2 = "
        SELECT p.name AS producto, 
               SUM(oi.quantity) AS total_vendido
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.status = 'paid'
        GROUP BY p.id, p.name
        HAVING SUM(oi.quantity) > 2
        ORDER BY total_vendido DESC
    ";
    $stmt2 = $pdo->query($query2);
    $productosEstrella = $stmt2->fetchAll();

    // Reporte 3: Ingresos totales
    $query3 = "SELECT SUM(total) AS ingresos_totales FROM orders WHERE status = 'paid'";
    $ingresosTotales = $pdo->query($query3)->fetch()['ingresos_totales'] ?? 0;

} catch (Exception $e) {
    die("Error en el reporte: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes - Café Zaraki</title>
    <link rel="stylesheet" href="css/base.css">
    <style>
        .reporte-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 20px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .card h3 { margin-bottom: 15px; color: var(--color-espresso); border-bottom: 2px solid var(--color-latte); padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f9f9f9; font-weight: 600; }
        .ingresos-box { background: var(--color-cafe); color: white; padding: 20px; border-radius: 8px; text-align: center; margin-top: 20px; }
        .ingresos-box h2 { font-size: 2rem; margin: 0; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="container">
        <div class="ingresos-box">
            <h2>$<?= number_format($ingresosTotales, 2) ?></h2>
            <p>Ingresos Totales (Órdenes Pagadas)</p>
        </div>

        <div class="reporte-grid">
            <!-- Tabla 1: Ventas por Producto -->
            <div class="card">
                <h3>Ventas por Producto</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Vendidos</th>
                            <th>Ingresos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ventasPorProducto)): ?>
                            <tr><td colspan="3">Aún no hay ventas registradas.</td></tr>
                        <?php else: ?>
                            <?php foreach ($ventasPorProducto as $v): ?>
                                <tr>
                                    <td><?= htmlspecialchars($v['producto']) ?></td>
                                    <td><?= $v['total_vendido'] ?></td>
                                    <td>$<?= number_format($v['ingresos'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Tabla 2: Productos Estrella (HAVING) -->
            <div class="card">
                <h3>Productos Estrella (Más de 2 vendidos)</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Total Vendido</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($productosEstrella)): ?>
                            <tr><td colspan="2">Ningún producto ha superado las 2 ventas.</td></tr>
                        <?php else: ?>
                            <?php foreach ($productosEstrella as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['producto']) ?></td>
                                    <td><?= $p['total_vendido'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>