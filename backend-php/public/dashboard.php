<?php
session_start();

// Proteger la vista: Si no está logueado, lo manda al login
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Café Zaraki</title>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/empleado.css">
</head>
<body>
    <header style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1>👨‍🍳 Panel de Control</h1>
            <div class="subtitle">Bienvenido, <?= htmlspecialchars($_SESSION['usuario_nombre']) ?></div>
        </div>
        <div>
            <a href="empleado.html" class="btn-primary" style="text-decoration: none; padding: 8px 16px; border-radius: 8px; margin-right: 10px;">Ver Órdenes</a>
            <a href="logout.php" class="btn-danger" style="text-decoration: none; padding: 8px 16px; border-radius: 8px;">Cerrar Sesión</a>
        </div>
    </header>

    <main class="container">
        <div style="text-align: center; margin-top: 50px;">
            <h2>Bienvenido al sistema</h2>
            <p>Has iniciado sesión correctamente. Desde aquí puedes gestionar los pedidos.</p>
            <!-- Aquí a futuro podrías agregar tarjetas de reportes (Ventas del día, etc.) -->
        </div>
    </main>
</body>
</html>