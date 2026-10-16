<?php
// Configuración MariaDB (XAMPP)
$host = '127.0.0.1';
$port = '3306';       // Puerto de MariaDB
$dbname = 'cafe_zaraki';
$user = 'root';       // Usuario por defecto de XAMPP
$password = '';       // Sin contraseña por defecto

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