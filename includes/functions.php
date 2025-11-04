<?php
// Importar clases de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/PHPMailer/Exception.php';

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
// ======================================================
// FUNCION: Registrar log de correo enviado
// ======================================================
function registrarLogCorreo($id_registro, $destinatario, $asunto, $estado, $error = null) {
    global $conn;
    $sql = "INSERT INTO registro_mail_log 
            (id_registro, destinatario, estado_envio) 
            VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $id_registro, $destinatario, $estado);
    return $stmt->execute();
}

// ======================================================
// FUNCION: Enviar correo de entrega
// ======================================================
function enviarCorreoEntrega($info) {
    $mail = new PHPMailer(true);
    
    // Destinatarios: empleado solicitante y administradores
    $destinatarios = [];
    
    // Agregar el correo del empleado si existe
    if (!empty($info['correo_empleado']) && filter_var($info['correo_empleado'], FILTER_VALIDATE_EMAIL)) {
        $destinatarios[] = $info['correo_empleado'];
    }
    
    // Agregar administradores
    $destinatarios[] = 'gfchaves@ccss.sa.cr';
    $destinatarios[] = 'basalazar@ccss.sa.cr';
    
    // Verificar que tengamos al menos un destinatario
    if (empty($destinatarios)) {
        error_log("Error: No hay destinatarios válidos para el correo");
        return false;
    }
    
    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'brandonsanchezpacheco@gmail.com';
        $mail->Password = 'arnk lcsj gqyv joiu ';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        // Remitente
        $mail->setFrom('aramiras@ccss.sa.cr', 'Sistema de Inventario');

        // Agregar destinatarios al correo
        $destinatariosAgregados = 0;
        foreach ($destinatarios as $email) {
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $mail->addAddress($email);
                $destinatariosAgregados++;
            }
        }
        
        // Verificar que se agregaron destinatarios
        if ($destinatariosAgregados === 0) {
            throw new Exception("No se pudieron agregar destinatarios válidos");
        }

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = '📦 Equipo entregado - Inventario';
        $mail->Body = "
            <h3>Equipo entregado correctamente</h3>
            <p><strong>Empleado:</strong> {$info['empleado']}</p>
            <p><strong>Equipo:</strong> {$info['articulo']}</p>
            <p><strong>Fecha de salida:</strong> {$info['fecha_de_salida']}</p>
            <p><strong>Fecha de retorno:</strong> {$info['fecha_de_retorno']}</p>
            <p>Este mensaje fue enviado automáticamente por el sistema de inventario</p>
        ";

        $mail->send();
        
        // Registrar el envío exitoso para cada destinatario
        foreach ($destinatarios as $email) {
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                registrarLogCorreo(
                    $info['id_registro'],
                    $email,
                    $mail->Subject,
                    'SI'
                );
            }
        }
        
        return true;
    } catch (Exception $e) {
        // Registrar el error para cada destinatario
        foreach ($destinatarios as $email) {
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                registrarLogCorreo(
                    $info['id_registro'],
                    $email,
                    $mail->Subject,
                    'NO',
                    $mail->ErrorInfo
                );
            }
        }
        error_log("Error al enviar correo: " . $e->getMessage());
        return false;
    }
}
?>
