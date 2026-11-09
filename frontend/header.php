<?php
// Solo iniciamos sesión si no está activa para evitar errores
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../backend/auth.php';
?>



<header style="display: flex; justify-content: space-between; align-items: center; background: var(--color-espresso); padding: 15px 24px; color: white;">
    <div>
        <h1 style="font-size: 1.5rem; margin: 0;">☕ Café Zaraki</h1>
        <div style="font-size: 0.85rem; opacity: 0.75;">Panel Administrativo</div>
    </div>
    <nav style="display: flex; gap: 10px; align-items: center;">
        <?php if (isset($_SESSION['usuario_id'])): ?>
            <span style="font-size: 0.9rem; margin-right: 15px;">Hola, <strong><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></strong></span>
            <a href="dashboard.php" style="color: white; text-decoration: none; padding: 8px 12px; border-radius: 6px; background: rgba(255,255,255,0.1);">Inicio</a>
            <?php if (tienePermiso('ver')): ?>
                <a href="empleado.php" style="color: white; text-decoration: none; padding: 8px 12px; border-radius: 6px; background: rgba(255,255,255,0.1);">Órdenes</a>
                <a href="reporte.php" style="color: white; text-decoration: none; padding: 8px 12px; border-radius: 6px; background: rgba(255,255,255,0.1);">Reportes</a>
            <?php endif; ?>
            <?php if (tienePermiso('crear')): // Solo admin 
            ?>
                <a href="productos.php" style="color: white; text-decoration: none; padding: 8px 12px; border-radius: 6px; background: rgba(255,255,255,0.1);">Productos</a>
                <a href="inventario.php" style="color: white; text-decoration: none; padding: 8px 12px; border-radius: 6px; background: rgba(255,255,255,0.1);">Inventario</a>
            <?php endif; ?>
            <?php if (tienePermiso('usuarios')): ?>
                <a href="usuarios.php" style="color: white; text-decoration: none; padding: 8px 12px; border-radius: 6px; background: rgba(255,255,255,0.1);">Usuarios</a>
            <?php endif; ?>
        <?php endif; ?>
        <a href="logout.php" style="color: #ff8a80; text-decoration: none; padding: 8px 12px; border-radius: 6px; font-weight: bold;">Salir</a>

    </nav>
</header>