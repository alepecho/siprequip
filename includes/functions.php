<?php
// ======================================================
// CONEXIÓN A LA BASE DE DATOS
// ======================================================
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'empleados';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

// ======================================================
// FUNCION: Obtener todos los empleados
// ======================================================
function obtenerEmpleados() {
    global $conn;
    $sql = "SELECT * FROM empleados ORDER BY nombre";
    $result = $conn->query($sql);
    $empleados = [];
    while ($row = $result->fetch_assoc()) {
        $empleados[] = $row;
    }
    return $empleados;
}

// ======================================================
// FUNCION: Obtener todos los servicios
// ======================================================
function obtenerServicios() {
    global $conn;
    $sql = "SELECT * FROM servicio ORDER BY id_servicio";
    $result = $conn->query($sql);
    $servicios = [];
    while ($row = $result->fetch_assoc()) {
        $servicios[] = $row;
    }
    return $servicios;
}

// ======================================================
// FUNCION: Obtener todos los equipos del inventario
// ======================================================
function obtenerInventario() {
    global $conn;
    $sql = "SELECT * FROM inventario ORDER BY articulo";
    $result = $conn->query($sql);
    $inventario = [];
    while ($row = $result->fetch_assoc()) {
        $inventario[] = $row;
    }
    return $inventario;
}
/*
// ======================================================
// FUNCION: Registrar nueva solicitud
// ======================================================
function crearSolicitud($id_empleados, $id_servicio, $id_inventario, $fecha_salida, $fecha_retorno) {
    global $conn;
    $sql = "INSERT INTO registro_detalle (id_empleados, id_servicio, id_inventario, fecha_de_salida, fecha_de_retorno, id_estado)
            VALUES (?, ?, ?, ?, ?, 2)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiss", $id_empleados, $id_servicio, $id_inventario, $fecha_salida, $fecha_retorno);
    return $stmt->execute();
}
*/
// ======================================================
// FUNCION: Marcar una solicitud como entregada
// ======================================================
/*
function marcarEntregado($id_registro) {
    global $conn;
    $sql = "UPDATE registro_detalle SET id_estado = 1 WHERE id_registro = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_registro);
    $stmt->execute();
}
*/
// ======================================================
// FUNCION: Obtener todas las solicitudes (para admin)
// ======================================================
function obtenerSolicitudes() {
    global $conn;
    $sql = "
        SELECT 
            r.id_registro,
            e.id_empleados,
            CONCAT(e.nombre, ' ', e.apellido1, ' ', e.apellido2) AS usuario,
            s.id_servicio,
            i.articulo AS equipo,
            i.cantidad,
            es.nombre AS estado,
            r.fecha_de_salida AS fecha_solicitud,
            r.fecha_de_retorno AS fecha_entrega,
            r.id_estado
        FROM registro_detalle r
        JOIN empleados e ON r.id_empleados = e.id_empleados
        JOIN servicio s ON r.id_servicio = s.id_servicio
        JOIN inventario i ON r.id_inventario = i.id_inventario
        JOIN estado es ON r.id_estado = es.id_estado
        ORDER BY r.fecha_de_salida DESC
    ";
    $result = $conn->query($sql);
    $solicitudes = [];
    while ($row = $result->fetch_assoc()) {
        $solicitudes[] = $row;
    }
    return $solicitudes;
}
function enviarCorreoEntrega($info) {
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'brandonsanchezpacheco@gmail.com'; // <-- tu correo Gmail
        $mail->Password = 'arnk lcsj gqyv joiu '; // <-- contraseña de aplicación
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        // Remitente
        $mail->setFrom('aramiras@ccss.sa.cr', 'Sistema de Inventario ');

        // Destinatarios (los tres administradores)
        $mail->addAddress('aramiras@ccss.sa.cr');
        $mail->addAddress('gfchaves@ccss.sa.cr');
        $mail->addAddress('basalazar@ccss.sa.cr');

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = '📦 Equipo entregado - Inventario ';
        $mail->Body = "
            <h3>Equipo entregado correctamente</h3>
            <p><strong>Empleado:</strong> {$info['empleado']}</p>
            <p><strong>Equipo:</strong> {$info['articulo']}</p>
            <p><strong>Fecha de salida:</strong> {$info['fecha_de_salida']}</p>
            <p><strong>Fecha de retorno:</strong> {$info['fecha_de_retorno']}</p>
            <p>Este mensaje fue enviado automáticamente por el sistema de inventario</p>
        ";

        $mail->send();
    } catch (Exception $e) {
        error_log("Error al enviar correo: " . $mail->ErrorInfo);
    }
}
?>
