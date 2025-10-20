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

            <p class="mt-4 text-center small">
              ¿No tienes cuenta? <a href="register.php" class="text-decoration-none text-primary fw-semibold">Regístrate aquí</a>
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
