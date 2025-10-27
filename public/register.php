<?php
/*session_start();
require_once __DIR__ . '/../includes/db.php';

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

    // Insertar empleado
    $insertStmt = $conn->prepare("INSERT INTO empleados (cedula, usuario_caja, correo_caja, nombre, apellido1, apellido2, id_servicio, contraseña, id_rol)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insertStmt->bind_param("ssssssssi", $cedula, $usuario_caja, $correo_caja, $nombre, $apellido1, $apellido2, $id_servicio, $contrasena_hash, $id_rol);

    if ($conn->query($sql) === TRUE) {
        echo "Usuario registrado correctamente";
        header("Location: login.php");
        exit;
    } else {
        echo "Error al registrar: " . $conn->error;
    }
}
?>
