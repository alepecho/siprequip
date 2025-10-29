<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$errors_login = [];
$errors_register = [];
$errors_forgot = [];

// ==========================
// LOGIN
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['correo'])) {
    $correo = trim($_POST['correo']);
    $password = $_POST['password'];

    // Obtenemos todos los datos del usuario, incluyendo su rol
    $stmt = $conn->prepare("SELECT * FROM empleados WHERE correo_caja=? LIMIT 1");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user || !password_verify($password, $user['contrasena'])) {
        $errors_login[] = "Correo o contraseña incorrectos.";
    } else {
        $_SESSION['user_id'] = $user['id_empleados'];
        $_SESSION['user_name'] = $user['nombre'];
        $_SESSION['user_rol'] = $user['id_rol'];

        // ✅ Redirección corregida para administradores
        if ($user['id_rol'] == 3) {
            header("Location: admin/admin_dashboard.php");
        } else {
            header("Location: dashboard.php");
        }
        exit;
    }
}

// ==========================
// REGISTRO
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cedula'])) {
    $cedula = trim($_POST['cedula']);
    $usuario_caja = trim($_POST['usuario_caja']);
    $correo_caja = trim($_POST['correo_caja']);
    $nombre = trim($_POST['nombre']);
    $apellido1 = trim($_POST['apellido1']);
    $apellido2 = trim($_POST['apellido2']);
    $contrasena = trim($_POST['contrasena']);

    $checkStmt = $conn->prepare("SELECT 1 FROM empleados WHERE correo_caja=? OR usuario_caja=?");
    $checkStmt->bind_param("ss", $correo_caja, $usuario_caja);
    $checkStmt->execute();
    $resCheck = $checkStmt->get_result();
    $checkStmt->close();

    if ($resCheck->num_rows > 0) {
        $errors_register[] = "Correo o usuario ya registrados.";
    } else {
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
            $checkServ->close();
        }

        if ($id_servicio === 0) {
            $errors_register[] = "Servicio inválido. Selecciona uno o agrégalo correctamente.";
        } else {
            $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);
            $rolStmt = $conn->prepare("SELECT id_rol FROM roles WHERE nombre_rol='Empleado' LIMIT 1");
            $rolStmt->execute();
            $rolRes = $rolStmt->get_result();
            $rolRow = $rolRes->fetch_assoc();
            $id_rol = $rolRow['id_rol'] ?? 1;
            $rolStmt->close();

            $stmt = $conn->prepare("INSERT INTO empleados (
                usuario_caja, cedula, nombre, apellido1, apellido2, correo_caja, contrasena, id_servicio, id_rol
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssii", 
                $usuario_caja, 
                $cedula, 
                $nombre, 
                $apellido1, 
                $apellido2, 
                $correo_caja, 
                $contrasena_hash, 
                $id_servicio,     
                $id_rol
            );
            if(!$stmt->execute()){
                $errors_register[] = "Error al registrar. Intenta de nuevo.";
            }
            $stmt->close();
        }
    }
}

// ==========================
// OLVIDÉ CONTRASEÑA
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario_forgot'])) {
    $usuario_forgot = trim($_POST['usuario_forgot']);
    $cedula_forgot = trim($_POST['cedula_forgot']);
    $password_forgot = $_POST['password_forgot'];
    $password_forgot_confirm = $_POST['password_forgot_confirm'];

    if ($password_forgot !== $password_forgot_confirm) {
        $errors_forgot[] = "Las contraseñas no coinciden.";
    } else {
        $stmt = $conn->prepare("SELECT id_empleados FROM empleados WHERE (correo_caja=? OR usuario_caja=?) AND cedula=? LIMIT 1");
        $stmt->bind_param("sss", $usuario_forgot, $usuario_forgot, $cedula_forgot);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $errors_forgot[] = "No se encontró usuario con esos datos.";
        } else {
            $new_hash = password_hash($password_forgot, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE empleados SET contrasena=? WHERE id_empleados=?");
            $updateStmt->bind_param("si", $new_hash, $user['id_empleados']);
            $updateStmt->execute();
            $updateStmt->close();
            $success_forgot = "Contraseña restablecida con éxito. Ahora puedes iniciar sesión.";
        }
    }
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

      <?php if($errors_login): ?>
        <div class="alert alert-danger text-center">
          <?php foreach($errors_login as $e) echo "<div>$e</div>"; ?>
        </div>
      <?php endif; ?>

      <!-- LOGIN -->
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

      <div class="text-center mt-2">
        <a href="#" class="text-secondary" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">
          Olvidé mi contraseña
        </a>
      </div>
    </div>
  </div>
</div>

<!-- MODAL REGISTRO -->
<?php require_once 'register.php';?>

<!-- MODAL OLVIDÉ CONTRASEÑA -->
<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-primary">Restablecer Contraseña</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <?php if($errors_forgot): ?>
          <div class="alert alert-danger">
            <?php foreach($errors_forgot as $e) echo "<div>$e</div>"; ?>
          </div>
        <?php elseif(!empty($success_forgot)): ?>
          <div class="alert alert-success"><?php echo $success_forgot; ?></div>
        <?php endif; ?>
        <form method="post">
          <div class="mb-3">
            <label class="form-label fw-semibold">Usuario</label>
            <input class="form-control" name="usuario_forgot" type="text" placeholder="correo o usuario" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Cédula</label>
            <input class="form-control" name="cedula_forgot" type="text" placeholder="Cédula" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Nueva contraseña</label>
            <input class="form-control" type="password" name="password_forgot" id="passwordForgot" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Confirmar nueva contraseña</label>
            <input class="form-control" type="password" name="password_forgot_confirm" id="passwordForgotConfirm" required>
          </div>
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="showPasswordForgot">
            <label class="form-check-label" for="showPasswordForgot">Mostrar contraseñas</label>
          </div>
          <button class="btn btn-primary w-100 rounded-3">Restablecer contraseña</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Mostrar/ocultar contraseña login
document.getElementById('showPasswordLogin').addEventListener('change', function() {
  document.getElementById('passwordLogin').type = this.checked ? 'text' : 'password';
});

// Mostrar/ocultar contraseña olvidé
document.getElementById('showPasswordForgot').addEventListener('change', function() {
  ['passwordForgot','passwordForgotConfirm'].forEach(function(id){
    document.getElementById(id).type = this.checked ? 'text' : 'password';
  }.bind(this));
});

// Convertir automáticamente a mayúsculas todos los inputs de texto y correo
document.querySelectorAll('input[type="text"], input[type="email"]').forEach(function(input){
  input.addEventListener('input', function(){
    this.value = this.value.toUpperCase();
  });
});
</script>
</body>
</html>