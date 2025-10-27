<?php
session_start();
require_once __DIR__ . '/../includes/db.php'; // Conexión a la base de datos

$errors = [];

// ==========================
// LOGIN
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['correo'])) {
    $correo = trim($_POST['correo']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM empleados WHERE correo_caja=? LIMIT 1");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user || !password_verify($password, $user['contraseña'])) {
        $errors[] = "Correo o contraseña incorrectos.";
    } else {
        $_SESSION['user_id'] = $user['id_empleados'];
        $_SESSION['user_name'] = $user['nombre'];
        header("Location: dashboard.php");
        exit;
    }
}

// ==========================
// REGISTRO
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cedula'])) {
    // Capturar datos
    $cedula = trim($_POST['cedula']);
    $usuario_caja = trim($_POST['usuario_caja']);
    $correo_caja = trim($_POST['correo_caja']);
    $nombre = trim($_POST['nombre']);
    $apellido1 = trim($_POST['apellido1']);
    $apellido2 = trim($_POST['apellido2']);
    $contrasena = trim($_POST['contrasena']);

    // Validar existencia
    $checkStmt = $conn->prepare("SELECT * FROM empleados WHERE correo_caja=? OR usuario_caja=?");
    $checkStmt->bind_param("ss", $correo_caja, $usuario_caja);
    $checkStmt->execute();
    $resCheck = $checkStmt->get_result();
    if ($resCheck->num_rows > 0) {
        echo "<script>alert('Correo o usuario ya registrados'); window.location.href='login.php';</script>";
        exit;
    }

    // Manejar servicio
    $id_servicio = intval($_POST['id_servicio'] ?? 0);
    $nuevo_servicio = trim($_POST['nuevo_servicio'] ?? '');

    if (!empty($nuevo_servicio)) {
        $checkServ = $conn->prepare("SELECT id_servicio FROM servicio WHERE nombre_servicio=?");
        $checkServ->bind_param("s", $nuevo_servicio);
        $checkServ->execute();
        $resServ = $checkServ->get_result();

        if ($resServ->num_rows > 0) {
            $row = $resServ->fetch_assoc();
            $id_servicio = $row['id_servicio'];
        } else {
            $insertServ = $conn->prepare("INSERT INTO servicio (nombre_servicio) VALUES (?)");
            $insertServ->bind_param("s", $nuevo_servicio);
            $insertServ->execute();
            $id_servicio = $conn->insert_id;
            $insertServ->close();
        }
    }

    // Hash de contraseña
    $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);

    // Obtener id_rol de "Empleado"
    $rolStmt = $conn->prepare("SELECT id_rol FROM roles WHERE nombre_rol='Empleado' LIMIT 1");
    $rolStmt->execute();
    $rolRes = $rolStmt->get_result();
    $rolRow = $rolRes->fetch_assoc();
    $id_rol = $rolRow['id_rol'] ?? 1;


    if ($id_servicio === 0) {
    echo "<script>alert('Servicio inválido. Selecciona uno o agrégalo correctamente.'); window.location.href='login.php';</script>";
    exit;
  }

    // Insertar empleado
    $insertStmt = $conn->prepare("INSERT INTO empleados (usuario_caja,cedula,nombre,apellido1,apellido2,  correo_caja,contrasena, id_servicio,  id_rol)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insertStmt->bind_param("isssssisi", $cedula, $usuario_caja, $correo_caja, $nombre, $apellido1, $apellido2, $id_servicio, $contrasena_hash, $id_rol);


    if ($insertStmt->execute()) {
        echo "<script>alert('Registro exitoso. Ahora puedes iniciar sesión.'); window.location.href='login.php';</script>";
    } else {
        echo "Error al registrar: " . $insertStmt->error;
    }

    $insertStmt->close();
}
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
        <div class="mb-3">
          <label class="form-label fw-semibold">Contraseña</label>
          <input class="form-control" name="password" type="password" id="passwordLogin" placeholder="********" required>
        </div>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" id="showPasswordLogin">
          <label class="form-check-label" for="showPasswordLogin">Mostrar contraseña</label>
        </div>
        <button class="btn btn-primary w-100 rounded-3">Iniciar sesión</button>
      </form>

      <div class="text-center mt-3">
        ¿No tienes cuenta? 
        <a href="#" class="text-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#registerModal">
          Regístrate aquí
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Modal Registro -->
<div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content" style="background-color:#f7f9fb;">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="registerModalLabel"><center>Crear cuenta</center></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form method="POST">
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

          <!-- Servicios -->
          <div class="mb-3">
            <label for="id_servicio" class="form-label">Servicio o Depto</label>
            <select class="form-control" id="id_servicio" name="id_servicio">
              <option value="">Seleccione un servicio...</option>
              <?php
                $servicios = $conn->query("SELECT id_servicio, nombre_servicio FROM servicio");
                while ($row = $servicios->fetch_assoc()) {
                    echo '<option value="'.$row['id_servicio'].'">'.$row['nombre_servicio'].'</option>';
                }
              ?>
            </select>
          </div>

          <div class="mb-3">
            <label for="nuevo_servicio" class="form-label">Agregar nuevo servicio (opcional)</label>
            <input type="text" class="form-control" id="nuevo_servicio" name="nuevo_servicio" placeholder="Nombre del nuevo servicio">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Contraseña</label>
            <input class="form-control" name="contrasena" type="password" id="passwordRegister" placeholder="********" required>
          </div>

          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="showPasswordRegister">
            <label class="form-check-label" for="showPasswordRegister">Mostrar contraseña</label>
          </div>

          <button type="submit" class="btn btn-primary w-100">Registrarse</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
  const loginCheckbox = document.getElementById("showPasswordLogin");
  const loginInput = document.getElementById("passwordLogin");
  loginCheckbox?.addEventListener("change", () => {
    loginInput.type = loginCheckbox.checked ? "text" : "password";
  });

  const registerCheckbox = document.getElementById("showPasswordRegister");
  const registerInput = document.getElementById("passwordRegister");
  registerCheckbox?.addEventListener("change", () => {
    registerInput.type = registerCheckbox.checked ? "text" : "password";
  });
});
</script>
</body>
</html>

