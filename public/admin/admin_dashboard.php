<?php
session_start();
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/db.php';

// --- Validar si el usuario es admin ---
/*
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] != 3) {
    header("Location: ../login.php");
    exit;
}
*/

// ==============================
// FUNCIONES
// ==============================

// Obtener solicitudes completas con JOINs
/*function obtenerSolicitudes() {
    global $conn;
    $sql = "
        SELECT 
            rd.id_registro,
            e.nombre AS nombre_usuario,
            s.nombre_servicio AS departamento,
            i.articulo AS equipo,
            i.cantidad,
            est.nombre AS estado,
            rd.id_estado,
            rd.fecha_de_salida AS fecha_solicitud,
            rd.fecha_de_retorno AS fecha_entrega
        FROM registro_detalle rd
        JOIN empleados e ON rd.id_empleados = e.id_empleados
        JOIN servicio s ON e.id_servicio = s.id_servicio
        JOIN inventario i ON rd.id_inventario = i.id_inventario
        JOIN estado est ON rd.id_estado = est.id_estado
        ORDER BY rd.id_registro DESC
    ";
    $result = $conn->query($sql);
    $solicitudes = [];
    while ($row = $result->fetch_assoc()) {
        $solicitudes[] = $row;
    }
    return $solicitudes;
}
*/
// Marcar solicitud como entregada
/*function marcarEntregado($id_registro) {
    global $conn;
    $stmt = $conn->prepare("UPDATE registro_detalle SET id_estado = 1 WHERE id_registro = ?");
    $stmt->bind_param("i", $id_registro);
    $stmt->execute();
    $stmt->close();
}*/

$solicitudes = obtenerSolicitudes();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['entregar'])) {
    $id = intval($_POST['solicitud_id']);
    marcarEntregado($id);
    header("Location: admin_dashboard.php");
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
      <a class="navbar-brand fw-bold" href="#">Inventario GSI - Admin</a>
      <div class="d-flex">
        <span class="navbar-text me-3 text-white">
          Bienvenido, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Administrador'); ?>
        </span>
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
                  <th>Departamento</th>
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
                      <td><?= htmlspecialchars($s['id_empleados']) ?></td>
                      <td><?= htmlspecialchars($s['departamento']) ?></td>
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
