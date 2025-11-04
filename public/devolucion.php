<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

// Establecer headers de seguridad
setSecurityHeaders();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_registro']) && isset($_POST['cantidad_devuelta'])) {
    $id_registro = validateInt($_POST['id_registro'], 1);
    $cantidad_devuelta = validateInt($_POST['cantidad_devuelta'], 1);

    if ($id_registro === false || $cantidad_devuelta === false) {
        die("Datos inválidos");
    }

    registrarDevolucionConTabla($id_registro, $cantidad_devuelta);

    header("Location: dashboard.php?devuelto=1");
    exit;
}
?>