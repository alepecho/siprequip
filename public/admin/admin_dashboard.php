<?php
session_start();
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/db.php';

// Validar si el usuario es admin
/*
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] != 3) {
    header("Location: ../login.php");
    exit;
}
*/

// ==============================
// FUNCIONES
// ==============================

// Función para marcar como entregado y actualizar inventario
function marcarEntregado($id_registro) {
    global $conn;

    // Obtener el id_inventario de la solicitud
    $stmt = $conn->prepare("SELECT id_inventario FROM registro_detalle WHERE id_registro = ?");
    $stmt->bind_param("i", $id_registro);
    $stmt->execute();
    $stmt->bind_result($id_inventario);
    $stmt->fetch();
    $stmt->close();

    // Cambiar estado de la solicitud a "Entregado" (id_estado = 1)
    $stmt = $conn->prepare("UPDATE registro_detalle SET id_estado = 1 WHERE id_registro = ?");
    $stmt->bind_param("i", $id_registro);
    $stmt->execute();
    $stmt->close();

    // Cambiar estado del inventario a "Disponible" (id_estado = 1)
    $stmt = $conn->prepare("UPDATE inventario SET id_estado = 1 WHERE id_inventario = ?");
    $stmt->bind_param("i", $id_inventario);
    $stmt->execute();
    $stmt->close();
}

// Función para obtener solicitudes completas
/*function obtenerSolicitudes() {
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
*/
// ==============================
// PROCESAR ACCIONES
// ==============================
$solicitudes = obtenerSolicitudes();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entregar'])) {
    $id = intval($_POST['solicitud_id']);
    marcarEntregado($id);
    header("Location: admin_dashboard.php"); // Refresca la página
    exit;
}
?>

<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Panel Administrador - Inventario</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="estilo2.css">
</head>
<body class="dashboard-body">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="#">Inventario CGI - Admin</a>
        <div class="d-flex align-items-center">
            <span class="navbar-text me-3 text-white">
                Bienvenido, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Administrador'); ?>
            </span>
            <a href="cambiar_contraseña.php" class="btn btn-warning btn-sm me-2">Cambiar contraseña</a>
            <a href="../logout.php" class="btn btn-light btn-sm">Cerrar sesión</a>
        </div>
    </div>
</nav>

<div class="dashboard-wrapper py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-11">
                <h3 class="text-center text-primary fw-bold mb-4">Registro de Solicitudes</h3>

                <div class="table-responsive shadow-sm">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-primary text-center">
                            <tr>
                                <th>#</th>
                                <th>Usuario</th>
                                <th>Servicio</th>
                                <th>Equipo</th>
                                <th>Cantidad</th>
                                <th>Estado</th>
                                <th>Fecha solicitud</th>
                                <th>Devolución</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($solicitudes)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">No hay solicitudes registradas</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($solicitudes as $s): ?>
                                    <tr>
                                        <td class="text-center"><?= $s['id_registro'] ?></td>
                                        <td><?= htmlspecialchars($s['usuario']) ?></td>
                                        <td class="text-center">Servicio #<?= htmlspecialchars($s['id_servicio']) ?></td>
                                        <td><?= htmlspecialchars($s['equipo']) ?></td>
                                        <td class="text-center"><?= $s['cantidad'] ?></td>
                                        <td class="text-center">
                                            <span class="badge <?= strtolower($s['estado']) === 'disponible' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                                <?= htmlspecialchars($s['estado']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($s['fecha_solicitud']) ?></td>
                                        <td><?= htmlspecialchars($s['fecha_entrega']) ?></td>
                                        <td class="text-center">
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="solicitud_id" value="<?= $s['id_registro'] ?>">
                                                <?php if ($s['id_estado'] != 1): ?>
                                                    <button class="btn btn-sm btn-success" name="entregar">Marcar Entregado</button>
                                                <?php else: ?>
                                                    <span class="text-success fw-semibold">Entregado</span>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>
</body>
</html>
