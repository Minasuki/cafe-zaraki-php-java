<?php
// Configuración MariaDB (XAMPP) - Usuario con privilegios mínimos
$host = '127.0.0.1';
$port = '3306';
$dbname = 'cafe_zaraki';
$user = 'app_cafe';              // <-- Nuevo usuario
$password = 'CafeSeguro2026';   // <-- La contraseña que pusiste arriba

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}
?>