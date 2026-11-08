<?php
// backend-php/auth.php
function tienePermiso($permiso) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $rol = $_SESSION['usuario_rol'] ?? 'invitado';
    
    // Definimos qué puede hacer cada rol
    $permisos = [
        'admin' => ['crear', 'editar', 'archivar', 'ver', 'cobrar'],
        'empleado' => ['ver', 'cobrar'] // Solo puede ver y cobrar
    ];

    return in_array($permiso, $permisos[$rol] ?? []);
}
?>