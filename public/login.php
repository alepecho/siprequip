<?php
// public/login.php
session_start();
require_once __DIR__ . '/../includes/functions.php';

$errors = [];
/*if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo']);
    $password = $_POST['password'];

    $user = findUserByEmail($correo);
    if (!$user || !password_verify($password, $user['password'])) {
        $errors[] = "Correo o contraseña incorrectos.";
    } else {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nombre'];
        header("Location: dashboard.php");
        exit;
    }
}*/
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Iniciar Sesión - Inventario</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-body">
          <div class="login-wrapper">
           <div class="card">
            <div class="card-body">
              <h3 class="card-title text-center text-primary mb-4 fw-bold">Inicio de Sesión</h3>

            <?php if ($errors): ?>
              <div class="alert alert-danger text-center">
                <?php foreach($errors as $e) echo "<div>$e</div>"; ?>
              </div>
            <?php endif; ?>

            <form method="post">
              <div class="mb-3">
                <label class="form-label fw-semibold">Correo electrónico</label>
                <input class="form-control" name="correo" type="email" placeholder="ejemplo@correo.com" required>
              </div>
              <div class="mb-2">
                <label class="form-label fw-semibold">Contraseña</label>
                <input class="form-control" name="password" type="password" placeholder="********" required>
              </div>

              <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="mostrarPassword">
                <label class="form-check-label" for="mostrarPassword">Mostrar contraseña</label>
              </div>

              <button class="btn btn-primary w-100 rounded-3">Iniciar sesión</button>
            </form>

            <p>
              <!-- Scrollable modal -->
           <!-- Enlace que abre el modal -->
<div class="text-center mt-3">
  ¿No tienes cuenta? 
  <a href="#" class="text-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#registerModal">
    Regístrate aquí
  </a>
</div>

<!-- Modal de registro -->
<div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content" style="background-color:#f7f9fb;">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="registerModalLabel">Crear cuenta</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <form action="register.php" method="POST">
          
          <div class="mb-3">
            <label for="cedula" class="form-label">Cédula</label>
            <input type="text" class="form-control" id="cedula" name="cedula" required>
          </div>

          <div class="mb-3">
            <label for="usuario_caja" class="form-label">Usuario Caja</label>
            <input type="text" class="form-control" id="usuario_caja" name="usuario_caja" required>
          </div>

          <div class="mb-3">
            <label for="correo_caja" class="form-label">Correo Caja</label>
            <input type="email" class="form-control" id="correo_caja" name="correo_caja" required>
          </div>

          <div class="mb-3">
            <label for="nombre" class="form-label">Nombre</label>
            <input type="text" class="form-control" id="nombre" name="nombre" required>
          </div>

          <div class="mb-3">
            <label for="apellido1" class="form-label">Apellido 1</label>
            <input type="text" class="form-control" id="apellido1" name="apellido1" required>
          </div>

          <div class="mb-3">
            <label for="apellido2" class="form-label">Apellido 2</label>
            <input type="text" class="form-control" id="apellido2" name="apellido2">
          </div>

          <div class="mb-3">
            <label for="departamento" class="form-label">Servicio o Depto</label>
            <input type="text" class="form-control" id="departamento" name="departamento" required>
          </div>

          <div class="mb-3">
            <label for="contraseña" class="form-label">Contraseña</label>
            <input type="password" class="form-control" id="contraseña" name="contraseña" required>
          </div>

          <button type="submit" class="btn btn-primary w-100">Registrarse</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Script de Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

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
