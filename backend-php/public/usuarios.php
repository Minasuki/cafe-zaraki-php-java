<?php
session_start();
require_once __DIR__ . '/../auth.php';

// Bloqueo de seguridad: Solo admin puede ver esto
if (!tienePermiso('usuarios')) {
    header('Location: dashboard.php');
    exit;
}
require_once __DIR__ . '/../config.php';

$mensaje = '';

// Procesar la creación de usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $nombre = trim($_POST['nombre_completo']);
    $password = $_POST['password'];
    $rol_id = (int)$_POST['rol_id'];

    if ($username && $nombre && $password && $rol_id) {
        try {
            // Encriptar la contraseña de forma segura
            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO usuarios (username, password_hash, nombre_completo, rol_id) VALUES (:u, :h, :n, :r)");
            $stmt->execute([':u' => $username, ':h' => $hash, ':n' => $nombre, ':r' => $rol_id]);
            $mensaje = "✅ Usuario creado exitosamente.";
        } catch (Exception $e) {
            $mensaje = "❌ Error: El nombre de usuario ya existe o hubo un problema.";
        }
    }
}

// Obtener la lista de usuarios
$stmtU = $pdo->query("SELECT u.id, u.username, u.nombre_completo, r.nombre AS rol FROM usuarios u JOIN roles r ON u.rol_id = r.id ORDER BY u.id DESC");
$usuarios = $stmtU->fetchAll();

// Obtener los roles disponibles
$roles = $pdo->query("SELECT * FROM roles")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios - Café Zaraki</title>
    <link rel="stylesheet" href="css/base.css">
    <style>
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f9f9f9; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; font-size: 0.9rem; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .alert { background: #e8f5e9; color: #2e7d32; padding: 10px; border-radius: 6px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="container">
        <?php if ($mensaje): ?>
            <div class="alert"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>➕ Crear Nuevo Usuario</h3>
            <form method="POST" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Nombre de Usuario (para login)</label>
                    <input type="text" name="username" required placeholder="Ej. juanperez">
                </div>
                <div class="form-group">
                    <label>Nombre Completo</label>
                    <input type="text" name="nombre_completo" required placeholder="Ej. Juan Pérez">
                </div>
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="password" required placeholder="Escribe una contraseña segura">
                </div>
                <div class="form-group">
                    <label>Rol del Usuario</label>
                    <select name="rol_id" required>
                        <option value="">Seleccione un rol...</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= ucfirst($r['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="grid-column: span 2;">
                    <button type="submit" class="btn-success" style="width: 100%; padding: 12px;">Guardar Usuario</button>
                </div>
            </form>
        </div>

        <div class="card">
            <h3>👥 Lista de Usuarios</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Nombre Real</th>
                        <th>Rol</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                            <td><?= htmlspecialchars($u['nombre_completo']) ?></td>
                            <td>
                                <span style="padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; font-weight: bold; 
                                    background: <?= $u['rol'] === 'admin' ? '#ffebee' : '#e3f2fd' ?>; 
                                    color: <?= $u['rol'] === 'admin' ? '#c62828' : '#1565c0' ?>;">
                                    <?= ucfirst($u['rol']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>