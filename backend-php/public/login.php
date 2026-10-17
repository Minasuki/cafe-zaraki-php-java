<?php
session_start();

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../config.php';
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = :username AND activo = TRUE");
        $stmt->execute([':username' => $username]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($password, $usuario['password_hash'])) {
            // Login exitoso
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre_completo'];
            $_SESSION['usuario_rol'] = $usuario['rol_id'];
            
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    } else {
        $error = 'Por favor, llena todos los campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Café Zaraki</title>
    <link rel="stylesheet" href="css/base.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            background: white;
            padding: 40px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
        }
        .login-container h2 { text-align: center; color: var(--color-espresso); margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group input {
            width: 100%; padding: 12px; border: 2px solid var(--color-latte);
            border-radius: var(--radius-md); font-family: inherit;
        }
        .form-group input:focus { outline: none; border-color: var(--color-cafe); }
        .btn-login { width: 100%; padding: 14px; font-size: 1.1rem; margin-top: 10px; }
        .error-msg {
            background: #ffebee; color: var(--color-peligro);
            padding: 10px; border-radius: var(--radius-sm); margin-bottom: 20px; text-align: center;
        }
    </style>
</head>
<body>
    <header>
        <h1>☕ Café Zaraki</h1>
    </header>

    <div class="login-container">
        <h2>Iniciar Sesión (Personal)</h2>
        
        <?php if ($error): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Usuario</label>
                <input type="text" id="username" name="username" required placeholder="Ej. admin">
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-primary btn-login">Entrar</button>
        </form>
    </div>
</body>
</html>