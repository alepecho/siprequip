<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

// --- PHPMailer ---
require_once __DIR__ . '/../includes/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../includes/PHPMailer/SMTP.php';
require_once __DIR__ . '/../includes/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- Verificar sesión activa ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// --- Verificar rol (solo empleados, no admins) ---
if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 3) {
    header("Location: admin_dashboard.php");
    exit;
}

$success = false;
$errors = [];

// ==============================
// FUNCIONES
// ==============================

// Obtener todos los equipos con su estado y fecha de devolución
function obtenerEquipos() {
    global $conn;
    $sql = "
        SELECT inventario.id_inventario, inventario.articulo, estado.nombre AS nombre_estado
        FROM inventario
        JOIN estado ON inventario.id_estado = estado.id_estado
        ORDER BY inventario.articulo ASC
    ";
    $result = $conn->query($sql);
    $equipos = [];
    while ($row = $result->fetch_assoc()) {
        $equipos[] = $row;
    }
    return $equipos;
}

// Verificar si un equipo está disponible en las fechas seleccionadas
function equipoDisponible($id_inventario, $fecha_salida, $fecha_retorno) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM registro_detalle
        WHERE id_inventario = ?
          AND id_estado = 2
          AND (
                (fecha_de_salida <= ? AND fecha_de_retorno >= ?)
                OR
                (fecha_de_salida <= ? AND fecha_de_retorno >= ?)
                OR
                (fecha_de_salida >= ? AND fecha_de_retorno <= ?)
              )
    ");
    $stmt->bind_param(
        "issssss",
        $id_inventario,
        $fecha_salida, $fecha_salida,
        $fecha_retorno, $fecha_retorno,
        $fecha_salida, $fecha_retorno
    );
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count == 0;
}

// Enviar correo a los tres administradores
function enviarNotificacionAdmin($departamento, $equipo, $fecha_salida, $fecha_retorno, $usuario_nombre) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'brandonsanchezpacheco@gmail.com';  // tu Gmail
        $mail->Password = 'arnk lcsj gqyv joiu ';                   // contraseña de aplicación
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('brandonsanchezpacheco@gmail.com', 'Sistema Inventario');
        $mail->addAddress('fmoragarita@gmail.com');
        $mail->addAddress('bsanchez25031@gmail.com');
        $mail->addAddress('Isaacchacon839@gmail.com');

        $mail->isHTML(true);
        $mail->Subject = 'Nueva solicitud de equipo';
        $mail->Body = "
            <h2>Nueva solicitud de equipo</h2>
            <p><strong>Usuario:</strong> $usuario_nombre</p>
            <p><strong>Departamento/Servicio:</strong> $departamento</p>
            <p><strong>Equipo:</strong> $equipo</p>
            <p><strong>Fecha de Entrega:</strong> $fecha_salida</p>
            <p><strong>Fecha de Devolución:</strong> $fecha_retorno</p>
        ";
        $mail->send();
    } catch (Exception $e) {
        error_log("Error al enviar correo: " . $mail->ErrorInfo);
    }
}

