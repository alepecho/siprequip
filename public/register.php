<?php
// public/register.php
session_start();
require_once __DIR__ . '/../includes/functions.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cedula = trim($_POST['cedula']);
    $usuario_caja = trim($_POST['usuario_caja']);
    $nombre = trim($_POST['nombre']);
    $apellido1 = trim($_POST['apellido1']);
    $apellido2 = trim($_POST['apellido2']);
    $correocaja = trim($_POST['correocaja']);
    $servicio = trim($_POST['servicio']);
    $password = $_POST['password'];

    if (!$cedula || !$usuario_caja || !$nombre || !$apellido1 || !$apellido2 || 
        !filter_var($correocaja, FILTER_VALIDATE_EMAIL) || !$servicio || strlen($password) < 6) {
        $errors[] = "Rellena todos los campos correctamente (contraseña mínimo 6 caracteres).";
    } else {
        if (findUserByEmail($correocaja)) {
            $errors[] = "El correo ya está registrado.";
        } else {
            $pwHash = password_hash($password, PASSWORD_DEFAULT);
            $insertId = createUser($cedula, $usuario_caja, $nombre, $apellido1, $apellido2, $correocaja, $servicio, $pwHash);
            if ($insertId) {
                $success = true;
                $_SESSION['user_id'] = $insertId;
                $_SESSION['user_name'] = $nombre;
                header("Location: dashboard.php");
                exit;
            } else {
                $errors[] = "Error al crear usuario, intenta nuevamente.";
            }
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Registro - Inventario</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/styles.css">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-7">
        <div class="card shadow-lg border-0 rounded-4">
          <div class="card-body p-4">
            <h3 class="card-title text-center text-primary mb-4 fw-bold">Crear cuenta</h3>

            <?php if ($errors): ?>
              <div class="alert alert-danger">
                <?php foreach($errors as $e) echo "<div>$e</div>"; ?>
              </div>
            <?php endif; ?>

            <form method="post">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold">Cédula</label>
                  <input class="form-control" name="cedula" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold">Usuario caja</label>
                  <input class="form-control" name="usuario_caja" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold">Nombre</label>
                  <input class="form-control" name="nombre" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold">Apellido 1</label>
                  <input class="form-control" name="apellido1" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold">Apellido 2</label>
                  <input class="form-control" name="apellido2" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold">Correo caja</label>
                  <input class="form-control" name="correocaja" type="email" placeholder="ejemplo@correo.com" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold">Servicio o Depto</label>
                  <input class="form-control" name="servicio" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold">Contraseña</label>
                  <input class="form-control" name="password" type="password" placeholder="********" minlength="6" required>
                </div>
              </div>

              <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="mostrarPassword">
                <label class="form-check-label" for="mostrarPassword">Mostrar contraseña</label>
              </div>

              <button class="btn btn-primary w-100 rounded-3">Registrarse</button>
            </form>

            <p class="mt-4 text-center small">
              ¿Ya tienes cuenta? <a href="login.php" class="text-decoration-none text-primary fw-semibold">Inicia sesión</a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const checkbox = document.getElementById("mostrarPassword");
  const passwordInput = document.querySelector("input[name='password']");
  
  if (checkbox && passwordInput) {
    checkbox.addEventListener("change", () => {
      passwordInput.type = checkbox.checked ? "text" : "password";
    });
  }
});
</script>
</body>
</html>
