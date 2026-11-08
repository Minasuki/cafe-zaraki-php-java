<?php
session_start();
// Si no hay sesión, lo pateamos al login
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
        <div class="filtros" style="flex-wrap: wrap;">
            <button class="filtro-btn active" data-filtro="active">Activas <span class="badge">0</span></button>
            <button class="filtro-btn" data-filtro="pending">Pendientes <span class="badge">0</span></button>
            <button class="filtro-btn" data-filtro="preparing">Preparando <span class="badge">0</span></button>
            <button class="filtro-btn" data-filtro="ready">Listas para entregar <span class="badge">0</span></button>
            <button class="filtro-btn" data-filtro="paid">Historial Cobradas</button>
        </div>

        <!-- Filtros de tiempo (Solo aparecen cuando se selecciona "Cobradas") -->
        <div id="filtros-tiempo" style="display: none; gap: 10px; margin-bottom: 20px;">
            <button class="btn-secondary btn-fecha active" data-fecha="today">Hoy</button>
            <button class="btn-secondary btn-fecha" data-fecha="week">Última semana</button>
            <button class="btn-secondary btn-fecha" data-fecha="month">Último mes</button>
        </div>

        <div class="ordenes-grid" id="ordenes-grid">
            <p class="loading">Cargando órdenes...</p>
        </div>
    </main>

    <div class="loading-overlay" id="loading-overlay">✓ Actualizado</div>

    <script src="js/api.js"></script>
    <script src="js/empleado.js"></script>
</body>

</html>