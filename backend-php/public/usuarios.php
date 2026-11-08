<?php
session_start();
require_once __DIR__ . '/../auth.php';

if (!tienePermiso('usuarios')) {
    header('Location: dashboard.php');
    exit;
}
require_once __DIR__ . '/../config.php';

$mensaje = '';
$usuario_editar = null;

// --- LÓGICA PARA CARGAR EL USUARIO A EDITAR ---
if (isset($_GET['editar'])) {
    $stmtEdit = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
    $stmtEdit->execute([':id' => $_GET['editar']]);
    $usuario_editar = $stmtEdit->fetch();
}

// --- LÓGICA PARA GUARDAR (CREAR O EDITAR) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $nombre = trim($_POST['nombre_completo']);
    $password = $_POST['password'];
    $rol_id = (int)$_POST['rol_id'];
    $id_editar = $_POST['id_editar'] ?? null;

    try {
        if ($id_editar) {
            // ES UNA EDICIÓN
            if (!empty($password)) {
                // Si escribió una contraseña nueva, la actualizamos
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET username=:u, nombre_completo=:n, password_hash=:h, rol_id=:r WHERE id=:id");
                $stmt->execute([':u' => $username, ':n' => $nombre, ':h' => $hash, ':r' => $rol_id, ':id' => $id_editar]);
            } else {
                // Si dejó la contraseña en blanco, no la tocamos
                $stmt = $pdo->prepare("UPDATE usuarios SET username=:u, nombre_completo=:n, rol_id=:r WHERE id=:id");
                $stmt->execute([':u' => $username, ':n' => $nombre, ':r' => $rol_id, ':id' => $id_editar]);
            }
            $mensaje = "✅ Usuario actualizado exitosamente.";
            $usuario_editar = null; // Limpiamos el formulario
        } else {
            // ES UNA CREACIÓN NUEVA
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO usuarios (username, password_hash, nombre_completo, rol_id) VALUES (:u, :h, :n, :r)");
            $stmt->execute([':u' => $username, ':h' => $hash, ':n' => $nombre, ':r' => $rol_id]);
            $mensaje = "✅ Usuario creado exitosamente. Se le asignaron privilegios por defecto.";
        }
    } catch (Exception $e) {
        $mensaje = "❌ Error: El nombre de usuario ya existe o hubo un problema.";
    }
}

// Obtener la lista de usuarios
$stmtU = $pdo->query("SELECT u.id, u.username, u.nombre_completo, r.nombre AS rol FROM usuarios u JOIN roles r ON u.rol_id = r.id ORDER BY u.id DESC");
$usuarios = $stmtU->fetchAll();

// Obtener SOLO los roles admin y empleado (Filtro solicitado)
$roles = $pdo->query("SELECT * FROM roles WHERE nombre IN ('admin', 'empleado')")->fetchAll();
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
        .btn-edit { background: #2196f3; color: white; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 0.85rem; }
        .form-edicion { background: #fff3e0; border: 2px solid #ff9800; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="container">
        <?php if ($mensaje): ?>
            <div class="alert"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <div class="card <?= $usuario_editar ? 'form-edicion' : '' ?>">
            <h3><?= $usuario_editar ? '✏️ Editando Usuario: ' . htmlspecialchars($usuario_editar['username']) : '➕ Crear Nuevo Usuario' ?></h3>
            
            <form method="POST" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <!-- Campo oculto para saber si estamos editando -->
                <?php if ($usuario_editar): ?>
                    <input type="hidden" name="id_editar" value="<?= $usuario_editar['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Nombre de Usuario (para login)</label>
                    <input type="text" name="username" required value="<?= $usuario_editar ? htmlspecialchars($usuario_editar['username']) : '' ?>">
                </div>
                <div class="form-group">
                    <label>Nombre Completo</label>
                    <input type="text" name="nombre_completo" required value="<?= $usuario_editar ? htmlspecialchars($usuario_editar['nombre_completo']) : '' ?>">
                </div>
                <div class="form-group">
                    <label>Contraseña <?= $usuario_editar ? '(Dejar en blanco para no cambiar)' : '' ?></label>
                    <input type="password" name="password" <?= $usuario_editar ? '' : 'required' ?>>
                </div>
                <div class="form-group">
                    <label>Rol del Usuario</label>
                    <select name="rol_id" required>
                        <option value="">Seleccione un rol...</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= $r['id'] ?>" <?= ($usuario_editar && $usuario_editar['rol_id'] == $r['id']) ? 'selected' : '' ?>>
                                <?= ucfirst($r['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="grid-column: span 2; display: flex; gap: 10px;">
                    <button type="submit" class="btn-success" style="flex-grow: 1; padding: 12px;">
                        <?= $usuario_editar ? 'Guardar Cambios' : 'Crear Usuario' ?>
                    </button>
                    <?php if ($usuario_editar): ?>
                        <a href="usuarios.php" class="btn-secondary" style="padding: 12px; text-decoration: none;">Cancelar</a>
                    <?php endif; ?>
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
                        <th>Acciones</th>
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
                            <td>
                                <a href="usuarios.php?editar=<?= $u['id'] ?>" class="btn-edit">✏️ Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>