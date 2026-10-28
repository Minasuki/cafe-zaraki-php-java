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
    <?php include 'header.php'; ?>

    <main class="container">
        <div style="text-align: center; margin-top: 50px;">
            <h2>Bienvenido al sistema</h2>
            <p>Has iniciado sesión correctamente. Desde aquí puedes gestionar los pedidos.</p>
            <!-- Aquí a futuro podrías agregar tarjetas de reportes (Ventas del día, etc.) -->
        </div>
    </main>
</body>

</html>