<?php
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

    /*if (!$cedula || !$usuario_caja || !$nombre || !$apellido1 || !$apellido2 || 
        !filter_var($correocaja, FILTER_VALIDATE_EMAIL) || !$servicio || strlen($password) < 6) {
        $errors[] = "Rellena todos los campos correctamente (contraseña mínimo 6 caracteres).";
    } else {
        if (findUserByEmail($correocaja)) {
            $errors[] = "El correo ya está registrado.";
        } else {
            $pwHash = password_hash($password, PASSWORD_DEFAULT);
            $insertId = createUser($cedula, $usuario_caja, $nombre, $apellido1, $apellido2, $correocaja, $servicio, $pwHash);
            if ($insertId) {
                $_SESSION['user_id'] = $insertId;
                $_SESSION['user_name'] = $nombre;
                header("Location: dashboard.php");
                exit;
            } else {
                $errors[] = "Error al crear usuario, intenta nuevamente.";
            }
        }
    }*/
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Registro - Inventario</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
  <div class="registro-wrapper d-flex align-items-center justify-content-center min-vh-100">
    <div class="registro-card d-flex flex-column flex-md-row shadow-lg rounded-4 overflow-hidden animate__animated animate__fadeIn">

      <!-- Panel Izquierdo (Azul) -->
      <div class="registro-left text-white d-flex flex-column justify-content-center align-items-center p-5">
        <h2 class="fw-bold mb-3">¡Bienvenido!</h2>
        <p class="text-center">Crea tu cuenta para gestionar el inventario y los servicios de manera más eficiente.</p>
        <img src="img/Logo-CCSS-CostaRica-negro.png" alt="" class="mt-4" width="120">
      </div>

      <!-- Panel Derecho (Formulario) -->
      <div class="registro-right bg-light p-5 flex-fill">
        <h3 class="text-center text-primary fw-bold mb-4">Crear cuenta</h3>

        <?php if ($errors): ?>
          <div class="alert alert-danger">
            <?php foreach($errors as $e) echo "<div>$e</div>"; ?>
          </div>
        <?php endif; ?>

        <form method="post" class="registro-form">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Cédula</label>
              <input class="form-control" name="cedula" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Usuario caja</label>
              <input class="form-control" name="usuario_caja" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Nombre</label>
              <input class="form-control" name="nombre" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Apellido 1</label>
              <input class="form-control" name="apellido1" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Apellido 2</label>
              <input class="form-control" name="apellido2" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Correo caja</label>
              <input class="form-control" name="correocaja" type="email" placeholder="ejemplo@correo.com" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Servicio o Depto</label>
              <input class="form-control" name="servicio" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Contraseña</label>
              <input class="form-control" name="password" type="password" placeholder="********" minlength="6" required>
            </div>
          </div>

          <div class="form-check my-3">
            <input class="form-check-input" type="checkbox" id="mostrarPassword">
            <label class="form-check-label" for="mostrarPassword">Mostrar contraseña</label>
          </div>

          <button class="btn btn-primary w-100 py-2 fw-semibold rounded-3">Registrarse</button>
        </form>

        <p class="mt-4 text-center small">
          ¿Ya tienes cuenta? <a href="login.php" class="text-decoration-none text-primary fw-semibold">Inicia sesión</a>
        </p>
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