// Crear la solicitud en la base de datos (CORREGIDO)
function crearSolicitud($id_usuario, $departamento, $id_inventario, $fecha_salida, $fecha_retorno, $usuario_nombre) {
    global $conn;

    // Obtener datos del equipo
    $stmt = $conn->prepare("SELECT cantidad, id_estado, articulo FROM inventario WHERE id_inventario = ?");
    $stmt->bind_param("i", $id_inventario);
    $stmt->execute();
    $stmt->bind_result($cantidad, $id_estado, $articulo);
    $stmt->fetch();
    $stmt->close();

    if ($id_estado != 1 || $cantidad <= 0) return false;
    if (!equipoDisponible($id_inventario, $fecha_salida, $fecha_retorno)) return false;

    // ✅ Obtener id_servicio del empleado
    $stmt = $conn->prepare("SELECT id_servicio FROM empleados WHERE id_empleados = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->bind_result($id_servicio);
    $stmt->fetch();
    $stmt->close();

    if (!$id_servicio) {
        error_log("El empleado no tiene servicio asignado.");
        return false;
    }

    // ✅ Insertar solicitud con id_servicio incluido
    $stmt = $conn->prepare("
        INSERT INTO registro_detalle 
        (fecha_de_salida, fecha_de_retorno, id_empleados, id_estado, id_inventario, id_servicio)
        VALUES (?, ?, ?, 2, ?, ?)
    ");
    $stmt->bind_param("ssiii", $fecha_salida, $fecha_retorno, $id_usuario, $id_inventario, $id_servicio);
    $resultado = $stmt->execute();
    $stmt->close();

    if ($resultado) {
        // Actualizar estado del equipo
        $stmt = $conn->prepare("UPDATE inventario SET id_estado = 2 WHERE id_inventario = ?");
        $stmt->bind_param("i", $id_inventario);
        $stmt->execute();
        $stmt->close();

        // Notificar a los administradores
        enviarNotificacionAdmin($departamento, $articulo, $fecha_salida, $fecha_retorno, $usuario_nombre);
        return true;
    }
    return false;
}

// ==============================
// PROCESAR FORMULARIO
// ==============================
$equipos = obtenerEquipos();

// Obtener servicio del usuario logueado
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT s.nombre_servicio 
    FROM empleados e 
    JOIN servicio s ON e.id_servicio = s.id_servicio 
    WHERE e.id_empleados = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($nombre_servicio);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departamento = trim($_POST['departamento']);
    $id_inventario = intval($_POST['equipo']);
    $fecha_salida = $_POST['fecha_salida'];
    $fecha_retorno = $_POST['fecha_retorno'];
    $id_usuario = $_SESSION['user_id'];

    $hoy = date('Y-m-d');
    if ($fecha_salida < $hoy) $errors[] = "La fecha de entrega no puede ser anterior a hoy.";
    if ($fecha_retorno < $fecha_salida) $errors[] = "La fecha de devolución no puede ser anterior a la fecha de entrega.";
    if (!$departamento || !$id_inventario || !$fecha_salida || !$fecha_retorno) $errors[] = "Completa todos los campos.";

    if (empty($errors)) {
        if (crearSolicitud($id_usuario, $departamento, $id_inventario, $fecha_salida, $fecha_retorno, $_SESSION['user_name'])) {
            $success = true;
        } else {
            $errors[] = "El equipo no está disponible o ocurrió un error.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Solicitud de Equipo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/estilo1.css">
</head>
<body>
   <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold" href="#">Inventario GSI</a>
      <div class="d-flex">
        <span class="navbar-text me-3 text-white">
          Bienvenido, <?= htmlspecialchars($_SESSION['user_name']); ?>
        </span>
        <a href="logout.php" class="btn btn-light btn-sm">Cerrar sesión</a>
      </div>
    </div>
  </nav>

<div class="container py-5">
    <h3 class="text-center mb-4">Solicitud de Equipo</h3>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ Solicitud enviada correctamente y los administradores han sido notificados.</div>
    <?php elseif ($errors): ?>
        <div class="alert alert-danger">
            <?php foreach($errors as $e) echo "<div>$e</div>"; ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label>Departamento / Servicio</label>
            <input type="text" name="departamento" class="form-control" value="<?= htmlspecialchars($nombre_servicio) ?>" readonly>
        </div>

        <div class="mb-3">
            <label>Equipo</label>
            <select name="equipo" class="form-select" required>
                <option value="">-- Selecciona un equipo --</option>
                <?php foreach($equipos as $e): ?>
                    <?php
                        $estado = $e['nombre_estado'];
                        $nombre = htmlspecialchars($e['articulo']);
                        $texto = $nombre . " (" . $estado . ")";

                        if (strtolower($estado) === 'disponible') {
                            echo "<option value='{$e['id_inventario']}'>✅ $texto</option>";
                        } elseif (strtolower($estado) === 'prestado') {
                            echo "<option value='{$e['id_inventario']}'>⚠️ $texto</option>";
                        } else {
                            echo "<option value='{$e['id_inventario']}'>❌ $texto</option>";
                        }
                    ?>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label>Fecha de Prestamo</label>
                <input type="date" name="fecha_salida" class="form-control" min="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label>Fecha de Devolución</label>
                <input type="date" name="fecha_retorno" class="form-control" min="<?= date('Y-m-d') ?>" required>
            </div>
        </div>

        <button class="btn btn-primary w-100">Enviar Solicitud</button>
    </form>
</div>
</body>
</html>

