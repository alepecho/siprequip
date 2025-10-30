<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/PHPMailer/Exception.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ===================================================
// CREAR USUARIO
// ===================================================
function createUser($cedula, $usuario_caja, $nombre, $apellido1, $apellido2, $correo_caja, $servicio_departamento, $contraseña) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO empleados_caja (cedula, usuario_caja, nombre, apellido1, apellido2, correo_caja, servicio_departamento, contraseña)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $cedula, $usuario_caja, $nombre, $apellido1, $apellido2, $correo_caja, $servicio_departamento, $contraseña);
    if ($stmt->execute()) {
        return $conn->insert_id;
    }
    return false;
}

// ===================================================
// BUSCAR USUARIO POR CORREO
// ===================================================
function findUserByEmail($correo_caja) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM empleados WHERE correo_caja = ?");
    $stmt->bind_param("s", $correo_caja);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// ===================================================
// OBTENER SOLICITUDES
// ===================================================
function obtenerSolicitudes() {
    global $conn;
    $sql = "SELECT 
                rd.id_registro,
                e.nombre AS empleado,
                i.articulo,
                rd.fecha_de_salida,
                rd.fecha_de_retorno,
                es.nombre AS estado
            FROM registro_detalle rd
            JOIN empleados e ON rd.id_empleados = e.id_empleados
            JOIN inventario i ON rd.id_inventario = i.id_inventario
            JOIN estado es ON rd.id_estado = es.id_estado
            ORDER BY rd.fecha_de_salida DESC";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// ===================================================
// MARCAR SOLICITUD COMO ENTREGADA Y ENVIAR CORREO
// ===================================================
function marcarEntregado($id_registro) {
    global $conn;

    // Actualizar estado
    $stmt = $conn->prepare("UPDATE registro_detalle SET id_estado = 2 WHERE id_registro = ?");
    $stmt->bind_param("i", $id_registro);
    $stmt->execute();

    // Obtener información de la solicitud para el correo
    $sql = "SELECT 
                e.nombre AS empleado,
                e.correo AS correo_empleado,
                i.articulo,
                rd.fecha_de_salida,
                rd.fecha_de_retorno
            FROM registro_detalle rd
            JOIN empleados e ON rd.id_empleados = e.id_empleados
            JOIN inventario i ON rd.id_inventario = i.id_inventario
            WHERE rd.id_registro = ?";
    $stmt2 = $conn->prepare($sql);
    $stmt2->bind_param("i", $id_registro);
    $stmt2->execute();
    $info = $stmt2->get_result()->fetch_assoc();

    if ($info) {
        enviarCorreoEntrega($info);
    }

    return true;
}

// ===================================================
// ENVIAR CORREO A ADMINISTRADORES
// ===================================================
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
        $mail->setFrom('brandonsanchezpacheco@gmail.com', 'Sistema de Inventario ');

        // Destinatarios (los tres administradores)
        $mail->addAddress('fmoragarita@gmail.com');
        $mail->addAddress('Isaacchacon839@gmail.com');
        $mail->addAddress('bsanchez25031@gmail.com');

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = '📦 Equipo entregado - Inventario ';
        $mail->Body = "
            <h3>Equipo entregado correctamente</h3>
            <p><strong>Empleado:</strong> {$info['empleado']}</p>
            <p><strong>Equipo:</strong> {$info['articulo']}</p>
            <p><strong>Fecha de salida:</strong> {$info['fecha_de_salida']}</p>
            <p><strong>Fecha de retorno:</strong> {$info['fecha_de_retorno']}</p>
            <p>Este mensaje fue enviado automáticamente por el sistema de inventario TECHZONE.</p>
        ";

        $mail->send();
    } catch (Exception $e) {
        error_log("Error al enviar correo: " . $mail->ErrorInfo);
    }
}
?>
