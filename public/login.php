<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Establecer headers de seguridad
setSecurityHeaders();

$errors_login = [];
$errors_register = [];
$errors_forgot = [];

// ==========================
// LOGIN
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['correo'])) {
    // Rate limiting - prevenir ataques de fuerza bruta
    $ip = $_SERVER['REMOTE_ADDR'];
    if (!checkRateLimit('login_' . $ip, 5, 300)) {
        $errors_login[] = "Demasiados intentos. Intenta de nuevo en 5 minutos.";
    } else {
        $correo = cleanInput($_POST['correo']);
        $password = $_POST['password'];

        // Validar email
        if (!validateEmail($correo)) {
            $errors_login[] = "Correo electrónico inválido.";
        } else {
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
                // Regenerar sesión para prevenir session fixation
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $user['id_empleados'];
                $_SESSION['user_name'] = sanitizeOutput($user['nombre']);
                $_SESSION['user_rol'] = $user['id_rol'];
                
                // Generar token CSRF
                generateCSRFToken();

                // ✅ Redirección corregida para administradores
                if ($user['id_rol'] == 3) {
                    header("Location: admin/admin_dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit;
            }
        }
    }
}

// ==========================
// REGISTRO
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cedula'])) {
    $cedula = validateInt(cleanInput($_POST['cedula']));
    $usuario_caja = validateString(cleanInput($_POST['usuario_caja']), 3, 50);
    $correo_caja = cleanInput($_POST['correo_caja']);
    $nombre = validateString(cleanInput($_POST['nombre']), 2, 50);
    $apellido1 = validateString(cleanInput($_POST['apellido1']), 2, 50);
    $apellido2 = validateString(cleanInput($_POST['apellido2']), 2, 50);
    $contrasena = $_POST['contrasena'];

    // Validaciones
    if (!$cedula) {
        $errors_register[] = "Cédula inválida.";
    }
    if (!$usuario_caja) {
        $errors_register[] = "Usuario inválido (3-50 caracteres).";
    }
    if (!validateEmail($correo_caja)) {
        $errors_register[] = "Correo electrónico inválido.";
    }
    if (!$nombre || !$apellido1 || !$apellido2) {
        $errors_register[] = "Nombre y apellidos son requeridos.";
    }
    
    // Validar contraseña segura
    $passValidation = validatePassword($contrasena);
    if (!$passValidation['valid']) {
        $errors_register[] = $passValidation['message'];
    }

    if (empty($errors_register)) {
        $checkStmt = $conn->prepare("SELECT 1 FROM empleados WHERE correo_caja=? OR usuario_caja=?");
        $checkStmt->bind_param("ss", $correo_caja, $usuario_caja);
        $checkStmt->execute();
        $resCheck = $checkStmt->get_result();
        $checkStmt->close();

        if ($resCheck->num_rows > 0) {
            $errors_register[] = "Correo o usuario ya registrados.";
        } else {
            $id_servicio = validateInt($_POST['id_servicio'] ?? 0);
            $nuevo_servicio = validateString(cleanInput($_POST['nuevo_servicio'] ?? ''), 0, 100);

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

            if ($id_servicio === 0 || $id_servicio === false) {
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
                $stmt->bind_param("sisssssii", 
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
}

// ==========================
// OLVIDÉ CONTRASEÑA
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario_forgot'])) {
    // Rate limiting
    $ip = $_SERVER['REMOTE_ADDR'];
    if (!checkRateLimit('forgot_' . $ip, 3, 600)) {
        $errors_forgot[] = "Demasiados intentos. Intenta de nuevo en 10 minutos.";
    } else {
        $usuario_forgot = cleanInput($_POST['usuario_forgot']);
        $cedula_forgot = validateInt(cleanInput($_POST['cedula_forgot']));
        $password_forgot = $_POST['password_forgot'];
        $password_forgot_confirm = $_POST['password_forgot_confirm'];

        if ($password_forgot !== $password_forgot_confirm) {
            $errors_forgot[] = "Las contraseñas no coinciden.";
        } else {
            // Validar contraseña segura
            $passValidation = validatePassword($password_forgot);
            if (!$passValidation['valid']) {
                $errors_forgot[] = $passValidation['message'];
            } else {
                $stmt = $conn->prepare("SELECT id_empleados FROM empleados WHERE (correo_caja=? OR usuario_caja=?) AND cedula=? LIMIT 1");
                $stmt->bind_param("ssi", $usuario_forgot, $usuario_forgot, $cedula_forgot);
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
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="login-body">

<div class="login-wrapper">
  <div class="card">
    <div class="card-body">
      <img src="img/logo-CCSS-CostaRica-negro.png" 
            alt="logo-CCSS" 
            class="img-fluid mb-3 d-block mx-auto"
            style="max-width: 100px; height: auto;">

      <h3 class="card-title text-center text-primary mb-4 fw-bold">Inicio de Sesión</h3>

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
        <form method="post">
          <div class="mb-3">
            <label class="form-label fw-semibold">Usuario</label>
            <input class="form-control" name="usuario_forgot" type="text" placeholder="correo o usuario" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Cédula</label>
            <input class="form-control" name="cedula_forgot" type="text" placeholder="Cédula" required>
          </div>

          <!-- 🔹 NUEVO: Campo con verificación de fuerza -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Nueva contraseña</label>
            <input class="form-control" type="password" name="password_forgot" id="passwordForgot" required>

            <!-- 🔹 Indicador de fuerza -->
            <small id="passwordForgotStrengthText" class="form-text mt-1 fw-semibold"></small>

            <!-- 🔹 Requisitos -->
            <ul id="passwordForgotRequirements" class="list-unstyled small mt-2">
              <li id="f-req-length">• Al menos 8 caracteres</li>
              <li id="f-req-upper">• Una letra mayúscula</li>
              <li id="f-req-lower">• Una letra minúscula</li>
              <li id="f-req-number">• Un número</li>
              <li id="f-req-special">• Un carácter especial (!@#$%^&*)</li>
            </ul>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Confirmar nueva contraseña</label>
            <input class="form-control" type="password" name="password_forgot_confirm" id="passwordForgotConfirm" required>
            <!-- 🔹 Mensaje de error si no coinciden -->
            <small id="passwordMismatch" class="text-danger fw-semibold" style="display:none;">⚠️ Las contraseñas no coinciden</small>
          </div>

          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="showPasswordForgot">
            <label class="form-check-label" for="showPasswordForgot">Mostrar contraseñas</label>
          </div>

          <!-- 🔹 Botón desactivado inicialmente -->
          <button class="btn btn-primary w-100 rounded-3" id="forgotButton" disabled>Restablecer contraseña</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- 🔹 SCRIPT -->
<script>
document.addEventListener("DOMContentLoaded", () => {
  const forgotPasswordInput = document.getElementById("passwordForgot");
  const confirmInput = document.getElementById("passwordForgotConfirm");
  const showPasswords = document.getElementById("showPasswordForgot");
  const forgotButton = document.getElementById("forgotButton");
  const strengthText = document.getElementById("passwordForgotStrengthText");
  const mismatchMsg = document.getElementById("passwordMismatch");

  const reqs = {
    length: document.getElementById("f-req-length"),
    upper: document.getElementById("f-req-upper"),
    lower: document.getElementById("f-req-lower"),
    number: document.getElementById("f-req-number"),
    special: document.getElementById("f-req-special")
  };

  // Mostrar / Ocultar contraseñas
  showPasswords.addEventListener("change", () => {
    const type = showPasswords.checked ? "text" : "password";
    forgotPasswordInput.type = type;
    confirmInput.type = type;
  });

  // 🔹 Validar fuerza
  forgotPasswordInput.addEventListener("input", () => {
    const val = forgotPasswordInput.value;
    const hasLength = val.length >= 8;
    const hasUpper = /[A-Z]/.test(val);
    const hasLower = /[a-z]/.test(val);
    const hasNumber = /\d/.test(val);
    const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(val);

    updateReq(reqs.length, hasLength);
    updateReq(reqs.upper, hasUpper);
    updateReq(reqs.lower, hasLower);
    updateReq(reqs.number, hasNumber);
    updateReq(reqs.special, hasSpecial);

    const score = [hasLength, hasUpper, hasLower, hasNumber, hasSpecial].filter(Boolean).length;

    if (val.length === 0) {
      strengthText.textContent = "";
      forgotButton.disabled = true;
    } else if (score <= 2) {
      strengthText.textContent = "Fuerza: Débil";
      strengthText.style.color = "red";
      forgotButton.disabled = true;
    } else if (score === 3 || score === 4) {
      strengthText.textContent = "Fuerza: Media";
      strengthText.style.color = "orange";
      forgotButton.disabled = true;
    } else {
      strengthText.textContent = "Fuerza: Fuerte";
      strengthText.style.color = "green";
      validatePasswords(); // Verifica coincidencia
    }
  });

  // 🔹 Validar coincidencia
  confirmInput.addEventListener("input", validatePasswords);

  function validatePasswords() {
    const pass1 = forgotPasswordInput.value;
    const pass2 = confirmInput.value;

    if (pass1 === pass2 && strengthText.textContent === "Fuerza: Fuerte") {
      confirmInput.classList.remove("is-invalid");
      confirmInput.classList.add("is-valid");
      mismatchMsg.style.display = "none";
      forgotButton.disabled = false;
    } else {
      confirmInput.classList.remove("is-valid");
      confirmInput.classList.add("is-invalid");
      mismatchMsg.style.display = pass2.length > 0 ? "block" : "none";
      forgotButton.disabled = true;
    }
  }

  function updateReq(element, valid) {
    element.style.color = valid ? "green" : "red";
    element.style.fontWeight = valid ? "bold" : "normal";
  }
});
</script>


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

// SweetAlert2 - Mostrar errores y éxitos
<?php if($errors_login): ?>
  Swal.fire({
    icon: 'error',
    title: 'Error al iniciar sesión',
    html: '<?php foreach($errors_login as $e) echo $e . "<br>"; ?>',
    confirmButtonColor: '#0d6efd'
  });
<?php endif; ?>

<?php if($errors_forgot): ?>
  Swal.fire({
    icon: 'error',
    title: 'Error al restablecer contraseña',
    html: '<?php foreach($errors_forgot as $e) echo $e . "<br>"; ?>',
    confirmButtonColor: '#0d6efd'
  }).then(() => {
    // Abrir modal de olvidé contraseña
    const modal = new bootstrap.Modal(document.getElementById('forgotPasswordModal'));
    modal.show();
  });
<?php endif; ?>

<?php if(!empty($success_forgot)): ?>
  Swal.fire({
    icon: 'success',
    title: '¡Contraseña restablecida!',
    text: '<?php echo $success_forgot; ?>',
    confirmButtonColor: '#198754'
  });
<?php endif; ?>

<?php if($errors_register): ?>
  Swal.fire({
    icon: 'error',
    title: 'Error al registrar',
    html: '<?php foreach($errors_register as $e) echo $e . "<br>"; ?>',
    confirmButtonColor: '#0d6efd'
  }).then(() => {
    // Abrir modal de registro
    const modal = new bootstrap.Modal(document.getElementById('registerModal'));
    modal.show();
  });
<?php endif; ?>
</script>
</body>
</html>