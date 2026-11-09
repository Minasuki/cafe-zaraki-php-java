<?php
function tienePermiso($permiso) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $rol = $_SESSION['usuario_rol'] ?? 'invitado';
    
    $permisos = [
        'admin' => ['crear', 'editar', 'archivar', 'ver', 'cobrar', 'usuarios'],
        'empleado' => ['ver', 'cobrar']
    ];

    return in_array($permiso, $permisos[$rol] ?? []);
}