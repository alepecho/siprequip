<?php
session_start();
require_once __DIR__ . '/../includes/db.php'; // Conexión a la base de datos
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

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

    // Se usa $user['contrasena'] (sin ñ) para validar el hash.
    if (!$user || !password_verify($password, $user['contrasena'])) {
        $errors[] = "Correo o contraseña incorrectos.";
    } else {
        $_SESSION['user_id'] = $user['id_empleados'];
        $_SESSION['user_name'] = $user['nombre'];
        header("Location: dashboard.php");
        exit;
    }
    $stmt->close(); // Cerrar la sentencia para liberar recursos
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
    $checkStmt = $conn->prepare("SELECT 1 FROM empleados WHERE correo_caja=? OR usuario_caja=?");
    $checkStmt->bind_param("ss", $correo_caja, $usuario_caja);
    $checkStmt->execute();
    $resCheck = $checkStmt->get_result();
    if ($resCheck->num_rows > 0) {
        echo "<script>alert('Correo o usuario ya registrados'); window.location.href='login.php';</script>";
        exit;
    }
    $checkStmt->close();

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
        $checkServ->close();
    }

    if ($id_servicio === 0) {
        echo "<script>alert('Servicio inválido. Selecciona uno o agrégalo correctamente.'); window.location.href='login.php';</script>";
        exit;
    }

    // Hash de contraseña
    $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);

    // Obtener id_rol de "Empleado"
    $rolStmt = $conn->prepare("SELECT id_rol FROM roles WHERE nombre_rol='Empleado' LIMIT 1");
    $rolStmt->execute();
    $rolRes = $rolStmt->get_result();
    $rolRow = $rolRes->fetch_assoc();
    $id_rol = $rolRow['id_rol'] ?? 1;
    $rolStmt->close();

    // Función para registrar empleado (El código PHP es correcto)
function registrarEmpleado($conn, $data) {
    // La lista de columnas no incluye 'id_empleados' para que la BD lo genere.
    $stmt = $conn->prepare("INSERT INTO empleados (
        usuario_caja, cedula, nombre, apellido1, apellido2, correo_caja, contrasena, id_servicio, id_rol
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // Cadena de tipos: 7 strings (hash incluido) y 2 integers.
    $stmt->bind_param("sssssssii", 
        $data['usuario_caja'], 
        $data['cedula'], 
        $data['nombre'], 
        $data['apellido1'], 
        $data['apellido2'], 
        $data['correo_caja'], 
        $data['contrasena_hash'], 
        $data['id_servicio'],     
        $data['id_rol']           
    );

    $stmt->execute();
    $stmt->close();
}

    // Ejecutar registro
    try {
        registrarEmpleado($conn, [
            'usuario_caja' => $usuario_caja,
            'cedula' => $cedula,
            'nombre' => $nombre,
            'apellido1' => $apellido1,
            'apellido2' => $apellido2,
            'correo_caja' => $correo_caja,
            'contrasena_hash' => $contrasena_hash,
            'id_servicio' => $id_servicio,
            'id_rol' => $id_rol
        ]);

        echo "<script>alert('Registro exitoso. Ahora puedes iniciar sesión.'); window.location.href='login.php';</script>";
    } catch (\mysqli\Driver $e) { // Captura de errores de MySQLi
        error_log("Error de BD: " . $e->getMessage());
        echo "Error al registrar: Falló la inserción en la base de datos. Detalles en logs.";
    } catch (Exception $e) { // Otros errores
        error_log("Error PHP: " . $e->getMessage());
        echo "Error al registrar: " . $e->getMessage();
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
<?php require_once 'register.php';?>
</body>
</html>