<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_registro']) && isset($_POST['cantidad_devuelta'])) {
    $id_registro = intval($_POST['id_registro']);
    $cantidad_devuelta = intval($_POST['cantidad_devuelta']);

    registrarDevolucionConTabla($id_registro, $cantidad_devuelta);

    header("Location: dashboard.php?devuelto=1");
    exit;
}
?>