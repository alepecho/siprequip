<?php
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


function findUserByEmail($correo_caja) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM empleados_caja WHERE correo_caja = ?");
    $stmt->bind_param("s", $correo_caja);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
function crearSolicitud($usuario_id, $departamento, $equipo, $cantidad, $estado, $comentario) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO solicitudes (usuario_id, departamento, equipo, cantidad, estado, comentario, fecha_solicitud)
                            VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ississ", $usuario_id, $departamento, $equipo, $cantidad, $estado, $comentario);
    return $stmt->execute();
}

// --- Notificación a los administradores ---
function enviarNotificacionAdmin($departamento, $equipo, $cantidad, $estado, $comentario, $usuario) {
    $asunto = "Nueva Solicitud de Equipo - Inventario GSI";
    $mensaje = "
        <h2>Nueva Solicitud de Equipo</h2>
        <p><b>Usuario:</b> {$usuario}</p>
        <p><b>Departamento:</b> {$departamento}</p>
        <p><b>Equipo:</b> {$equipo}</p>
        <p><b>Cantidad:</b> {$cantidad}</p>
        <p><b>Estado:</b> {$estado}</p>
        <p><b>Comentario:</b> {$comentario}</p>
        <p><small>Fecha: " . date("d/m/Y H:i:s") . "</small></p>
    ";

    $cabeceras = "MIME-Version: 1.0\r\n";
    $cabeceras .= "Content-type: text/html; charset=UTF-8\r\n";
    $cabeceras .= "From: Inventario GSI <notificaciones@gsi.com>\r\n";

    // Tres correos de administradores
    $admins = ["admin1@gsi.com", "admin2@gsi.com", "admin3@gsi.com"];

    foreach ($admins as $correo) {
        mail($correo, $asunto, $mensaje, $cabeceras);
    }
}
function obtenerSolicitudes() {
    global $conn;
    $sql = "SELECT s.*, u.nombre AS nombre_usuario
            FROM solicitudes s
            JOIN usuarios u ON s.usuario_id = u.id
            ORDER BY s.fecha_solicitud DESC";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function marcarEntregado($id) {
    global $conn;
    $stmt = $conn->prepare("UPDATE solicitudes SET fecha_entrega = NOW() WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}
