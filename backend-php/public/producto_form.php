<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config.php';

$producto = null;
$mensaje = '';
$error = '';

// Si viene un ID, estamos EDITANDO. Buscamos los datos.
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute([':id' => $_GET['id']]);
    $producto = $stmt->fetch();
    if (!$producto) {
        die("Producto no encontrado.");
    }
}

// Procesar el formulario cuando se envía (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $category = trim($_POST['category']);
    $stock = (int)$_POST['stock'];

    // Validaciones básicas en PHP (Backend)
    if ($name === '' || $price <= 0) {
        $error = 'El nombre y un precio válido son obligatorios.';
    } else {
        try {
            if (isset($_POST['id']) && !empty($_POST['id'])) {
                // ACTUALIZAR
                $sql = "UPDATE products SET name=:name, description=:desc, price=:price, category=:cat, stock=:stock WHERE id=:id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':name' => $name, ':desc' => $description, ':price' => $price, 
                    ':cat' => $category, ':stock' => $stock, ':id' => $_POST['id']
                ]);
                $mensaje = 'Producto actualizado correctamente.';
                // Refrescamos los datos del producto
                $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
                $stmt->execute([':id' => $_POST['id']]);
                $producto = $stmt->fetch();
            } else {
                // INSERTAR
                $sql = "INSERT INTO products (name, description, price, category, stock) VALUES (:name, :desc, :price, :cat, :stock)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':name' => $name, ':desc' => $description, ':price' => $price, 
                    ':cat' => $category, ':stock' => $stock
                ]);
                $mensaje = 'Producto creado correctamente.';
                header("Location: productos.php");
                exit;
            }
        } catch (Exception $e) {
            $error = 'Error en la BD: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $producto ? 'Editar' : 'Nuevo' ?> Producto - Café Zaraki</title>
    <link rel="stylesheet" href="css/base.css">
    <style>
        .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-top: 20px; max-width: 600px; margin-left: auto; margin-right: auto; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-family: inherit; }
        .btn-group { display: flex; gap: 10px; margin-top: 20px; }
    </style>
</head>
<body>
    <main class="container">
        <div class="card">
            <h2><?= $producto ? '✏️ Editar Producto' : '➕ Nuevo Producto' ?></h2>
            
            <?php if ($mensaje): ?>
                <div style="background: #e8f5e9; color: #2e7d32; padding: 10px; border-radius: 6px; margin-bottom: 15px;"><?= $mensaje ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div style="background: #ffebee; color: #c62828; padding: 10px; border-radius: 6px; margin-bottom: 15px;"><?= $error ?></div>
            <?php endif; ?>

            <!-- FORMULARIO con Validación HTML5 y JavaScript -->
            <form method="POST" onsubmit="return validarFormulario()">
                <!-- Si hay ID, lo enviamos oculto para saber que es actualización -->
                <?php if ($producto): ?>
                    <input type="hidden" name="id" value="<?= $producto['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Nombre del Producto *</label>
                    <input type="text" name="name" id="name" value="<?= $producto ? htmlspecialchars($producto['name']) : '' ?>" required>
                </div>

                <div class="form-group">
                    <label>Descripción</label>
                    <textarea name="description" rows="3"><?= $producto ? htmlspecialchars($producto['description']) : '' ?></textarea>
                </div>

                <div class="form-group">
                    <label>Precio ($) *</label>
                    <input type="number" name="price" id="price" step="0.01" min="0.01" value="<?= $producto ? $producto['price'] : '' ?>" required>
                </div>

                <div class="form-group">
                    <label>Categoría</label>
                    <select name="category">
                        <option value="Bebidas" <?= ($producto && $producto['category'] == 'Bebidas') ? 'selected' : '' ?>>Bebidas</option>
                        <option value="Pastelería" <?= ($producto && $producto['category'] == 'Pastelería') ? 'selected' : '' ?>>Pastelería</option>
                        <option value="Panadería" <?= ($producto && $producto['category'] == 'Panadería') ? 'selected' : '' ?>>Panadería</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Stock (Usa 9999 para productos ilimitados)</label>
                    <input type="number" name="stock" id="stock" min="0" value="<?= $producto ? $producto['stock'] : '9999' ?>" required>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn-primary">Guardar Producto</button>
                    <a href="productos.php" class="btn-secondary" style="text-decoration: none; padding: 10px 16px; border-radius: 6px; text-align: center;">Cancelar</a>
                </div>
            </form>
        </div>
    </main>

    <!-- Validación en JavaScript (Requisito del Profe) -->
    <script>
        function validarFormulario() {
            const name = document.getElementById('name').value.trim();
            const price = parseFloat(document.getElementById('price').value);
            const stock = parseInt(document.getElementById('stock').value);

            if (name.length < 3) {
                alert('El nombre del producto debe tener al menos 3 caracteres.');
                return false;
            }
            if (isNaN(price) || price <= 0) {
                alert('El precio debe ser mayor a 0.');
                return false;
            }
            if (isNaN(stock) || stock < 0) {
                alert('El stock no puede ser negativo.');
                return false;
            }
            return true; // Si todo está bien, envía el formulario
        }
    </script>
</body>
</html>