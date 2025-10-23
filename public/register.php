<?php
session_start();
require_once __DIR__ . '/../includes/db.php'; // Asegúrate de que este archivo exista y tenga la conexión a MySQL

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capturar los datos del formulario
    $cedula = trim($_POST['cedula']);
    $usuario_caja = trim($_POST['usuario_caja']);
    $correo_caja = trim($_POST['correo_caja']);
    $nombre = trim($_POST['nombre']);
    $apellido1 = trim($_POST['apellido1']);
    $apellido2 = trim($_POST['apellido2']);
    $departamento = trim($_POST['departamento']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Validar que no exista ya ese correo o usuario
    $checkQuery = "SELECT * FROM empleados WHERE correo_caja = ? OR usuario_caja = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ss", $correo_caja, $usuario_caja);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('El correo o el usuario ya están registrados.'); window.location.href='login.php';</script>";
        exit;
    }

    // Insertar el nuevo usuario
    $query = "INSERT INTO empleados (cedula, usuario_caja, correo_caja, nombre, apellido1, apellido2, servicio_depto, password)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssssss", $cedula, $usuario_caja, $correo_caja, $nombre, $apellido1, $apellido2, $departamento, $password);

    if ($stmt->execute()) {
        echo "<script>alert('Registro exitoso. Ahora puedes iniciar sesión.'); window.location.href='login.php';</script>";
    } else {
        echo "<script>alert('Error al registrar el usuario. Intenta nuevamente.'); window.location.href='login.php';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
